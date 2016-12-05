<?php
/**
 * Created by PhpStorm.
 * User: thiago
 * Date: 20/04/16
 * Time: 16:13
 */

namespace Bleez\Pagseguro\Model;


class Notification
{

    public function __construct(\Bleez\Pagseguro\Helper\Standard $helper) {
        $this->_helper = $helper;
    }

    public function transactionNotification($code){
        try {
        $credentials = $this->_helper->getCredentials();

        $transaction = \PagSeguroNotificationService::checkTransaction($credentials, $code);

        if($transaction){
            return $transaction;
        }

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    public function authorizationNotification($code){
        $credentials = $this->_helper->getCredentials();

        try {
            $authorization = \PagSeguroNotificationService::checkAuthorization($credentials, $code);

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    public function preApprovalNotification($code){
        $credentials = $this->_helper->getCredentials();

        try {
            $preApproval = \PagSeguroNotificationService::checkPreApproval($credentials, $code);

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }
}