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
    const CC_ERR_MSG = 'Unable to block resource';
    const CC_NO_QUOTE = 'QuoteId not found';
    const CC_NO_VALIDATE ='Validation in progress, try again later';
    const GMO_ERR_MSG = 'Merchant Order Not Found';
    const GPOI_ERR_MSG = 'Pmt Order Not Found';
    const GPOI_NO_ORDERID = 'We can not get the PagaMasTarde identification in database.';
    const GPO_ERR_MSG = 'Unable to get Order';
    const COS_ERR_MSG = 'Order status is not authorized';
    const COS_WRONG_STATUS = 'Invalid Pmt status';
    const CMOS_ERR_MSG = 'Merchant Order status is invalid';
    const CMOS_ALREADY_PROCESSED = 'Cart already processed.';
    const VA_ERR_MSG = 'Amount conciliation error';
    const VA_WRONG_AMOUNT = 'Wrong order amount';
    const PMO_ERR_MSG = 'Unknown Error';
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
            $exception = unserialize($exception->getMessage());
            $status = $exception->status;
            $response = array();
            $response['timestamp'] = time();
            $response['order_id']= $this->magentoOrderId;
            $response['result'] = $exception->result;
            $response['result_description'] = $exception->result_description;
            $response = json_encode($response);
        }

        try {
            if (!isset($response)) {
                $response = $this->confirmPmtOrder();
            }
        } catch (\Exception $exception) {
            $this->insertLog($exception);
            $this->rollbackMerchantOrder();
            $exception = unserialize($exception->getMessage());
            $status = $exception->status;
            $response = array();
            $response['timestamp'] = time();
            $response['order_id']= $this->magentoOrderId;
            $response['result'] = self::CPO_ERR_MSG;
            $response['result_description'] = $exception->result_description;
            $response = json_encode($response);
        }

        $this->unblockConcurrency(true);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header("HTTP/1.1 $status", true, $status);
            header('Content-Type: application/json', true);
            header('Content-Length: ' . strlen($response));
            echo ($response);
            exit();
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
        try {
            $this->getQuoteId();
            $this->checkDbTable();
            $this->unblockConcurrency();
            $this->blockConcurrency();
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='429';
            $exceptionObject->result= self::CC_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function getMerchantOrder()
    {
        try {
            $this->quote = $this->quoteRepository->get($this->quoteId);
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='404';
            $exceptionObject->result= self::GMO_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function getPmtOrderId()
    {
        try {
            $this->getPmtOrderIdDb();
            $this->getMagentoOrderId();
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='404';
            $exceptionObject->result= self::GPOI_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function getPmtOrder()
    {
        try {
            $this->orderClient = new Client($this->config['public_key'], $this->config['secret_key']);
            $this->pmtOrder = $this->orderClient->getOrder($this->pmtOrderId);
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='400';
            $exceptionObject->result= self::GPO_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function checkOrderStatus()
    {
        try {
            $this->checkPmtStatus(array('AUTHORIZED'));
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='403';
            $exceptionObject->result= self::COS_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function checkMerchantOrderStatus()
    {
        try {
            $this->checkCartStatus();
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='409';
            $exceptionObject->result= self::CMOS_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function validateAmount()
    {
        try {
            $this->comparePrices();
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='409';
            $exceptionObject->result= self::VA_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function processMerchantOrder()
    {
        try {
            $this->saveOrder();
            $this->updateBdInfo();
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='500';
            $exceptionObject->result= self::PMO_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }
    }

    private function confirmPmtOrder()
    {
        try {
            $this->pmtOrder = $this->orderClient->confirmOrder($this->pmtOrderId);
        } catch (\Exception $e) {
            $exceptionObject = new \stdClass();
            $exceptionObject->method= __FUNCTION__;
            $exceptionObject->status='500';
            $exceptionObject->result= self::CPO_ERR_MSG;
            $exceptionObject->result_description = $e->getMessage();
            throw new \Exception(serialize($exceptionObject));
        }

        $response = array();
        $response['status'] = '200';
        $response['timestamp'] = time();
        $response['order_id']= $this->magentoOrderId;
        $response['result'] = self::CPO_OK_MSG;
        $response = json_encode($response);
        return $response;
    }

    /**
     * UTILS FUNCTIONS
     */

    /** STEP 1 CC - Check concurrency */
    private function getQuoteId()
    {
        $this->quoteId = $this->getRequest()->getParam('quoteId');
        if ($this->quoteId == '') {
            throw new \Exception(self::CC_NO_QUOTE);
        }
    }

    private function checkDbTable()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
        $query = "CREATE TABLE IF NOT EXISTS $tableName(`id` int not null,`timestamp` int not null,PRIMARY KEY (`id`))";
        return $dbConnection->query($query);
    }

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
            throw new \Exception($exception->getMessage());
        }
    }

    private function blockConcurrency()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            $dbConnection->insert($tableName, array('id'=>$this->quoteId, 'timestamp'=>time()));
        } catch (Exception $exception) {
            throw new \Exception(self::CC_NO_VALIDATE);
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Pmt OrderId */
    private function getPmtOrderIdDb()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $query        = "select order_id from $tableName where id='$this->quoteId'";
        $queryResult  = $dbConnection->fetchRow($query);
        $this->pmtOrderId = $queryResult['order_id'];
        if ($this->pmtOrderId == '') {
            throw new \Exception(self::GPOI_NO_ORDERID);
        }
    }

    /** STEP 4 GPO - Get Pmt Order */
    /** STEP 5 COS - Check Order Status */
    private function checkPmtStatus($statusArray)
    {
        $pmtStatus = array();
        foreach ($statusArray as $status) {
            $pmtStatus[] = constant("\PagaMasTarde\OrdersApiClient\Model\Order::STATUS_$status");
        }

        $payed = in_array($this->pmtOrder->getStatus(), $pmtStatus);
        if (!$payed) {
            throw new \Exception(self::CMOS_ERR_MSG."=>".$this->pmtOrder->getStatus());
        }
    }

    /** STEP 6 CMOS - Check Merchant Order Status */
    private function checkCartStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->getMagentoOrderId();
            throw new \Exception(self::CMOS_ALREADY_PROCESSED);
        }
    }

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
    private function comparePrices()
    {
        $grandTotal = $this->quote->getGrandTotal();
        if ($this->pmtOrder->getShoppingCart()->getTotalAmount() != intval(strval(100 * $grandTotal))) {
            throw new \Exception(self::VA_ERR_MSG);
        }
    }
    /** STEP 8 PMO - Process Merchant Order */
    private function saveOrder()
    {
        $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
        $this->magentoOrderId = $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);
        /** @var \Magento\Sales\Api\Data\OrderInterface magentoOrder */
        $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);

        if ($this->magentoOrderId == '') {
            throw new \Exception(self::PMO_ERR_MSG);
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

    private function insertLog($exceptionMessage)
    {
        if ($exceptionMessage instanceof \Exception) {
            $this->checkDbLogTable();
            $logObject          = new \stdClass();
            $logObject->message = $exceptionMessage->getMessage();
            $logObject->code    = $exceptionMessage->getCode();
            $logObject->line    = $exceptionMessage->getLine();
            $logObject->file    = $exceptionMessage->getFile();
            $logObject->trace   = $exceptionMessage->getTraceAsString();

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
            $dbConnection->insert($tableName, array('log' => json_encode($logObject)));
        }
    }
}
