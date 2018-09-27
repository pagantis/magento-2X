<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\App\ResourceConnection;

class Iframe extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        ResourceConnection $dbObject,
        \DigitalOrigin\Pmt\Helper\Config $pmtconfig
    ) {
        $this->_pageFactory = $pageFactory;
        $this->dbObject = $dbObject;
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
            $this->insertLog($exception);
            die($exception->getMessage());
        }
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
                ->addColumn('createdAt', Table::TYPE_TIMESTAMP, null, array('nullable'=>false, 'default'=>TIMESTAMP_INIT));
            return $dbConnection->createTable($table);
        }

        return;
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
