<?php

namespace Bleez\Pagseguro\Model\Payment;

class Standard extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'pagseguro_standard';

    //protected $_isGateway = true;

    //protected $_canCapture = true;

    protected $_canRefund = true;

    protected $_helper;

    protected $_urlBuilder;

    protected $_checkoutSession;

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
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
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


    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_helper->initPagseguroLib();

        $paymentRequest = $this->_helper->getPaymentRequest();

        $paymentRequest->setCurrency("BRL");
        foreach ($payment->getOrder()->getItems() as $item) {
            if($item->getPrice()){
                $paymentRequest->addItem($item->getSku(), $item->getName(), $item->getQtyOrdered(), $item->getPrice()-($item->getDiscountAmount()/$item->getQtyOrdered()));
            }
        }

        if($payment->getShippingAmount()){
            $paymentRequest->addItem('frete', 'Frete', 1, $payment->getShippingAmount());
        }

        $paymentRequest->setReference($payment->getOrder()->getIncrementId());

        $paymentRequest->setRedirectUrl($this->_urlBuilder->getUrl('checkout/onepage/success'));

        //$paymentRequest->addParameter('notificationURL', $this->_urlBuilder->getUrl('pagseguro/notification'));

        $credentials = $this->_helper->getCredentials();

        $url = $paymentRequest->register($credentials);

        $payment->setIsTransactionPending(true);

        $this->_checkoutSession->setData('pagSeguroUrlRedirect', $url);

        return $this;

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

    public function canUseForCurrency($currencyCode)
    {
        if($currencyCode == 'BRL'){
            return true;
        }else{
            return false;
        }
    }

}