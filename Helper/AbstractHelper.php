<?php

namespace Bleez\Pagseguro\Helper;

abstract class AbstractHelper
{

    protected $_scopeConfig;
    protected $_transactionBuilder;
    protected $_invoice;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Sales\Model\Order\Invoice $invoice){
        $this->_scopeConfig = $scopeConfig;
        $this->_invoice = $invoice;
    }

    public function initPagseguroLib(){

        $PagSeguroConfig = array();

        $PagSeguroConfig['credentials'] = array();
        $PagSeguroConfig['credentials']['token']['production'] = $this->_scopeConfig->getValue('payment/pagseguro_general/token_production');;
        $PagSeguroConfig['credentials']['token']['sandbox'] = $this->_scopeConfig->getValue('payment/pagseguro_general/token_sandbox');;
        $PagSeguroConfig['credentials']['appId']['production'] = $this->_scopeConfig->getValue('payment/pagseguro_general/appid_production');;
        $PagSeguroConfig['credentials']['appId']['sandbox'] = $this->_scopeConfig->getValue('payment/pagseguro_general/appid_sandbox');;
        $PagSeguroConfig['credentials']['appKey']['production'] = $this->_scopeConfig->getValue('payment/pagseguro_general/appkey_production');;
        $PagSeguroConfig['credentials']['appKey']['sandbox'] = $this->_scopeConfig->getValue('payment/pagseguro_general/appkey_sandbox');;

        \PagSeguroLibrary::init();
        \PagSeguroLibrary::$config->setEnvironment($this->_scopeConfig->getValue('payment/pagseguro_general/environment'));
        \PagSeguroLibrary::$config->setData('credentials', 'email',  $this->_scopeConfig->getValue('payment/pagseguro_general/email'));
        \PagSeguroLibrary::$config->setData('credentials', 'token',  $PagSeguroConfig['credentials']['token']);
        \PagSeguroLibrary::$config->setData('credentials', 'appId',  $PagSeguroConfig['credentials']['appId']);
        \PagSeguroLibrary::$config->setData('credentials', 'appKey',  $PagSeguroConfig['credentials']['appKey']);

        \PagSeguroLibrary::$config->setApplicationCharset('application', "UTF-8");
        \PagSeguroLibrary::$config->activeLog(BP.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'pagseguro.log');
    }

    public function getCredentials(){
        return \PagSeguroLibrary::$config->getAccountCredentials();
    }
}