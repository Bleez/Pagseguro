<?php

namespace Bleez\Pagseguro\Model\Payment;

use Magento\Framework\Webapi\Exception;

class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'pagseguro_boleto';

    protected $_isGateway = true;

    protected $_canCapture = true;

    protected $_canRefund = true;

    protected $_helper;

    protected $_urlBuilder;

    protected $_checkoutSession;

    //protected $_isInitializeNeeded = true;


    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bleez\Pagseguro\Helper\Standard $helper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        $this->_regionFactory = $regionFactory;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $data = $data->getData();
        if (isset($data['additional_data'])) {
            $data = array_merge($data, (array)$data['additional_data']);
//            unset($data['additional_data']);
        }

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $infoInstance = $this->getInfoInstance();

        $infoInstance->setAdditionalInformation('vatId', $data->getData('vatId'));
        $infoInstance->setAdditionalInformation('userHash', $data->getData('userHash'));

        return $this;
    }


    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_helper->initPagseguroLib();

        $directPaymentRequest = new \PagSeguroDirectPaymentRequest();

        $directPaymentRequest->setPaymentMode('DEFAULT');
        $directPaymentRequest->setPaymentMethod('BOLETO');

        $directPaymentRequest->setCurrency("BRL");

        $directPaymentRequest->setReceiverEmail($this->_scopeConfig->getValue('payment/pagseguro_general/email'));

        foreach ($payment->getOrder()->getItems() as $item) {
            if ($item->getPrice()) {
                $directPaymentRequest->addItem(
                    $item->getSku(),
                    $item->getName(),
                    $item->getQtyOrdered(),
                    $item->getPrice() - ($item->getDiscountAmount() / $item->getQtyOrdered())
                );
            }
        }

        if ($payment->getShippingAmount()) {
            $directPaymentRequest->addItem('frete', 'Frete', 1, $payment->getShippingAmount());
        }

        $directPaymentRequest->setReference($payment->getOrder()->getIncrementId());

        $billing = $payment->getOrder()->getBillingAddress();

        $taxVat = $billing->getOrder()->getCustomerTaxvat() ? $billing->getOrder()->getCustomerTaxvat() : $payment->getAdditionalInformation('vatId');

        if($taxVat == null){
            $taxVat = $billing->getVatId();
        }
        
        if ($this->_scopeConfig->getValue('payment/pagseguro_general/environment') == 'sandbox') {
            $email = $this->_scopeConfig->getValue('payment/pagseguro_general/email_comprador');
        }else{
            $email = $billing->getEmail();
        }
        $directPaymentRequest->setSender(
            $billing->getName(),
            $email,
            substr(str_replace(array('-', '(', ')', ' '), '', $billing->getTelephone()), -11, 2),
            substr(str_replace(array('-', '(', ')', ' '), '', $billing->getTelephone()), -8),
            'CPF',
            $taxVat
        );

        $directPaymentRequest->setSenderHash($payment->getAdditionalInformation('userHash'));
        $end = $billing->getStreet();

        $region = $this->_regionFactory->create()->loadByName($billing->getRegion(), $billing->getCountryId());

        $directPaymentRequest->setShippingAddress(
            str_replace('-', '', $billing->getPostcode()),
            isset($end[0]) ? trim($end[0]) : null,
            isset($end[1]) ? trim($end[1]) : null,
            isset($end[3]) ? trim($end[3]) : null,
            isset($end[2]) ? trim($end[2]) : null,
            $billing->getCity(),
            $region->getCode(),
            $billing->getCountryId()
        );




        try{
            $credentials = $this->_helper->getCredentials();
            $return = $directPaymentRequest->register($credentials);

            $this->_checkoutSession->setData('pagSeguroBoleto', $return->getPaymentLink());
            $payment->setIsTransactionPending(true);
            return $this;
        }catch (Exception $e){
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionCode = $payment->getRefundTransactionId();

        try {

            $this->_helper->initPagseguroLib();

            $credentials = $this->_helper->getCredentials();

            $refund = \PagSeguroRefundService::createRefundRequest($credentials, $transactionCode);

            if($refund == "OK"){
                $payment->setAmountRefunded($amount);
                $payment->setBaseAmountRefunded($amount);
                $payment->setBaseAmountRefundedOnline($amount);
            }

        } catch (\PagSeguroServiceException $e) {
            die($e->getMessage());
        }
        return $this;
    }


    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }

//    public function getCheckoutRedirectUrl()
//    {
//        return $this->_urlBuilder->getUrl('paypal/payflowexpress/start');
//    }

    public function canUseForCurrency($currencyCode)
    {
        if($currencyCode == 'BRL'){
            return true;
        }else{
            return false;
        }
    }

}