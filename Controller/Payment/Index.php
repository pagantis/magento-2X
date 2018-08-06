<?php

namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Serialize\Serializer\Json;
use PagaMasTarde\OrdersApiClient\Model\Order\User\Address;
use DigitalOrigin\Pmt\Helper\Config;
use DigitalOrigin\Pmt\Logger\Logger;

define('__ROOT__', dirname((dirname(dirname(__FILE__)))));

/**
 * Class Iframe
 * @package DigitalOrigin\Pmt\Controller\Payment
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var Json $jsonHelper
     */
    protected $jsonHelper;

    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @var config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Index constructor.
     *
     * @param JsonFactory                     $resultJsonFactory
     * @param Context                         $context
     * @param PageFactory                     $resultPageFactory
     * @param Order                           $order
     * @param Session                         $session
     * @param Json                            $jsonHelper
     * @param Config                          $config
     * @param Logger                          $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        PageFactory $resultPageFactory,
        Order $order,
        Session $session,
        Json $jsonHelper,
        Config $config,
        Logger $logger
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->session = $session;
        $this->order = $order;
        $this->jsonFactory = $resultJsonFactory;
        $this->context = $context;
        $this->config = $config->getConfig();
        $this->logger = $logger;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context);
    }

    /**
     * You will pay controller
     */
    public function execute()
    {
        try {
            $quote = $this->session->getQuote();
            /** @var Order $order */
            $lastOrder = $this->session->getLastRealOrder();
            $customer = $quote->getCustomer();
            $params = $this->getRequest()->getParams();

            $userAddress =  new Address();
            $shippingAddress = $quote->getShippingAddress();
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
            $email ='';
            if (isset($params['email']) && $params['email']!='') {
                $email = $params['email'];
                $this->session->setEmail($email);
            } elseif ($customer->getEmail()!='') {
                $email = $customer->getEmail();
            } else { //TODO
                $email = $this->session->getEmail();
            }
            $billingAddress->setEmail($email);
            $orderUser
                ->setAddress($userAddress)
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setBillingAddress($orderBillingAddress)
                ->setEmail($email)
                ->setFixPhone($shippingAddress->getTelephone())
                ->setMobilePhone($shippingAddress->getTelephone())
                ->setShippingAddress($orderShippingAddress)
            ;

            if ($customer->getDob()) {
                $orderUser->setDateOfBirth($customer->getDob());
            }
            if (\PagaMasTarde\OrdersApiClient\Model\Order\User::dniCheck($customer->getTaxvat())) {
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
            $grandTotal = $quote->collectTotals()->getTotals()['grand_total']->getData('value');
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($quote->getId())
                ->setPromotedAmount(0)
                ->setTotalAmount(intval(strval(100 * $grandTotal)))
            ;

            $orderConfigurationUrls = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Urls();
            $quoteId = $quote->getId();
            $cancelUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
            $okUrl = $this->_url->getUrl('paylater/Notify', ['_query' => ['quoteId'=>$quoteId]]);
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
            $this->logger->info("[ERROR_1]".$validationException->getMessage());
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
            $this->logger->info("[ERROR_2]".$exception->getMessage());
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
     * @return array|mixed
     */
    private function getOrders($customerId)
    {
        $orderCollection = array();
        if ($customerId!='') {
            $orderCollection = $this->objectManager->create('\Magento\Sales\Model\ResourceModel\Order\Collection');
            $orderCollection->addAttributeToFilter('customer_id', $customerId)
                            ->addAttributeToFilter(
                                'status',
                                ['in' => ['processing','pending','complete']]
                            )
                            ->load();
            $orderCollection = $orderCollection->getData();
        }
        return $orderCollection;
    }

    /**
     * @return mixed
     */
    private function checkDbTable()
    {
        $dbConnection = $this->objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection();
        $query = "CREATE TABLE IF NOT EXISTS `mg_pmt_order` ( `id` int, `order_id` int, PRIMARY KEY (`id`, `order_id`))";
        return $dbConnection->query($query);
    }

    /**
     * @param $cartId
     * @param $orderId
     *
     * @return mixed
     */
    private function insertRow($quoteId, $orderId)
    {
        $this->checkDbTable();
        $dbConnection = $this->objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection();
        $query = "INSERT INTO `mg_pmt_order` (`id`, `order_id`) VALUES ('$quoteId','$orderId') 
                          ON DUPLICATE KEY UPDATE `order_id` = '$orderId'";
        return $dbConnection->query($query);
    }

    private function getMetadata()
    {
        $curlInfo = curl_version();
        $curlVersion = $curlInfo['version'];
        $magentoVersion = $this->objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
        $moduleInfo = $this->objectManager->get('Magento\Framework\Module\ModuleList')->getOne('DigitalOrigin_Pmt');
        return array(  'magento' => $magentoVersion,
                       'pmt' => $moduleInfo['setup_version'],
                       'php' => phpversion(),
                       'curl' => $curlVersion);
    }
}
