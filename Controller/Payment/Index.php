<?php

namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

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
     * Index constructor.
     *
     * @param JsonFactory $resultJsonFactory
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(JsonFactory $resultJsonFactory, Context $context, PageFactory $resultPageFactory)
    {
        $this->jsonFactory = $resultJsonFactory;
        $this->context = $context;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * You will pay controller
     */
    public function execute()
    {
        //JSON RESPONSE:
        //return  $this->jsonFactory->create()->setData(['Test-Message' => 'test']);

        //REDIRECT RESPONE:
        //$this->_redirect('checkout/cart');

        //IFRAME RESPONSE:
        return $this->resultPageFactory->create();
    }
}
