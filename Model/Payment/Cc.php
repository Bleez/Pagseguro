<?php
namespace Bleez\Pagseguro\Model\Payment;

use Magento\Framework\Webapi\Exception;
use Magento\Payment\Model\Method\TransparentInterface;;

class Cc extends \Magento\Payment\Model\Method\Cc implements TransparentInterface
{
    const CODE = 'pagseguro_cc';

    protected $_code = 'pagseguro_cc';

    protected $_isGateway = true;

    protected $_canCapture = true;

    protected $_canRefund = true;

    protected $_helper;

    protected $_urlBuilder;

    protected $_checkoutSession;

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
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Bleez\Pagseguro\Helper\Standard $helper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,


        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_urlBuilder = $urlBuilder;
        $this->_regionFactory = $regionFactory;
        $this->_helper = $helper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection
        );
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $data = $data->getData();
        if (isset($data['additional_data'])) {
            $data = array_merge($data, (array)$data['additional_data']);
            //unset($data['additional_data']);
        }

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        parent::assignData($data);

        $infoInstance = $this->getInfoInstance();

        $infoInstance->setAdditionalInformation('cc_cpf', $data->getData('cc_cpf'));
        $infoInstance->setAdditionalInformation('cc_ddd', $data->getData('cc_ddd'));
        $infoInstance->setAdditionalInformation('cc_telefone', $data->getData('cc_telefone'));
        $infoInstance->setAdditionalInformation('cc_dob', $data->getData('cc_dob'));
        $infoInstance->setAdditionalInformation('cc_parcelas', $data->getData('cc_parcelas'));
        $infoInstance->setAdditionalInformation('cc_token', $data->getData('cc_token'));
        $infoInstance->setAdditionalInformation('userHash', $data->getData('userHash'));
        $infoInstance->setAdditionalInformation('parcela_amount', $data->getData('parcela_amount'));

        return $this;
    }



    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_helper->initPagseguroLib();

        $directPaymentRequest = new \PagSeguroDirectPaymentRequest();

        $directPaymentRequest->setPaymentMode('DEFAULT');
        $directPaymentRequest->setPaymentMethod('CREDIT_CARD');

        $directPaymentRequest->setCurrency("BRL");

        $directPaymentRequest->setReceiverEmail($this->_scopeConfig->getValue('payment/pagseguro_general/email'));

        foreach ($payment->getOrder()->getItems() as $item) {
            if($item->getPrice()){
                $directPaymentRequest->addItem(
                    $item->getSku(),
                    $item->getName(),
                    $item->getQtyOrdered(),
                    $item->getPrice()-($item->getDiscountAmount()/$item->getQtyOrdered())
                );
            }
        }

        if($payment->getShippingAmount()){
            $directPaymentRequest->addItem('frete', 'Frete', 1, $payment->getShippingAmount());
        }

        $directPaymentRequest->setReference($payment->getOrder()->getIncrementId());

        $billing = $payment->getOrder()->getBillingAddress();
        $taxVat = $billing->getOrder()->getCustomerTaxvat() ? $billing->getOrder()->getCustomerTaxvat() : $payment->getAdditionalInformation('cc_cpf');
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
        


        $billingPagseguro = new \PagSeguroBilling
        (
            array(
                'postalCode' => str_replace('-', '', $billing->getPostcode()),
                'street' => isset($end[0]) ? trim($end[0]) : null,
                'number' => isset($end[1]) ? trim($end[1]) : null,
                'district' => isset($end[2]) ? trim($end[2]) : null,
                'complement' => isset($end[3]) ? trim($end[3]) : null,
                'city' => $billing->getCity(),
                'state' => $region->getCode(),
                'country' => $billing->getCountryId()
            )
        );

        $token = $payment->getAdditionalInformation('cc_token');

        $installment = new \PagSeguroDirectPaymentInstallment(
            array(
                "quantity" => $payment->getAdditionalInformation('cc_parcelas'),
                "value" => $payment->getAdditionalInformation('parcela_amount'),
                //"noInterestInstallmentQuantity" => $payment->getAdditionalInformation('cc_parcelas')
            )
        );


        $cardCheckout = new \PagSeguroCreditCardCheckout(
            array(
                'token' => $token,
                'installment' => $installment,
                'holder' => new \PagSeguroCreditCardHolder(
                    array(
                        'name' => $payment->getCcOwner(),
                        'documents' => array(
                            'type' => 'CPF',
                            'value' => $payment->getAdditionalInformation('cc_cpf')
                        ),
                        'birthDate' => date($payment->getAdditionalInformation('cc_dob')),
                        'areaCode' => $payment->getAdditionalInformation('cc_ddd'),
                        'number' => str_replace('-', '', $payment->getAdditionalInformation('cc_telefone'))
                    )
                ),
                'billing' => $billingPagseguro
            )
        );

        $directPaymentRequest->setCreditCard($cardCheckout);
        $directPaymentRequest->setNotificationURL($this->getUrl('pagseguro/notification/index'));

        try {
            $credentials = $this->_helper->getCredentials();
            $return = $directPaymentRequest->register($credentials);
            $payment->setIsTransactionPending(true);
            return $this;

        }catch(Exception $e){
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

    }

    public function validate()
    {
        \Magento\Payment\Model\Method\AbstractMethod::validate();
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

    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('paypal/payflowexpress/start');
    }

    public function canUseForCurrency($currencyCode)
    {
        if($currencyCode == 'BRL'){
            return true;
        }else{
            return false;
        }
    }

    public function getConfigInterface(){
        return '';
    }

}