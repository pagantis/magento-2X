<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResourceConnection;

class Log extends Action
{
    /** Concurrency tablename */
    const LOGS_TABLE = 'pmt_logs';

    /** @var mixed $config */
    protected $config;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /**
     * Log constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \DigitalOrigin\Pmt\Helper\Config      $pmtConfig
     * @param ResourceConnection                    $dbObject
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \DigitalOrigin\Pmt\Helper\Config $pmtConfig,
        ResourceConnection $dbObject
    ) {
        $this->config = $pmtConfig->getConfig();
        $this->dbObject = $dbObject;
        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $secretKey = $this->getRequest()->getParam('secret');
            $privateKey = isset($this->config['secret_key']) ? $this->config['secret_key'] : null;

            if ($secretKey=='' || $privateKey=='') {
                die('ERROR');
            }
            
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
            $sql = $dbConnection
                ->select()
                ->from($tableName, array('log'));

            $dateFrom = $this->getRequest()->getParam('from');
            if ($dateFrom!='' && strtotime($dateFrom) === false) {
                $sql->where('createdAt > ?', strtotime($dateFrom));
            }

            $dateTo = $this->getRequest()->getParam('to');
            if ($dateTo!='' && strtotime($dateTo) === false) {
                $sql->where('createdAt < ?', strtotime($dateTo));
            }

            $limit = ($this->getRequest()->getParam('limit'))?($this->getRequest()->getParam('limit')):50;
            $sql->limit($limit);

            $result = $dbConnection->fetchAll($sql);

            if (isset($result) && $privateKey == $secretKey) {
                echo '<pre>';
                print_r($result);
                echo '</pre>';
            } else {
                die('ERROR');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
