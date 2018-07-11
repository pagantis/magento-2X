<?php

namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Serialize\Serializer\Json;
use PagaMasTarde\OrdersApiClient\Model\Order\User\Address;

define('__ROOT__', dirname((dirname(dirname(__FILE__)))));

/**
 * Class Index
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
     * Index constructor.
     *
     * @param JsonFactory $resultJsonFactory
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     * @param Order       $order
     * @param Session     $session
     * @param Json        $jsonHelper
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        PageFactory $resultPageFactory,
        Order $order,
        Session $session,
        Json $jsonHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->session = $session;
        $this->order = $order;
        $this->jsonFactory = $resultJsonFactory;
        $this->context = $context;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Get Order
     *
     * @return bool|Order
     */
    public function getOrder()
    {
        if ($this->session->getLastRealOrder()) {
            $lastOrderId = $this->session->getLastRealOrder()->getId();
            return $this->order->loadByIncrementId($lastOrderId);
        }

        return false;
    }

    /**
     * You will pay controller
     */
    public function execute()
    {
        try {
            //$checkoutPaymentUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
            $quote = $this->session->getQuote();
            $shippingAddress = $quote->getShippingAddress();
            $billingAddress = $quote->getBillingAddress();
            $orderShippingAddress = new Address();
            $orderShippingAddress
                ->setZipCode($shippingAddress->getPostcode())
                ->setFullName($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname())
                ->setCountryCode('ES')
                ->setCity($shippingAddress->getCity())
                ->setAddress($shippingAddress->getStreetFull())
            ;
            var_dump($billingAddress->getStreetFull());
            die;
            /*$orderBillingAddress = new \PagaMasTarde\OrdersApiClient\Model\Order\User\Address();
            $orderBillingAddress
                ->setZipCode($billingAddress->getPostcode())
                ->setFullName($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname())
                ->setCountryCode('ES')
                ->setCity($billingAddress->getCity())
                ->setAddress($billingAddress->getStreetFull())
            ;*/
            /*$orderUser = new \PagaMasTarde\OrdersApiClient\Model\Order\User();
            $orderUser
                ->setAddress($orderShippingAddress)
                ->setFullName($orderShippingAddress->getFullName())
                ->setBillingAddress($orderBillingAddress)
                ->setDateOfBirth($customer->birthday)
                ->setEmail($this->context->cookie->logged ? $this->context->cookie->email : $customer->email)
                ->setFixPhone($shippingAddress->phone)
                ->setMobilePhone($shippingAddress->phone_mobile)
                ->setShippingAddress($orderShippingAddress)
            ;*/
            //$orders = Order::getCustomerOrders($customer->id);
            /** @var \PrestaShop\PrestaShop\Adapter\Entity\Order $order */
            /*foreach ($orders as $order) {
                if ($order['valid']) {
                    $orderHistory = new \PagaMasTarde\OrdersApiClient\Model\Order\User\OrderHistory();
                    $orderHistory
                        ->setAmount(intval(100 * $order['total_paid']))
                        ->setDate(new \DateTime($order['date_add']))
                    ;
                    $orderUser->addOrderHistory($orderHistory);
                }
            }*/
            /*if (\PagaMasTarde\OrdersApiClient\Model\Order\User::dniCheck($shippingAddress->dni)) {
                $orderUser->setDni($shippingAddress->dni);
            }*/
            /*$details = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart\Details();
            $details->setShippingCost(intval(strval(100 * $cart->getTotalShippingCost())));
            $items = $cart->getProducts();
            foreach ($items as $key => $item) {
                $product = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart\Details\Product();
                $product
                    ->setAmount(intval(100 * $item['price_wt']))
                    ->setQuantity($item['quantity'])
                    ->setDescription($item['name']);
                $details->addProduct($product);
            }*/
            /*$orderShoppingCart = new \PagaMasTarde\OrdersApiClient\Model\Order\ShoppingCart();
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($cart->id)
                ->setPromotedAmount(0)
                ->setTotalAmount(intval(strval(100 * $cart->getOrderTotal(true))))
            ;*/
            /*$orderConfigurationUrls = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Urls();
            $orderConfigurationUrls
                ->setCancel($cancelUrl)
                ->setKo($cancelUrl)
                ->setNotificationCallback($okUrl)
                ->setOk($okUrl)
            ;*/
            /*$orderChannel = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Channel();
            $orderChannel
                ->setAssistedSale(false)
                ->setType(PagaMasTarde\OrdersApiClient\Model\Order\Configuration\Channel::ONLINE)
            ;*/
            /*$orderConfiguration = new \PagaMasTarde\OrdersApiClient\Model\Order\Configuration();
            $orderConfiguration
                ->setChannel($orderChannel)
                ->setUrls($orderConfigurationUrls)
            ;*/
            /*$metadataOrder = new \PagaMasTarde\OrdersApiClient\Model\Order\Metadata();
            foreach ($metadata as $key => $metadatum) {
                $metadataOrder
                    ->addMetadata($key, $metadatum);
            }*/
            /*$order = new \PagaMasTarde\OrdersApiClient\Model\Order();
            $order
                ->setConfiguration($orderConfiguration)
                ->setMetadata($metadataOrder)
                ->setShoppingCart($orderShoppingCart)
                ->setUser($orderUser)
            ;*/
        } catch (\PagaMasTarde\OrdersApiClient\Exception\ValidationException $validationException) {
            die($validationException);
        }
    }
}
