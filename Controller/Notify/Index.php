<?php

namespace Clearpay\Clearpay\Controller\Notify;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Clearpay\Clearpay\Helper\Config;
use Clearpay\Clearpay\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Afterpay\SDK\HTTP\Request as ClearpayRequest;
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as ClearpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\MerchantAccount as ClearpayMerchant;
use Clearpay\Clearpay\Logger\Logger;

/**
 * Class Index
 * @package Clearpay\Clearpay\Controller\Notify
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const CONCURRENCY_TABLE = 'Clearpay_orders';

    /** Payment method */
    const PAYMENT_METHOD = 'clearpay';

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

    /** @var mixed $magentoOrderId */
    protected $magentoOrderId;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var mixed $clearpayOrderId */
    protected $clearpayOrderId;

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

    /** @var Logger $logger */
    protected $logger;

    /** @var string $checkoutError */
    protected $checkoutError;

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
     * @param Logger                   $logger
     *
     * @throws \Exception
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
        RequestInterface $request,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->paymentInterface = $paymentInterface;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dbObject = $dbObject;
        $this->checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->logger = $logger;
        $this->token = $this->_request->getParam('token');
        $this->getQuoteId();

        $this->clearpayMerchantAccount = new ClearpayMerchant();
        $this->countryCode = $this->getClearpayOrderCountryCode();
        $this->clearpayMerchantAccount
            ->setMerchantId($this->config->getMerchantId())
            ->setSecretKey($this->config->getSecretKey())
            ->setApiEnvironment($this->config->getApiEnvironment())
        ;
        if (!is_null($this->countryCode)) {
            $this->clearpayMerchantAccount->setCountryCode($this->countryCode);
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $thrownException = false;
        try {
            $this->getMerchantOrder();
            $this->getClearpayOrderId();
            $this->getClearpayOrder();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
        } catch (\Exception $exception) {
            $thrownException = true;
            $this->logger->info($exception->getMessage());
        }

        try {
            if (!$thrownException) {
                $this->captureClearpayOrder();
            }
        } catch (\Exception $exception) {
            $thrownException = true;
            $this->logger->info($exception->getMessage());
        }

        try {
            if (!$thrownException) {
                $this->processMerchantOrder();
            }
        } catch (\Exception $exception) {
            $this->rollbackMerchantOrder();
            $this->logger->info($exception->getMessage());
        }

        $returnUrl = $this->getRedirectUrl();
        $returnMessage = sprintf(
            "[quoteId=%s][magentoOrderId=%s][clearpayOrderId=%s][returnUrl=%s][captureId=%s][token=%s]",
            $this->quoteId,
            $this->magentoOrderId,
            $this->clearpayOrderId,
            $returnUrl,
            $this->clearpayCapturedPaymentId,
            $this->token
        );
        $this->logger->info($returnMessage);
        if (!empty($this->checkoutError)) {
            $messageManager = $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface');
            $messageManager->addErrorMessage($this->checkoutError);
            $this->checkoutSession->setErrorMessage($this->checkoutError);
        }
        $this->_redirect($returnUrl);
    }

    /**
     * COMMON FUNCTIONS
     */

    /**
     * @throws \Exception
     */
    private function getMerchantOrder()
    {
        try {
            /** @var Quote quote */
            $this->quote = $this->quoteRepository->get($this->quoteId);
        } catch (\Exception $e) {
            throw new \Exception('Merchant Order Not Found');
        }
    }

    /**
     * @throws \Exception
     */
    private function getClearpayOrderId()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection     = $this->dbObject->getConnection();
            $tableName        = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $query            = "select order_id from $tableName where id='$this->quoteId' and token='$this->token'";
            $queryResult      = $dbConnection->fetchRow($query);
            $this->clearpayOrderId = $queryResult['order_id'];
            if ($this->clearpayOrderId == '') {
                throw new \Exception('NoIdentificationException');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function getClearpayOrder()
    {
        try {
            $getOrderRequest = new ClearpayRequest();
            $getOrderRequest
                ->setMerchantAccount($this->clearpayMerchantAccount)
                ->setUri("/v1/orders/" . $this->clearpayOrderId)
            ;
            $getOrderRequest->send();

            if ($getOrderRequest->getResponse()->getHttpStatusCode() >= 400) {
                throw new \Exception("Unable to retrieve order from Clearpay=$this->clearpayOrderId");
            }

            $this->clearpayOrder = $getOrderRequest->getResponse()->getParsedBody();
        } catch (\Exception $e) {
            throw new \Exception('Order not found');
        }
    }

    /**
     * @throws \Exception
     */
    private function checkMerchantOrderStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->getMagentoOrderId();
            throw new \Exception('Already processed');
        }
    }

    /**
     * @throws \Exception
     */
    private function validateAmount()
    {
        $clearpayAmount = $this->clearpayOrder->totalAmount->amount;
        $merchantAmount = $this->quote->getGrandTotal();
        if ($clearpayAmount != $merchantAmount) {
            $this->checkoutError =
            __('We are sorry to inform you that an error ocurred while processing your payment.') .
            __('Thanks for confirming your payment, however as your cart has changed we need a new confirmation') .
            __('Please proceed to Clearpay and retry again in a few minutes.') .
            __('For more information, please contact the Clearpay Customer Service Team: https://clearpay-europe.readme.io/docs/customer-support');
            throw new \Exception("Amount mismatch CP=$clearpayAmount MA=$merchantAmount");
        }
    }

    /**
     * @throws \Exception
     */
    private function processMerchantOrder()
    {
        try {
            $this->saveOrder();
            $this->updateBdInfo();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function captureClearpayOrder()
    {
        try {
            $immediatePaymentCaptureRequest = new ClearpayImmediatePaymentCaptureRequest(array(
                'token' => $this->clearpayOrderId
            ));
            $immediatePaymentCaptureRequest->setMerchantAccount($this->clearpayMerchantAccount);
            $immediatePaymentCaptureRequest->send();

            $this->clearpayCapturedPaymentId =
                isset($immediatePaymentCaptureRequest->getResponse()->getParsedBody()->id) ?
                    $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->id :null;

            if ($immediatePaymentCaptureRequest->getResponse()->getHttpStatusCode() >= 400) {
                $this->checkoutError =
                    __('We are sorry to inform you that your payment has been declined by Clearpay.').
                    __('For more information, please contact the Clearpay Customer Service Team: https://clearpay-europe.readme.io/docs/customer-support').
                    __('For reference, the Order ID for this transaction is:') .
                    $this->clearpayCapturedPaymentId;
                $exception = sprintf(
                    "Clearpay capture payment error, order token:%s ||Error code:%s",
                    $this->clearpayOrderId,
                    $immediatePaymentCaptureRequest->getResponse()->getHttpStatusCode()
                );
                throw new \Exception($exception);
            }

            if (!$immediatePaymentCaptureRequest->getResponse()->isApproved()) {
                $this->checkoutError =
                    __('We are sorry to inform you that your payment has been declined by Clearpay.').
                    __('For more information, please contact the Clearpay Customer Service Team: https ://clearpay-europe.readme.io/docs/customer-support').
                    __('For reference, the Order ID for this transaction is:') .
                    $this->clearpayCapturedPaymentId;
                $exception = sprintf(
                    "Clearpay capture payment error, payment was not proccesed token:%s ||Error code:%s",
                    $this->clearpayOrderId,
                    $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->errorCode
                );
                throw new \Exception($exception);
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * UTILS FUNCTIONS
     */

    /**
     * @throws \Exception
     */
    private function getQuoteId()
    {
        $this->quoteId = $this->getRequest()->getParam('quoteId');
        if ($this->quoteId == '') {
            throw new \Exception('Quote not found');
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Clearpay OrderId */
    /** STEP 4 GPO - Get Clearpay Order */
    /** STEP 5 COS - Check Order Status */
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

            if ($this->clearpayOrderId == '') {
                $this->getClearpayOrderId();
            }
            $clearpayOrderId   = $this->clearpayOrderId;

            $query = sprintf(
                "select mg_order_id from %s where id='%s' and order_id='%s' and token='%s'",
                $tableName,
                $this->quoteId,
                $clearpayOrderId,
                $this->token
            );
            $queryResult  = $dbConnection->fetchRow($query);
            $this->magentoOrderId = $queryResult['mg_order_id'];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function saveOrder()
    {
        try {
            $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
            $this->magentoOrderId = $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);

            /** @var OrderRepositoryInterface magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);

            $comment = sprintf(
                "Token = %s || Capture Payment Id=%s",
                $this->clearpayOrder->token,
                $this->clearpayCapturedPaymentId
            );
            $this->magentoOrder->addStatusHistoryComment($comment)
                               ->setIsCustomerNotified(false)
                               ->setEntityName('order')
                               ->save();

            if ($this->magentoOrderId == '') {
                throw new \Exception('Order can not be saved');
            }
        } catch (\Exception $e) {
            throw new \Exception('saveOrder'.$e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function updateBdInfo()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $dbConnection->update(
                $tableName,
                array('mg_order_id' => $this->magentoOrderId),
                "order_id='".$this->clearpayOrder->token."' and id='$this->quoteId'"
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function rollbackMerchantOrder()
    {
        try {
            $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
            $this->magentoOrder->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $this->magentoOrder->save();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getRedirectUrl()
    {
        $returnUrl = $this->_url->getUrl('checkout/cart');

        try {
            if ($this->clearpayOrderId == '') {
                $this->getClearpayOrderId();
            }

            if ($this->magentoOrderId == '') {
                $this->getMagentoOrderId();
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

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
                if (isset($this->extraConfig['CLEARPAY_OK_URL']) &&  $this->extraConfig['CLEARPAY_OK_URL']!= '') {
                    $returnUrl = $this->extraConfig['CLEARPAY_OK_URL'];
                } else {
                    $returnUrl = 'checkout/onepage/success';
                }
            } else {
                if (isset($this->extraConfig['CLEARPAY_KO_URL']) && $this->extraConfig['CLEARPAY_KO_URL'] != '') {
                    $returnUrl = $this->extraConfig['CLEARPAY_KO_URL'];
                } else {
                    $returnUrl = $this->_url->getUrl('checkout/cart');
                }
            }
        }

        return $returnUrl;
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
