<?php
/**
 * Created by PhpStorm.
 * User: thiago
 * Date: 09/06/16
 * Time: 17:26
 */

namespace Bleez\Pagseguro\Block;


class Checkout extends \Magento\Framework\View\Element\Template
{
    public function isPagseguroActive(){
        if($this->_scopeConfig->getValue('payment/pagseguro_standard/active') || $this->_scopeConfig->getValue('payment/pagseguro_cc/active') || $this->_scopeConfig->getValue('payment/pagseguro_boleto/active') || $this->_scopeConfig->getValue('payment/pagseguro_debito/active')) {
            return true;
        }
        return false;
    }

    public function getPagseguroScript(){
        if($this->_scopeConfig->getValue('payment/pagseguro_general/environment') == 'sandbox'){
            return '<script src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>';
        }else{
            return '<script src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"/></script>';
        }
    }

    public function getPagseguroPaymentMessages(){
        $messages['standard'] = $this->_scopeConfig->getValue('payment/pagseguro_standard/message');
        $messages['boleto'] = $this->_scopeConfig->getValue('payment/pagseguro_boleto/message');
        $messages['debito'] = $this->_scopeConfig->getValue('payment/pagseguro_debito/message');
        return json_encode($messages);
    }

}