<?php

namespace Bleez\Pagseguro\Helper;

class Standard extends \Bleez\Pagseguro\Helper\AbstractHelper
{

    public function getPaymentRequest(){
        $paymentRequest = new \PagSeguroPaymentRequest();
        return $paymentRequest;
    }

    public function setStatusOrder(\Magento\Sales\Model\Order $order, \PagSeguroTransaction $transaction){
        $status = $transaction->getStatus()->getValue();
        if($status == 3){
            if($order->getStatus() != 'processing') {
                $order->setStatus('processing');
                $order->setState('processing');
                $order->addStatusHistoryComment('Pagseguro Success', 'processing');
                $order->setTotalPaid($order->getGrandTotal());
                $order->setBaseTotalPaid($order->getGrandTotal());
                $invoice = $order->getInvoiceCollection()->getLastItem();
                $this->_invoice->load($invoice->getId());
                $this->_invoice->setTransactionId($transaction->getCode());
                $this->_invoice->pay();
                $this->_invoice->save();
            }
        }elseif($status == 7){
            $invoice = $order->getInvoiceCollection()->getLastItem();
            $this->_invoice->load($invoice->getId());
            $this->_invoice->cancel();
            $order->cancel();
            $order->addStatusHistoryComment('Pagseguro Cancelled', 'canceled');
        }
        $order->save();
    }


}