<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

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

    /**
     * Main function
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $response = array();
            $secretKey = $this->getRequest()->getParam('secret');
            $privateKey = isset($this->config['secret_key']) ? $this->config['secret_key'] : null;

            if ($secretKey!='' && $privateKey!='') {
                $this->checkDbLogTable();
                /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
                $dbConnection = $this->dbObject->getConnection();
                $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
                $sql          = $dbConnection
                    ->select()
                    ->from($tableName, array('log', 'createdAt'));

                if ($dateFrom = $this->getRequest()->getParam('from')) {
                    $sql->where('createdAt > ?', $dateFrom);
                }

                if ($dateTo = $this->getRequest()->getParam('to')) {
                    $sql->where('createdAt < ?', $dateTo);
                }

                $limit = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 50;
                $sql->limit($limit);
                $sql->order('createdAt', 'desc');

                $results = $dbConnection->fetchAll($sql);
                if (isset($results) && $privateKey == $secretKey) {
                    foreach ($results as $key => $result) {
                        $response[$key]['timestamp'] = $result['createdAt'];
                        $response[$key]['log']       = json_decode($result['log']);
                    }
                } else {
                    $response['result'] = 'Error';
                }

                $response = json_encode($response);
                header("HTTP/1.1 200", true, 200);
                header('Content-Type: application/json', true);
                header('Content-Length: '.strlen($response));
                echo($response);
                exit();
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @return void|\Zend_Db_Statement_Interface
     * @throws \Zend_Db_Exception
     */
    private function checkDbLogTable()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::LOGS_TABLE);
        if (!$dbConnection->isTableExists($tableName)) {
            $table = $dbConnection
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_SMALLINT,
                    null,
                    array('nullable'=>false, 'auto_increment'=>true, 'primary'=>true)
                )
                ->addColumn('log', Table::TYPE_TEXT, null, array('nullable'=>false))
                ->addColumn(
                    'createdAt',
                    Table::TYPE_TIMESTAMP,
                    null,
                    array('nullable'=>false,
                          'default'=>Table::TIMESTAMP_INIT)
                );
            return $dbConnection->createTable($table);
        }
        return;
    }
}
