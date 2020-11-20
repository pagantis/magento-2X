<?php

namespace Pagantis\Pagantis\Controller\Notify;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Pagantis\ModuleUtils\Exception\MerchantOrderNotFoundException;
use Pagantis\OrdersApiClient\Client;
use Pagantis\Pagantis\Helper\Config;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Ddl\Table;
use Pagantis\ModuleUtils\Exception\AmountMismatchException;
use Pagantis\ModuleUtils\Exception\ConcurrencyException;
use Pagantis\ModuleUtils\Exception\NoIdentificationException;
use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\ModuleUtils\Exception\QuoteNotFoundException;
use Pagantis\ModuleUtils\Exception\UnknownException;
use Pagantis\ModuleUtils\Exception\WrongStatusException;
use Pagantis\ModuleUtils\Model\Response\JsonSuccessResponse;
use Pagantis\ModuleUtils\Model\Response\JsonExceptionResponse;
use Pagantis\ModuleUtils\Exception\AlreadyProcessedException;
use Pagantis\ModuleUtils\Model\Log\LogEntry;
use Magento\Framework\App\RequestInterface;
use Pagantis\Pagantis\Model\Ui\ConfigProvider;
use Afterpay\SDK\HTTP\Request as ClearpayRequest;
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as ClearpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\MerchantAccount as ClearpayMerchant;

