<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Element\BlockInterface;

class Iframe extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \DigitalOrigin\Pmt\Helper\Config $pmtconfig
    ) {
        $this->_pageFactory = $pageFactory;
        $this->config = $pmtconfig->getConfig();
        return parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $resultPage = $this->_pageFactory->create();
            $orderId = $this->getRequest()->getParam('orderId');
            if ($orderId == '') {
                throw new \Exception('Empty orderId');
            }

            if ($this->config['public_key'] == '' || $this->config['secret_key'] == '') {
                throw new \Exception('Public and Secret Key not found');
            }

            $orderClient = new \PagaMasTarde\OrdersApiClient\Client(
                $this->config['public_key'],
                $this->config['secret_key']
            );

            $order = $orderClient->getOrder($orderId);

            /** @var \DigitalOrigin\Pmt\Block\Payment\Iframe $block */
            $block = $resultPage->getLayout()->getBlock('paylater_payment_iframe');
            $block
                ->setEmail($order->getUser()->getEmail())
                ->setOrderUrl($order->getActionUrls()->getForm())
                ->setCheckoutUrl($order->getConfiguration()->getUrls()->getCancel())
            ;

            return $resultPage;
        } catch (\Exception $exception) {
            $this->logger->info(__METHOD__.'=>'.$exception->getMessage());
            die($exception->getMessage());
        }
    }
}
