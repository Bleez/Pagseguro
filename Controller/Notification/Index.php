<?php

namespace Bleez\Pagseguro\Controller\Notification;

class Index extends \Magento\Framework\App\Action\Action {

    protected $_logger;

    protected $_helper;

    protected $_notification;

    protected $_modelOrder;

    public function __construct(\Magento\Payment\Model\Method\Logger $logger, \Magento\Sales\Model\Order $modelOrder, \Bleez\Pagseguro\Helper\Standard $helper, \Bleez\Pagseguro\Model\Notification $notification, \Magento\Framework\App\Action\Context $context)
    {
        $this->_notification = $notification;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_modelOrder = $modelOrder;
        parent::__construct($context);
    }

    public function execute()
    {
        $code = ($this->_request->getParam('notificationCode') && trim($this->_request->getParam('notificationCode')) !== "" ?
            trim($this->_request->getParam('notificationCode')) : null);
        $type = ($this->_request->getParam('notificationType') && trim($this->_request->getParam('notificationType')) !== "" ?
            trim($this->_request->getParam('notificationType')) : null);

        if(!preg_match('/^[0-9A-Z]{6}\-[0-9A-Z]{12}\-[0-9A-Z]{12}\-[0-9A-Z]{6}$/', strtoupper($code))) {
            return false;
        }

        if ($code && $type) {

            $this->_helper->initPagseguroLib();

            $notificationType = new \PagSeguroNotificationType($type);

            $strType = $notificationType->getTypeFromValue();

            switch ($strType) {

                case 'TRANSACTION':
                    $notification = $this->_notification->transactionNotification($code);
                    if($notification){
                        $this->_modelOrder->loadByIncrementId((string)$notification->getReference());
                        $this->_helper->setStatusOrder($this->_modelOrder, $notification);
                    }
                    break;

                case 'APPLICATION_AUTHORIZATION':
                    $this->_notification->authorizationNotification($code);
                    break;

                case 'PRE_APPROVAL':
                    $this->_notification->preApprovalNotification($code);
                    break;

            }

        }

    }

}