/**
 * Class Index
 * @package Pagantis\Pagantis\Controller\Notify
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const CONCURRENCY_TABLE = 'Pagantis_orders';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Pagantis_logs';

    /** Seconds to expire a locked request */
    const CONCURRENCY_TIMEOUT = 10;

    /** Payment method */
    const PAYMENT_METHOD = 'pagantis';

    /**
     * EXCEPTION RESPONSES
     */
    const CPO_ERR_MSG = 'Order not confirmed';
    const CPO_OK_MSG = 'Order confirmed';

    /** @var QuoteManagement */
    protected $quoteManagement;

    /** @var PaymentInterface $paymentInterface */
    protected $paymentInterface;

    /** @var OrderRepositoryInterface $orderRepositoryInterface */
    protected $orderRepositoryInterface;

    /** @var Quote $quote */
    protected $quote;

    /** @var QuoteRepository $quoteRepository */
    protected $quoteRepository;

    /** @var mixed $config */
    protected $config;

    /** @var mixed $quoteId */
    protected $quoteId;

    /** @var array $notifyResult */
    protected $notifyResult;

    /** @var mixed $magentoOrderId */
    protected $magentoOrderId;

    /** @var mixed $pagantisOrder */
    protected $pagantisOrder;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var Client $orderClient */
    protected $orderClient;

    /** @var mixed $pagantisOrderId */
    protected $pagantisOrderId;

    /** @var  OrderInterface $magentoOrder */
    protected $magentoOrder;

    /** @var ExtraConfig $extraConfig */
    protected $extraConfig;

    /** @var RequestInterface $_request*/
    protected $_request;

    /** @var mixed $origin */
    protected $token;

    /** @var mixed $countryCode */
    protected $countryCode;

    /** @var ClearpayMerchant $clearpayMerchantAccount */
    protected $clearpayMerchantAccount;

    /** @var Object $clearpayOrder */
    protected $clearpayOrder;

    /** @var string $clearpayCapturedPaymentId */
    protected $clearpayCapturedPaymentId;

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param Quote                    $quote
     * @param QuoteManagement          $quoteManagement
     * @param PaymentInterface         $paymentInterface
     * @param Config                   $config
     * @param QuoteRepository          $quoteRepository
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param ResourceConnection       $dbObject
     * @param Session                  $checkoutSession
     * @param ExtraConfig              $extraConfig
     * @param RequestInterface         $request
     *
     * @throws QuoteNotFoundException
     * @throws \Afterpay\SDK\Exception\InvalidArgumentException
     */
    public function __construct(
        Context $context,
        Quote $quote,
        QuoteManagement $quoteManagement,
        PaymentInterface $paymentInterface,
        Config $config,
        QuoteRepository $quoteRepository,
        OrderRepositoryInterface $orderRepositoryInterface,
        ResourceConnection $dbObject,
        Session $checkoutSession,
        ExtraConfig $extraConfig,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->paymentInterface = $paymentInterface;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->config = $config->getConfig();
        $this->quoteRepository = $quoteRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dbObject = $dbObject;
        $this->checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->token = $this->_request->getParam('token');
        $this->clearpayMerchantAccount = new ClearpayMerchant();
        $this->clearpayMerchantAccount
            ->setMerchantId($this->config['clearpay_merchant_id'])
            ->setSecretKey($this->config['clearpay_merchant_key'])
            ->setApiEnvironment($this->config['clearpay_api_environment'])
        ;
        $this->getQuoteId();
        $this->countryCode = $this->getClearpayOrderCountryCode();
        if (!is_null($this->countryCode)) {
            $this->clearpayMerchantAccount->setCountryCode($this->countryCode);
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws UnknownException
     */
    public function execute()
    {
        $thrownException = false;
        try {
            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getPagantisOrderId();
            $this->getPagantisOrder();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
            $this->processMerchantOrder();
        } catch (\Exception $exception) {
            $thrownException = true;
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->magentoOrderId);
            $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $this->insertLog($exception);
        }

        try {
            if (!$thrownException) {
                $this->confirmpagantisOrder();
                $jsonResponse = new JsonSuccessResponse();
                $jsonResponse->setMerchantOrderId($this->magentoOrderId);
                $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            }
        } catch (\Exception $exception) {
            $this->rollbackMerchantOrder();
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->magentoOrderId);
            $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $jsonResponse->toJson();
            $this->insertLog($exception);
        }

        $this->unblockConcurrency(true);

        if (true) {
            $returnMessage = sprintf(
                "[quoteId=%s][magentoOrderId=%s][pagantisOrderId=%s][message=%s]",
                $this->quoteId,
                $this->magentoOrderId,
                $this->pagantisOrderId,
                $jsonResponse->getResult()
            );
            $this->insertLog(null, $returnMessage);
            $jsonResponse->printResponse();
        } else {
            $returnUrl = $this->getRedirectUrl();
            $returnMessage = sprintf(
                "[quoteId=%s][magentoOrderId=%s][pagantisOrderId=%s][returnUrl=%s]",
                $this->quoteId,
                $this->magentoOrderId,
                $this->pagantisOrderId,
                $returnUrl
            );
            $this->insertLog(null, $returnMessage);
            $this->_redirect($returnUrl);
        }
    }

    /**
     * COMMON FUNCTIONS
     */

    /**
     * @throws ConcurrencyException
     * @throws QuoteNotFoundException
     * @throws UnknownException
     */
    private function checkConcurrency()
    {
        $this->unblockConcurrency();
        $this->blockConcurrency();
    }

    /**
     * @throws MerchantOrderNotFoundException
     */
    private function getMerchantOrder()
    {
        try {
            /** @var Quote quote */
            $this->quote = $this->quoteRepository->get($this->quoteId);
        } catch (\Exception $e) {
            throw new MerchantOrderNotFoundException();
        }
    }

    /**
     * @throws UnknownException
     */
    private function getPagantisOrderId()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection     = $this->dbObject->getConnection();
            $tableName        = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $query            = "select order_id from $tableName where id='$this->quoteId' and token='$this->token'";
            $queryResult      = $dbConnection->fetchRow($query);
            $this->pagantisOrderId = $queryResult['order_id'];
            if ($this->pagantisOrderId == '') {
                throw new NoIdentificationException();
            }
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @throws OrderNotFoundException
     */
    private function getPagantisOrder()
    {
        try {
            $getOrderRequest = new ClearpayRequest();
            $getOrderRequest
                ->setMerchantAccount($this->clearpayMerchantAccount)
                ->setUri("/v1/orders/" . $this->pagantisOrderId)
            ;
            $getOrderRequest->send();

            if ($getOrderRequest->getResponse()->getHttpStatusCode() >= 400) {
                throw new UnknownException("Unable to retrieve order from Clearpay=$this->clearpayOrderId");
            }

            $this->clearpayOrder = $getOrderRequest->getResponse()->getParsedBody();
        } catch (\Exception $e) {
            throw new OrderNotFoundException();
        }
    }

    /**
     * @throws AlreadyProcessedException
     */
    private function checkMerchantOrderStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->getMagentoOrderId();
            throw new AlreadyProcessedException();
        }
    }

    /**
     * @throws AmountMismatchException
     */
    private function validateAmount()
    {
        $pagantisAmount = $this->clearpayOrder->totalAmount->amount;
        $merchantAmount = $this->quote->getGrandTotal();
        if ($pagantisAmount != $merchantAmount) {
            throw new AmountMismatchException($pagantisAmount, $merchantAmount);
        }
    }

    /**
     * @throws UnknownException
     */
    private function processMerchantOrder()
    {
        try {
            $this->saveOrder();
            $this->updateBdInfo();
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return false|string
     * @throws UnknownException
     */
    private function confirmpagantisOrder()
    {
        try {
            $immediatePaymentCaptureRequest = new ClearpayImmediatePaymentCaptureRequest(array(
                'token' => $this->pagantisOrderId
            ));
            $immediatePaymentCaptureRequest->setMerchantAccount($this->clearpayMerchantAccount);
            $immediatePaymentCaptureRequest->send();
            if ($immediatePaymentCaptureRequest->getResponse()->getHttpStatusCode() >= 400) {
                $exception = sprintf(
                    "CleClearpay capture payment error, order token:%s ||Error code:%s",
                    $this->pagantisOrderId,
                    $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->errorCode
                );
                throw new UnknownException($exception);
            }
            if (!$immediatePaymentCaptureRequest->getResponse()->isApproved()) {
                $exception = sprintf(
                    "Clearpay capture payment error, payment was not proccesed token:%s ||Error code:%s",
                    $this->pagantisOrderId,
                    $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->errorCode
                );
                throw new UnknownException($exception);
            }
            $this->clearpayCapturedPaymentId = $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->id;

            $comment = sprintf(
                "Token = %s || Capture Payment Id=%s",
                $this->clearpayOrder->token,
                $this->clearpayCapturedPaymentId
            );
            $this->magentoOrder->addStatusHistoryComment($comment)
                               ->setIsCustomerNotified(false)
                               ->setEntityName('order')
                               ->save();
        } catch (\Exception $e) {
            throw new UnknownException(sprintf("%s", $e->getMessage()));
        }
    }

    /**
     * UTILS FUNCTIONS
     */

    /** STEP 1 CC - Check concurrency
     * @throws QuoteNotFoundException
     */
    private function getQuoteId()
    {
        $this->quoteId = $this->getRequest()->getParam('quoteId');
        if ($this->quoteId == '') {
            throw new QuoteNotFoundException();
        }
    }

    /**
     * @param bool $mode
     *
     * @throws \Exception
     */
    private function unblockConcurrency($mode = false)
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            if ($mode == false) {
                $dbConnection->delete($tableName, "timestamp<".(time() - 5));
            } elseif ($this->quoteId!='') {
                $dbConnection->delete($tableName, "id=".$this->quoteId);
            }
        } catch (Exception $exception) {
            throw new ConcurrencyException();
        }
    }

    /**
     * @throws ConcurrencyException
     * @throws UnknownException
     */
    private function blockConcurrency()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
        $query = "SELECT timestamp FROM $tableName where id='$this->quoteId'";
        $resultsSelect = $dbConnection->fetchRow($query);
        if (isset($resultsSelect['timestamp'])) {
            if ($this->isNotification()) {
                throw new ConcurrencyException();
            } else {
                $query = sprintf(
                    "SELECT timestamp - %s as rest FROM %s %s",
                    (time() - self::CONCURRENCY_TIMEOUT),
                    $tableName,
                    "WHERE id='".$this->quoteId."'"
                );
                $resultsSelect = $dbConnection->fetchRow($query);
                $restSeconds   = isset($resultsSelect['rest']) ? ($resultsSelect['rest']) : 0;
                $expirationSec = ($restSeconds > self::CONCURRENCY_TIMEOUT) ? self::CONCURRENCY_TIMEOUT : $restSeconds;
                if ($expirationSec > 0) {
                    sleep($expirationSec + 1);
                }

                $this->getPagantisOrderId();
                $this->getMagentoOrderId();

                $logMessage  = sprintf(
                    "User waiting %s seconds, default seconds %s, bd time to expire %s seconds[quoteId=%s]",
                    $expirationSec,
                    self::CONCURRENCY_TIMEOUT,
                    $restSeconds,
                    $this->quoteId
                );
                throw new UnknownException($logMessage);
            }
        } else {
            $dbConnection->insert($tableName, array('id'=>$this->quoteId, 'timestamp'=>time()));
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Pagantis OrderId */
    /** STEP 4 GPO - Get Pagantis Order */
    /** STEP 5 COS - Check Order Status */
    /**
     * @param $statusArray
     *
     * @throws \Exception
     */
    private function checkPagantisStatus($statusArray)
    {
        $pagantisStatus = array();
        foreach ($statusArray as $status) {
            $pagantisStatus[] = constant("\Pagantis\OrdersApiClient\Model\Order::STATUS_$status");
        }

        $payed = in_array($this->pagantisOrder->getStatus(), $pagantisStatus);
        if (!$payed) {
            throw new WrongStatusException($this->pagantisOrder->getStatus());
        }
    }

    /** STEP 6 CMOS - Check Merchant Order Status */
    /**
     * @throws \Exception
     */
    private function getMagentoOrderId()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);

            if ($this->pagantisOrderId == '') {
                $this->getPagantisOrderId();
            }
            $pagantisOrderId   = $this->pagantisOrderId;

            $query = sprintf(
                "select mg_order_id from %s where id='%s' and order_id='%s' and token='%s'",
                $tableName,
                $this->quoteId,
                $pagantisOrderId,
                $this->token
            );
            $queryResult  = $dbConnection->fetchRow($query);
            $this->magentoOrderId = $queryResult['mg_order_id'];
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /** STEP 7 VA - Validate Amount */
    /** STEP 8 PMO - Process Merchant Order */
    /**
     * @throws UnknownException
     */
    private function saveOrder()
    {
        try {
            $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
            $this->magentoOrderId = $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);

            /** @var OrderRepositoryInterface magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);

            /*$this->magentoOrder->setIsCustomerNotified(false)
                               ->setEntityName('order')
                               ->setTitle(self::PAYMENT_METHOD)
                               ->setPayment(self::PAYMENT_METHOD)
                               ->save();*/

            if ($this->magentoOrderId == '') {
                throw new UnknownException('Order can not be saved');
            }
        } catch (\Exception $e) {
            throw new UnknownException('saveOrder'.$e->getMessage());
        }
    }

    /**
     * @throws UnknownException
     */
    private function updateBdInfo()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $pagantisOrderId   = $this->clearpayOrder->token;
            $dbConnection->update(
                $tableName,
                array('mg_order_id' => $this->magentoOrderId),
                "order_id='".$this->clearpayOrder->token."' and id='$this->quoteId'"
            );
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /** STEP 9 CPO - Confirmation Pagantis Order */
    /**
     * @throws UnknownException
     */
    private function rollbackMerchantOrder()
    {
        try {
            $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
            $this->magentoOrder->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $this->magentoOrder->save();
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws UnknownException
     */
    private function getRedirectUrl()
    {
        //$returnUrl = 'checkout/#payment';
        $returnUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);

        if ($this->pagantisOrderId == '') {
            $this->getPagantisOrderId();
        }

        if ($this->magentoOrderId == '') {
            $this->getMagentoOrderId();
        }

        if ($this->magentoOrderId!='') {
            /** @var Order $this->magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);
            if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
                $checkoutMessage = sprintf(
                    "[quoteId=%s][magentoOrderId=%s][pagantisOrderId=%s]Setting checkout session",
                    $this->quoteId,
                    $this->magentoOrderId,
                    $this->pagantisOrderId
                );
                $this->insertLog(null, $checkoutMessage);

                $this->checkoutSession
                    ->setLastOrderId($this->magentoOrderId)
                    ->setLastRealOrderId($this->magentoOrder->getIncrementId())
                    ->setLastQuoteId($this->quoteId)
                    ->setLastSuccessQuoteId($this->quoteId)
                    ->setLastOrderStatus($this->magentoOrder->getStatus());
            }

            //Magento status flow => https://docs.magento.com/m2/ce/user_guide/sales/order-status-workflow.html
            //Order Workflow => https://docs.magento.com/m2/ce/user_guide/sales/order-workflow.html
            $orderStatus    = strtolower($this->magentoOrder->getStatus());
            $acceptedStatus = array('processing', 'completed');
            if (in_array($orderStatus, $acceptedStatus)) {
                if (isset($this->extraConfig['PAGANTIS_OK_URL']) &&  $this->extraConfig['PAGANTIS_OK_URL']!= '') {
                    $returnUrl = $this->extraConfig['PAGANTIS_OK_URL'];
                } else {
                    $returnUrl = 'checkout/onepage/success';
                }
            } else {
                if (isset($this->extraConfig['PAGANTIS_KO_URL']) && $this->extraConfig['PAGANTIS_KO_URL'] != '') {
                    $returnUrl = $this->extraConfig['PAGANTIS_KO_URL'];
                } else {
                    //$returnUrl = 'checkout/#payment';
                    $returnUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
                }
            }
        }
        return $returnUrl;
    }

    /**
     * @param null $exceptionMessage
     * @param null $logMessage
     *
     * @throws UnknownException
     */
    private function insertLog($exceptionMessage = null, $logMessage = null)
    { return true;
        try {
            $logEntryJson = '';
            if ($exceptionMessage instanceof \Exception) {
                $logEntry     = new LogEntry();
                $logEntryJson = $logEntry->error($exceptionMessage)->toJson();
            } elseif ($logMessage != null) {
                $logEntry     = new LogEntry();
                $logEntryJson = $logEntry->info($logMessage)->toJson();
            }

            if ($logEntryJson != '') {
                /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
                $dbConnection = $this->dbObject->getConnection();
                $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
                $dbConnection->insert($tableName, array('log' => $logEntryJson));
            }
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return bool
     */
    private function isNotification()
    {
        return true;
    }

    /**
     * @return bool
     */
    private function isRedirect()
    {
        return false;
    }

    /**
     * Find Clearpay country code
     *
     * @throws Exception
     */
    private function getClearpayOrderCountryCode()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection     = $this->dbObject->getConnection();
        $tableName        = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $query            = "select country_code from $tableName where id='$this->quoteId' and token='$this->token'";
        $queryResult      = $dbConnection->fetchRow($query);

        return $queryResult['country_code'];
    }
}
