<?php

namespace DigitalOrigin\Pmt\Controller\Notify;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use PagaMasTarde\OrdersApiClient\Client;
use DigitalOrigin\Pmt\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class Index
 * @package DigitalOrigin\Pmt\Controller\Notify
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const CONCURRENCY_TABLE = 'pmt_orders';

    /** Concurrency tablename */
    const LOGS_TABLE = 'pmt_logs';

    /** Payment code */
    const PAYMENT_METHOD = 'paylater';

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

    /** @var Array_ $notifyResult */
    protected $notifyResult;

    /** @var mixed $magentoOrderId */
    protected $magentoOrderId;

    /** @var mixed $pmtOrder */
    protected $pmtOrder;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var Client $orderClient */
    protected $orderClient;

    /** @var mixed $pmtOrderId */
    protected $pmtOrderId;

    /** @var  OrderInterface $magentoOrder */
    protected $magentoOrder;

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
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->paymentInterface = $paymentInterface;
        $this->config = $config->getConfig();
        $this->quoteRepository = $quoteRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dbObject = $dbObject;
        $this->checkoutSession = $checkoutSession;
    }

    //MAIN FUNCTION
    public function execute()
    {
        try {
            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getPmtOrderId();
            $this->getPmtOrder();
            $this->checkOrderStatus();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
            $this->processMerchantOrder();
        } catch (\Exception $exception) {
            $this->insertLog($exception);
            $jsonResponse = new JsonResponse($exception);
            $jsonResponse->setOrderId($this->magentoOrderId);
            $response = $jsonResponse->toJson();
        }

        try {
            if (!isset($response)) {
                $response = $this->confirmPmtOrder();
            }
        } catch (\Exception $exception) {
            $this->insertLog($exception);
            $this->rollbackMerchantOrder();
            $jsonResponse = new JsonResponse();
            $jsonResponse->setStatus($exception->getStatus());
            $jsonResponse->setOrderId($this->magentoOrderId);
            $jsonResponse->setResult($exception->getResult);
        }

        $this->unblockConcurrency(true);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $jsonResponse->printResponse();
        } else {
            $returnUrl = $this->getRedirectUrl();
            $this->_redirect($returnUrl);
        }
    }

    /**
     * COMMON FUNCTIONS
     */

    private function checkConcurrency()
    {
        $this->getQuoteId();
        $this->checkDbTable();
        $this->unblockConcurrency();
        $this->blockConcurrency();
    }

    private function getMerchantOrder()
    {
        try {
            $this->quote = $this->quoteRepository->get($this->quoteId);
        } catch (\Exception $e) {
            throw new MerchantOrderNotFoundException();
        }
    }

    private function getPmtOrderId()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $query        = "select order_id from $tableName where id='$this->quoteId'";
        $queryResult  = $dbConnection->fetchRow($query);
        $this->pmtOrderId = $queryResult['order_id'];
        if ($this->pmtOrderId == '') {
            throw new NoIdentificationException();
        }
    }

    private function getPmtOrder()
    {
        try {
            $this->orderClient = new Client($this->config['public_key'], $this->config['secret_key']);
            $this->pmtOrder = $this->orderClient->getOrder($this->pmtOrderId);
        } catch (\Exception $e) {
            throw new NoOrderFoundException();
        }
    }

    private function checkOrderStatus()
    {
        try {
            $this->checkPmtStatus(array('AUTHORIZED'));
        } catch (\Exception $e) {
            $this->getMagentoOrderId();
            if ($this->magentoOrderId!='') {
                throw new AlreadyProcessedException();
            } else {
                throw new WrongStatusException($this->pmtOrder->getStatus());
            }
        }
    }

    private function checkMerchantOrderStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->getMagentoOrderId();
            throw new AlreadyProcessedException();
        }
    }

    private function validateAmount()
    {
        $grandTotal = $this->quote->getGrandTotal();
        if ($this->pmtOrder->getShoppingCart()->getTotalAmount() != intval(strval(100 * $grandTotal))) {
            throw new AmountMismatchException(
                $this->pmtOrder->getShoppingCart()->getTotalAmount(),
                intval(strval(100 * $grandTotal))
            );
        }
    }

    private function processMerchantOrder()
    {
        $this->saveOrder();
        $this->updateBdInfo();
    }

    private function confirmPmtOrder()
    {
        try {
            $this->pmtOrder = $this->orderClient->confirmOrder($this->pmtOrderId);
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(200);
        $jsonResponse->setOrderId($this->magentoOrderId);
        $jsonResponse->setResult(self::CPO_OK_MSG);
        return $jsonResponse->toJson();
    }

    /**
     * UTILS FUNCTIONS
     */

    /** STEP 1 CC - Check concurrency */
    /**
     * @throws \Exception
     */
    private function getQuoteId()
    {
        $this->quoteId = $this->getRequest()->getParam('quoteId');
        if ($this->quoteId == '') {
            throw new NoQuoteFoundException();
        }
    }

    /**
     * @return \Zend_Db_Statement_Interface
     */
    private function checkDbTable()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
        $query = "CREATE TABLE IF NOT EXISTS $tableName(`id` int not null,`timestamp` int not null,PRIMARY KEY (`id`))";
        return $dbConnection->query($query);
    }

    /**
     * @return void|\Zend_Db_Statement_Interface
     * @throws \Zend_Db_Exception
     */
    private function checkDbLogTable()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::LOGS_TABLE);
        if (!$dbConnection->isTableExists($tableName)) {
            $table = $dbConnection
                ->newTable($tableName)
                ->addColumn('id', Table::TYPE_SMALLINT, null, array('nullable'=>false, 'auto_increment'=>true, 'primary'=>true))
                ->addColumn('log', Table::TYPE_TEXT, null, array('nullable'=>false))
                ->addColumn('createdAt', Table::TYPE_TIMESTAMP, null, array('nullable'=>false, 'default'=>Table::TIMESTAMP_INIT));
            return $dbConnection->createTable($table);
        }

        return;
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
     * @throws \Exception
     */
    private function blockConcurrency()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            $dbConnection->insert($tableName, array('id'=>$this->quoteId, 'timestamp'=>time()));
        } catch (Exception $exception) {
            throw new ConcurrencyException();
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Pmt OrderId */
    /** STEP 4 GPO - Get Pmt Order */
    /** STEP 5 COS - Check Order Status */
    /**
     * @param $statusArray
     *
     * @throws \Exception
     */
    private function checkPmtStatus($statusArray)
    {
        $pmtStatus = array();
        foreach ($statusArray as $status) {
            $pmtStatus[] = constant("\PagaMasTarde\OrdersApiClient\Model\Order::STATUS_$status");
        }

        $payed = in_array($this->pmtOrder->getStatus(), $pmtStatus);
        if (!$payed) {
            throw new WrongStatusException($this->pmtOrder->getStatus());
        }
    }

    /** STEP 6 CMOS - Check Merchant Order Status */
    /**
     * @throws \Exception
     */
    private function getMagentoOrderId()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $pmtOrderId   = $this->pmtOrderId;

        $query        = "select mg_order_id from $tableName where id='$this->quoteId' and order_id='$pmtOrderId'";
        $queryResult  = $dbConnection->fetchRow($query);
        $this->magentoOrderId = $queryResult['mg_order_id'];
    }

    /** STEP 7 VA - Validate Amount */
    /** STEP 8 PMO - Process Merchant Order */
    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function saveOrder()
    {
        $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
        $this->magentoOrderId = $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);
        /** @var \Magento\Sales\Api\Data\OrderInterface magentoOrder */
        $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);

        if ($this->magentoOrderId == '') {
            throw new UnkownException('Order can not be saved');
        }
    }

    private function updateBdInfo()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $pmtOrderId   = $this->pmtOrder->getId();
        $dbConnection->update(
            $tableName,
            array('mg_order_id'=>$this->magentoOrderId),
            "order_id='$pmtOrderId' and id='$this->quoteId'"
        );
    }

    /** STEP 9 CPO - Confirmation Pmt Order */
    private function rollbackMerchantOrder()
    {
        $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
        $this->magentoOrder->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $this->magentoOrder->save();
    }

    /**
     * @return string
     */
    private function getRedirectUrl()
    {
        $returnUrl = 'checkout/#payment';
        if ($this->magentoOrderId!='') {
            /** @var Order $this->magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);
            if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
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
                if ($this->config['ok_url'] != '') {
                    $returnUrl = $this->config['ok_url'];
                } else {
                    $returnUrl = 'checkout/onepage/success';
                }
            } else {
                if ($this->config['ko_url'] != '') {
                    $returnUrl = $this->config['ko_url'];
                } else {
                    $returnUrl = 'checkout/#payment';
                }
            }
        }
        return $returnUrl;
    }

    /**
     * @param $exceptionMessage
     *
     * @throws \Zend_Db_Exception
     */
    private function insertLog($exceptionMessage)
    {
        if ($exceptionMessage instanceof \Exception) {
            $this->checkDbLogTable();
            $logEntry = new LogEntry($exceptionMessage);

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
            $dbConnection->insert($tableName, array('log' => $logEntry->toJson()));
        }
    }
}
