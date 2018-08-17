<?php

namespace DigitalOrigin\Pmt\Controller\Notify;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use PagaMasTarde\OrdersApiClient\Client;
use DigitalOrigin\Pmt\Logger\Logger;
use DigitalOrigin\Pmt\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session;

//define('__ROOT__', dirname((dirname(dirname(__FILE__)))));

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

    /** Payment code */
    const PAYMENT_METHOD = 'paylater';

    /** @var string  */
    const ALREADY_PROCESSED = 'Cart already processed.';

    /** @var string  */
    const NO_QUOTE = 'QuoteId not found';

    /** @var string  */
    const NO_ORDERID = 'We can not get the PagaMasTarde identification.';

    /** @var string  */
    const WRONG_AMOUNT = 'Wrong order amount';

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

    /** @var Logger $logger */
    protected $logger;

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

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param Quote                    $quote
     * @param Logger                   $logger
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
        Logger $logger,
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
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->paymentInterface = $paymentInterface;
        $this->config = $config->getConfig();
        $this->quoteRepository = $quoteRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dbObject = $dbObject;
        $this->checkoutSession = $checkoutSession;
        $this->notifyResult = array('notification_message'=>'','notification_error'=>true);
    }

    /**
     * Main function
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->logger->info('Notifying via '.$_SERVER['REQUEST_METHOD']);
        try {
            $this->quoteId = $this->getQuoteId();
            if ($this->unblockConcurrency()) {
                if ($this->blockConcurrency()) {
                    $this->validateOrder();
                }
            }
        } catch (\Exception $exception) {
            $this->notifyResult['notification_message'] = $exception->getMessage();
            $this->logger->info(__METHOD__.'=>'.$exception->getMessage());
        }

        $this->unblockConcurrency(true);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $response = json_encode(array(
                'timestamp' => time(),
                'order_id' => $this->magentoOrderId,
                'result' => (!$this->notifyResult['notification_error']) ? 'success' : 'failed',
                'result_description' => $this->notifyResult['notification_message']
            ));
            if ($this->notifyResult['notification_error']) {
                header('HTTP/1.1 400 Bad Request', true, 400);
            } else {
                header('HTTP/1.1 200 Ok', true, 200);
            }
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
     * @return mixed
     * @throws \Exception
     */
    private function getQuoteId()
    {
        if ($this->getRequest()->getParam('quoteId')=='') {
            $this->notifyResult['notification_error'] = false;
            throw new \Exception(self::NO_QUOTE);
        }

        return $this->getRequest()->getParam('quoteId');
    }

    /**
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \PagaMasTarde\OrdersApiClient\Exception\HttpException
     * @throws \PagaMasTarde\OrdersApiClient\Exception\ValidationException
     */
    private function validateOrder()
    {
        $pmtOrderId = $this->getPmtOrderId();
        $orderClient = new Client($this->config['public_key'], $this->config['secret_key']);
        $this->pmtOrder = $orderClient->getOrder($pmtOrderId);
        $this->checkPmtStatus(array('CONFIRMED','AUTHORIZED'));

        $this->quote = $this->quoteRepository->get($this->quoteId);
        $this->checkCartStatus();
        $this->comparePrices();
        $this->magentoOrderId = $this->saveOrder();
        $this->updateBdInfo();
        $this->pmtOrder = $orderClient->confirmOrder($pmtOrderId);
        $this->checkPmtStatus(array('CONFIRMED'));
        $this->notifyResult['notification_error'] = false;

        return;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getPmtOrderId()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $query        = "select order_id from $tableName where id='$this->quoteId'";
        $queryResult  = $dbConnection->fetchRow($query);

        if ($queryResult['order_id'] == '') {
            $this->notifyResult['notification_error'] = false;
            throw new \Exception(self::NO_ORDERID);
        }
        return $queryResult['order_id'];
    }

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
            $this->notifyResult['notification_error'] = true;
            throw new \Exception(self::WRONG_AMOUNT.$this->pmtOrder->getStatus());
        }

        return;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function checkCartStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->magentoOrderId = $this->getMgOrderId();
            $this->notifyResult['notification_error'] = false;
            throw new \Exception(self::ALREADY_PROCESSED);
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    private function comparePrices()
    {
        $grandTotal = $this->quote->getGrandTotal();
        if ($this->pmtOrder->getShoppingCart()->getTotalAmount() != intval(strval(100 * $grandTotal))) {
            $this->notifyResult['notification_error'] = true;
            throw new \Exception(self::WRONG_AMOUNT);
        }
        return;
    }

    /**
     * @return int|mixed
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function saveOrder()
    {
        $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
        return $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);
    }

    /**
     * @return string
     */
    private function getRedirectUrl()
    {
        $returnUrl = 'checkout/#payment';
        if ($this->magentoOrderId!='') {
            /** @var Order $order */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);
            $orderStatus        = strtolower($this->magentoOrder->getStatus());
            //Magento status flow => https://docs.magento.com/m2/ce/user_guide/sales/order-status-workflow.html
            //Order Workflow => https://docs.magento.com/m2/ce/user_guide/sales/order-workflow.html
            $acceptedStatus     = array('processing', 'completed');
            if (in_array($orderStatus, $acceptedStatus)) {
                if ($this->notifyResult['notification_message'] == self::ALREADY_PROCESSED) {
                    $this->checkoutSession->setLastOrderId($this->magentoOrderId);
                    $this->checkoutSession->setLastRealOrderId($this->magentoOrder->getIncrementId());
                    $this->checkoutSession->setLastQuoteId($this->quoteId);
                    $this->checkoutSession->setLastSuccessQuoteId($this->quoteId);
                    $this->checkoutSession->setLastOrderStatus($this->magentoOrder->getStatus());
                }
                if ($this->config['ok_url'] != '') {
                    $returnUrl = $this->config['ok_url'];
                } else {
                    $returnUrl = '/checkout/onepage/success';
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
     * @return mixed
     */
    private function checkDbTable()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
        $query = "CREATE TABLE IF NOT EXISTS $tableName(`id` int not null,`timestamp` int not null,PRIMARY KEY (`id`))";
        $this->logger->info($query);
        return $dbConnection->query($query);
    }

    /**
     * @param bool $mode
     *
     * @return bool
     */
    private function unblockConcurrency($mode = false)
    {
        try {
            $sql='';
            $this->checkDbTable();
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            if ($mode == false) {
                $dbConnection->delete($tableName, "timestamp<".(time() - 5));
            } elseif ($this->quoteId!='') {
                $dbConnection->delete($tableName, "id  = ".$this->quoteId);
            }
        } catch (Exception $exception) {
            $this->logger->info(__METHOD__.'=>'.$exception->getMessage());
            $this->notifyResult['notification_message'] = $exception->getMessage();
            $this->notifyResult['notification_error'] = true;
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function blockConcurrency()
    {
        try {
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            //$sql = "INSERT INTO $tableName VALUE (" . $this->quoteId. "," . time() . ")";
            //$dbConnection->query($sql);
            $dbConnection->insert($tableName, array('id'=>$this->quoteId, 'timestamp'=>time()));
        } catch (\Exception $exception) {
            $this->logger->info(__METHOD__.'=>'.$exception->getMessage());
            $this->notifyResult['notification_message'] = 'Validation in progress, try again later';
            $this->notifyResult['notification_error'] = true;
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function updateBdInfo()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $pmtOrderId   = $this->pmtOrder->getId();
        /*$query        = "update $tableName set `mg_order_id`=$this->magentoOrderId
                         where order_id ='$pmtOrderId' and id='$this->quoteId'";
        $queryResult  = $dbConnection->fetchRow($query);*/
        $dbConnection->update(
            $tableName,
            array('mg_order_id'=>$this->magentoOrderId),
            "order_id='$pmtOrderId' and id='$this->quoteId'"
        );
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getMgOrderId()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $pmtOrderId   = $this->pmtOrder->getId();
        $query        = "select mg_order_id from $tableName where id='$this->quoteId' and order_id='$pmtOrderId'";
        $queryResult  = $dbConnection->fetchRow($query);

        if ($queryResult['mg_order_id']=='') {
            $this->notifyResult['notification_error'] = false;
            throw new \Exception('We can not get the PagaMasTarde identification.');
        }
        return $queryResult['mg_order_id'];
    }
}
