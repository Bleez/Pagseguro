<?php
namespace Bleez\Pagseguro\Model;

use Bleez\Pagseguro\Api\RedirectInterface;

class Redirect implements RedirectInterface{

    protected $_checkoutSession;

    public function __construct(\Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->_checkoutSession = $checkoutSession;
    }

    public function getRedirectStandard(){
        return $this->_checkoutSession->getData('pagSeguroUrlRedirect');
    }
}