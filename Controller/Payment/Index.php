<?php

namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use ShopperLibrary\ObjectModule\MagentoObjectModule;
use Magento\Framework\Serialize\Serializer\Json;

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
        $checkoutPaymentUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
        $quote = $this->session->getQuote();
        $quoteData = $this->jsonHelper->serialize($quote->getData());
        $quoteItems = json_encode($quote->getAllVisibleItems());
        echo($quoteItems);
        die();

        $magento2ObjectModule = new MagentoObjectModule();
        $magento2ObjectModule
            ->setOrder($order)
            ->setCustomer($customer)
            ->setItems($itemsData)
            ->setAddress($addressData)
            ->setModule($moduleConfig)
            ->setUrl($url)
            ->setMetadata($metadata)
        ;

        //JSON RESPONSE:
        return  $this->jsonFactory->create()->setData($pmtFormUrl);

        //REDIRECT RESPONE:
        //return $this->_redirect('checkout', ['_fragment' => 'payment']);

        //IFRAME RESPONSE:
        //return $this->resultPageFactory->create();
    }
}
