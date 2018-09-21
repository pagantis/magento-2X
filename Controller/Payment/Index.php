<?php

namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Checkout\Model\Session;
use DigitalOrigin\Pmt\Helper\Config;
use DigitalOrigin\Pmt\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use PagaMasTarde\OrdersApiClient\Model\Order\User\Address;

/**
 * Class Index
 * @package DigitalOrigin\Pmt\Controller\Payment
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** @var Context $context */
    protected $context;

    /** @var QuoteRepository  $quoteRepository */
    protected $quoteRepository;

    /** @var OrderCollection $orderCollection */
    protected $orderCollection;

    /** @var Session $session */
    protected $session;

    /** @var mixed $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var ProductMetadataInterface $productMetadataInterface */
    protected $productMetadataInterface;

    /** @var ModuleList $moduleList */
    protected $moduleList;

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param Session                  $session
     * @param Config                   $config
     * @param Logger                   $logger
     * @param QuoteRepository          $quoteRepository
     * @param OrderCollection          $orderCollection
     * @param ResourceConnection       $dbObject
     * @param ModuleList               $moduleList
     * @param ProductMetadataInterface $productMetadataInterface
     *
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        OrderCollection $orderCollection,
        Session $session,
        Config $config,
        Logger $logger,
        ResourceConnection $dbObject,
        ProductMetadataInterface $productMetadataInterface,
        ModuleList $moduleList
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->context = $context;
        $this->config = $config->getConfig();
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->orderCollection = $orderCollection;
        $this->dbObject = $dbObject;
        $this->moduleList = $moduleList;
        $this->productMetadataInterface = $productMetadataInterface;
    }

    /**
     * Main function
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        try {
            $cancelUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
            $quote = $this->session->getQuote();
            /** @var Order $order */
            $lastOrder = $this->session->getLastRealOrder();
            $params = $this->getRequest()->getParams();
            $customer = $quote->getCustomer();
            $shippingAddress = $quote->getShippingAddress();

            if (isset($params['email']) && $params['email']!='') {
                $this->session->setEmail($params['email']); //Get guest email after refresh page
                $customer->setEmail($params['email']);
                $quote->setCheckoutMethod('guest');
                $quote->getBillingAddress()->setEmail($params['email']);
            } elseif ($customer->getEmail()=='') {
                $customer->setEmail($this->session->getEmail());
                $quote->setCheckoutMethod('guest');
                $quote->getBillingAddress()->setEmail($this->session->getEmail());
            }

            /** @var Quote $currentQuote */
            $currentQuote = $this->quoteRepository->get($quote->getId());
            $currentQuote->setCustomerEmail($customer->getEmail());
            $this->quoteRepository->save($currentQuote);

            $userAddress =  new Address();
            $userAddress
                ->setZipCode($shippingAddress->getPostcode())
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode('ES')
                ->setCity($shippingAddress->getCity())
                ->setAddress($shippingAddress->getStreetFull())
            ;

            $orderShippingAddress = new Address();
            $orderShippingAddress
                ->setZipCode($shippingAddress->getPostcode())
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode('ES')
                ->setCity($shippingAddress->getCity())
                ->setAddress($shippingAddress->getStreetFull())
                ->setFixPhone($shippingAddress->getTelephone())
                ->setMobilePhone($shippingAddress->getTelephone())
            ;

            $orderBillingAddress =  new Address();
            $billingAddress = $quote->getBillingAddress();
            $orderBillingAddress
                ->setZipCode($billingAddress->getPostcode())
                ->setFullName($billingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode('ES')
                ->setCity($billingAddress->getCity())
                ->setAddress($billingAddress->getStreetFull())
                ->setFixPhone($billingAddress->getTelephone())
                ->setMobilePhone($billingAddress->getTelephone())
            ;

            $orderUser = new \PagaMasTarde\OrdersApiClient\Model\Order\User();
            $billingAddress->setEmail($customer->getEmail());
            $orderUser
                ->setAddress($userAddress)
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setBillingAddress($orderBillingAddress)
                ->setEmail($customer->getEmail())
                ->setFixPhone($shippingAddress->getTelephone())
                ->setMobilePhone($shippingAddress->getTelephone())
                ->setShippingAddress($orderShippingAddress)
            ;

            if ($customer->getDob()) {
                $orderUser->setDateOfBirth($customer->getDob());
            }
            if ($customer->getTaxvat()!='') {
                $orderUser->setDni($customer->getTaxvat());
                $orderBillingAddress->setDni($customer->getTaxvat());
                $orderShippingAddress->setDni($customer->getTaxvat());
            }

            $previousOrders = $this->getOrders($customer->getId());
            foreach ($previousOrders as $orderElement) {
                $orderHistory = new \PagaMasTarde\OrdersApiClient\Model\Order\User\OrderHistory();
                $orderHistory
                    ->setAmount(intval(100 * $orderElement['grand_total']))
                    ->setDate(new \DateTime($orderElement['created_at']))
                ;
                $orderUser->addOrderHistory($orderHistory);
            }

            $details = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart\Details();
            $shippingCost = $quote->collectTotals()->getTotals()['shipping']->getData('value');
            $details->setShippingCost(intval(strval(100 * $shippingCost)));
            $items = $quote->getAllVisibleItems();
            foreach ($items as $key => $item) {
                $product = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart\Details\Product();
                $product
                    ->setAmount(intval(100 * $item->getPrice()))
                    ->setQuantity($item->getQty())
                    ->setDescription($item->getName());
                $details->addProduct($product);
            }

            $orderShoppingCart = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart();
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($quote->getId())
                ->setPromotedAmount(0)
                ->setTotalAmount(intval(strval(100 * $quote->getGrandTotal())))
            ;

            $orderConfigurationUrls = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Urls();
            $quoteId = $quote->getId();
            $okUrl = $this->_url->getUrl('paylater/notify', ['_query' => ['quoteId'=>$quoteId]]);
            $orderConfigurationUrls
                ->setCancel($cancelUrl)
                ->setKo($okUrl)
                ->setNotificationCallback($okUrl)
                ->setOk($okUrl)
            ;

            $orderChannel = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Channel();
            $orderChannel
                ->setAssistedSale(false)
                ->setType(\PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Channel::ONLINE)
            ;
            $orderConfiguration = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration();
            $orderConfiguration
                ->setChannel($orderChannel)
                ->setUrls($orderConfigurationUrls)
            ;

            $metadataOrder = new \PagaMasTarde\OrdersApiClient\Model\Order\Metadata();
            $metadata = $this->getMetadata();
            foreach ($metadata as $key => $metadatum) {
                $metadataOrder->addMetadata($key, $metadatum);
            }

            $order = new \PagaMasTarde\OrdersApiClient\Model\Order();
            $order
                ->setConfiguration($orderConfiguration)
                ->setMetadata($metadataOrder)
                ->setShoppingCart($orderShoppingCart)
                ->setUser($orderUser)
            ;
        } catch (\PagaMasTarde\OrdersApiClient\Exception\ValidationException $validationException) {
            $this->logger->info(__METHOD__.'=>'.$validationException->getMessage());
            echo $cancelUrl;
            exit;
        }

        try {
            if ($this->config['public_key']=='' || $this->config['secret_key']=='') {
                throw new \Exception('Public and Secret Key not found');
            }

            $orderClient = new \PagaMasTarde\OrdersApiClient\Client(
                $this->config['public_key'],
                $this->config['secret_key']
            );

            $order = $orderClient->createOrder($order);
            if ($order instanceof \PagaMasTarde\OrdersApiClient\Model\Order) {
                $url = $order->getActionUrls()->getForm();
                $result = $this->insertRow($quote->getId(), $order->getId());
                if (!$result) {
                    throw new \Exception('Unable to save pmt-order-id');
                }
            } else {
                throw new \Exception('Order not created');
            }
        } catch (\Exception $exception) {
            $this->logger->info(__METHOD__.'=>'.$exception->getMessage());
            echo $cancelUrl;
            exit;
        }

        $displayMode = $this->config['display_mode'];
        if (!$displayMode) {
            echo $url;
            exit;
        } else {
            $iframeUrl = $this->_url->getUrl(
                "paylater/Payment/iframe",
                ['_query' => ["orderId"=>$order->getId()]]
            );
            echo $iframeUrl;
            exit;
        }
    }

    /**
     * Get the orders of a customer
     * @param $customerId
     *
     * @return array
     */
    private function getOrders($customerId)
    {
        $orderCollection = array();
        if ($customerId!='') {
            $this->orderCollection->addAttributeToFilter('customer_id', $customerId)
                            ->addAttributeToFilter(
                                'status',
                                ['in' => ['processing','pending','complete']]
                            )
                            ->load();
            $orderCollection = $this->orderCollection->getData();
        }
        return $orderCollection;
    }

    /**
     * @return \Zend_Db_Statement_Interface
     */
    private function checkDbTable()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        $query = "CREATE TABLE IF NOT EXISTS `$tableName` ( `id` int, `order_id` varchar(50), `mg_order_id` varchar(50), 
                  PRIMARY KEY (`id`))";
        return $dbConnection->query($query);
    }

    /**
     * @param $quoteId
     * @param $pmtOrderId
     *
     * @return int
     */
    private function insertRow($quoteId, $pmtOrderId)
    {
        $this->checkDbTable();
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        return $dbConnection->insertOnDuplicate(
            $tableName,
            array('id'=>$quoteId,'order_id'=>$pmtOrderId),
            array('order_id')
        );
    }

    /**
     * @return array
     */
    private function getMetadata()
    {
        $curlInfo = curl_version();
        $curlVersion = $curlInfo['version'];
        $magentoVersion = $this->productMetadataInterface->getVersion();
        $moduleInfo = $this->moduleList->getOne('DigitalOrigin_Pmt');
        return array(  'magento' => $magentoVersion,
                       'pmt' => $moduleInfo['setup_version'],
                       'php' => phpversion(),
                       'curl' => $curlVersion);
    }
}
