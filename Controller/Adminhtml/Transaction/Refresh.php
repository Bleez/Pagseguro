<?php

namespace Bleez\Pagseguro\Controller\Adminhtml\Transaction;

class Refresh extends \Magento\Framework\App\Action\Action {

    protected $_logger;

    protected $_helper;

    protected $_notification;

    protected $_modelOrder;

    public function __construct(\Magento\Payment\Model\Method\Logger $logger, \Magento\Sales\Model\Order $modelOrder, \Bleez\Pagseguro\Helper\Standard $helper, \Bleez\Pagseguro\Model\Notification $notification, \Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_notification = $notification;
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_modelOrder = $modelOrder;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {

        $dt = new \DateTime();

        $finalDate = $dt->format('Y-m-d\TH:i');

        $dt->sub(new \DateInterval('P30D'));

        $initialDate = $dt->format('Y-m-d\T').'00:00';

        $options = array(
            'page' => 1, //optional
            'maxPageResults' => 1, //optional
            'initialDate' => $initialDate, //optional
            'finalDate' => $finalDate //optional
        );

        try {

            $this->_helper->initPagseguroLib();

            $credentials = new \PagSeguroApplicationCredentials($this->_scopeConfig->getValue('payment/pagseguro_general/appid_production'), $this->_scopeConfig->getValue('payment/pagseguro_general/appkey_production'));

            $authorization = $this->searchAuthorizations($credentials, $options);

            if ($authorization->getAuthorizations()) {
                if (is_array($authorization->getAuthorizations())) {
                    foreach ($authorization->getAuthorizations() as $authorization) {
                        $this->_modelOrder->loadByIncrementId((string)$authorization->getReference());
                        $this->_helper->setStatusOrder($this->_modelOrder, $authorization);
                    }
                } else {
                    $this->_modelOrder->loadByIncrementId((string)$authorization->getReference());
                    $this->_helper->setStatusOrder($this->_modelOrder, $authorization);
                }
            }
            $this->messageManager->addSuccessMessage('Atualizado');
            $this->_redirect('admin/system_config');

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }


    public function searchByCode(\PagSeguroCredentials $credentials, $transactionCode)
    {

        \LogPagSeguro::info("PagSeguroTransactionSearchService.SearchByCode($transactionCode) - begin");

        $connectionData = new \PagSeguroConnectionData($credentials, 'transactionSearchService');

        try {
            $connection = new \PagSeguroHttpConnection();
            $connection->get(
                $this->buildSearchUrlByCode($connectionData, $transactionCode),
                $connectionData->getServiceTimeout(),
                $connectionData->getCharset()
            );

            return $this->searchByCodeResult($connection, $transactionCode);

        } catch (\PagSeguroServiceException $err) {
            throw $err;
        } catch (\Exception $err) {
            \LogPagSeguro::error("Exception: " . $err->getMessage());
            throw $err;
        }

    }

    protected function buildSearchUrlByCode(\PagSeguroConnectionData $connectionData, $transactionCode)
    {
        $url = $connectionData->getServiceUrl('v3');
        return "{$url}/{$transactionCode}/?" . $connectionData->getCredentialsUrlQuery();
    }

    protected function searchByCodeResult($connection, $code)
    {
        $httpStatus = new \PagSeguroHttpStatus($connection->getStatus());

        switch ($httpStatus->getType()) {
            case 'OK':
                $transaction = \PagSeguroTransactionParser::readTransaction($connection->getResponse());
                \LogPagSeguro::info(
                    "PagSeguroTransactionSearchService.SearchByCode(transactionCode=$code) - end " .
                    $transaction->toString()
                );
                break;
            case 'BAD_REQUEST':
                $errors = \PagSeguroTransactionParser::readErrors($connection->getResponse());
                $err = new \PagSeguroServiceException($httpStatus, $errors);
                \LogPagSeguro::error(
                    "PagSeguroTransactionSearchService.SearchByCode(transactionCode=$code) - error " .
                    $err->getOneLineMessage()
                );
                throw $err;
                break;
            default:
                $err = new\ PagSeguroServiceException($httpStatus);
                \LogPagSeguro::error(
                    "PagSeguroTransactionSearchService.SearchByCode(transactionCode=$code) - error " .
                    $err->getOneLineMessage()
                );
                throw $err;
                break;
        }
        return isset($transaction) ? $transaction : false;
    }

    public function searchByReference(\PagSeguroCredentials $credentials, $reference)
    {
        \LogPagSeguro::info("PagSeguroAuthorizationSearchService.SearchByReference($reference) - begin");
        $connectionData = new \PagSeguroConnectionData($credentials, 'authorizationService');

        try {
            $connection = new \PagSeguroHttpConnection();
            $connection->get(
                $this->buildSearchUrlByReference($connectionData, $reference),
                $connectionData->getServiceTimeout(),
                $connectionData->getCharset()
            );

            return $this->searchAuthorizationsReturn($connection, $reference);

        } catch (\PagSeguroServiceException $err) {
            throw $err;
        } catch (Exception $err) {
            \LogPagSeguro::error("Exception: " . $err->getMessage());
            throw $err;
        }
    }

    protected function buildSearchUrlByReference(\PagSeguroConnectionData $connectionData, $reference)
    {
        $url = $connectionData->getServiceUrl();
        return "{$url}?" . $connectionData->getCredentialsUrlQuery() . '&reference='.$reference;
    }

    protected function searchAuthorizations($credentials, $options){
        \LogPagSeguro::info("PagSeguroAuthorizationSearchService.searchAuthorizations() - begin");
        $connectionData = new \PagSeguroConnectionData($credentials, 'authorizationService');
        try {
            $connection = new \PagSeguroHttpConnection();
            $connection->get(
                $this->buildSearchUrl($connectionData, $options),
                $connectionData->getServiceTimeout(),
                $connectionData->getCharset()
            );
            return $this->searchAuthorizationsReturn($connection);
        } catch (\PagSeguroServiceException $err) {
            throw $err;
        } catch (Exception $err) {
            \LogPagSeguro::error("Exception: " . $err->getMessage());
            throw $err;
        }
    }

    protected function searchAuthorizationsReturn($connection)
    {
        $httpStatus = new \PagSeguroHttpStatus($connection->getStatus());
        switch ($httpStatus->getType()) {
            case 'OK':
                $authorization = \PagSeguroAuthorizationParser::readSearchResult($connection->getResponse());

                \LogPagSeguro::info(
                    "PagSeguroAuthorizationSearchService.searchAuthorizations() - end " .
                    $authorization->toString()
                );
                break;
            case 'BAD_REQUEST':
                $errors = \PagSeguroAuthorizationParser::readErrors($connection->getResponse());
                $err = new \PagSeguroServiceException($httpStatus, $errors);
                \LogPagSeguro::error(
                    "PagSeguroAuthorizationSearchService.searchAuthorizations() - error " .
                    $err->getOneLineMessage()
                );
                throw $err;
                break;
            default:
                $err = new \PagSeguroServiceException($httpStatus);
                \LogPagSeguro::error(
                    "PagSeguroAuthorizationSearchService.searchAuthorizations() - error " .
                    $err->getOneLineMessage()
                );
                throw $err;
                break;
        }
        return isset($authorization) ? $authorization : false;
    }

    protected function buildSearchUrl(\PagSeguroConnectionData $connectionData, $options = null)
    {
        if (!is_null($options)) {
            $options = http_build_query($options, '', '&');
            return $connectionData->getServiceUrl() . "/?" . $connectionData->getCredentialsUrlQuery() . "&" . $options;
        }
        return $connectionData->getServiceUrl() . "/?" . $connectionData->getCredentialsUrlQuery();
    }

}