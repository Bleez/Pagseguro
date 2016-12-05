<?php
namespace Bleez\Pagseguro\Block;


class Success extends \Magento\Framework\View\Element\Template
{
    protected $_helper;

    protected $_checkoutSession;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Checkout\Model\Session $checkoutSession,
                                array $data)
    {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    public function getBoletoUrl(){
        return $this->_checkoutSession->getData('pagSeguroBoleto');
    }

    public function getDebitoUrl(){
        return $this->_checkoutSession->getData('pagSeguroDebito');
    }

    public function unsetBoletoUrl(){
        return $this->_checkoutSession->unsetData('pagSeguroBoleto');
    }

    public function unsetDebitoUrl(){
        return $this->_checkoutSession->unsetData('pagSeguroDebito');
    }
}