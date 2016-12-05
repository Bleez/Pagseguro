<?php
/**
 * Created by PhpStorm.
 * User: thiago
 * Date: 20/04/16
 * Time: 16:13
 */

namespace Bleez\Pagseguro\Model;

use \Bleez\Pagseguro\Api\TransparentInterface;

class Transparent implements TransparentInterface
{

    public function __construct(\Bleez\Pagseguro\Helper\Transparent $helper) {
        $this->_helper = $helper;
    }

    public function getSessionId(){
        try {

            $this->_helper->initPagseguroLib();
            $credentials = $this->_helper->getCredentials();
            $sessionId = $this->_helper->getSession($credentials);
            return $sessionId;

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }
}