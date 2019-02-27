<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

class Config extends Action
{
    /** Config tablename */
    const CONFIG_TABLE = 'pmt_config';

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
            $response = array('status'=>null);
            $tableName = $this->dbObject->getTableName(self::CONFIG_TABLE);
            $secretKey = $this->getRequest()->getParam('secret');
            $privateKey = isset($this->config['pmt_private_key']) ? $this->config['pmt_private_key'] : null;

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            if ($privateKey != $secretKey) {
                $response['status'] = 401;
                $response['result'] = 'Unauthorized';
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if (count($_POST)) {
                    foreach ($_POST as $config => $value) {
                        if (isset($this->defaultConfigs[$config]) && $response['status']==null) {
                            $dbConnection->update(
                                $tableName,
                                array('value' => $value),
                                "config='$$config'"
                            );
                        } else {
                            $response['status'] = 400;
                            $response['result'] = 'Bad request';
                        }
                    }
                } else {
                    $response['status'] = 422;
                    $response['result'] = 'Empty data';
                }
            }

            if ($response['status']==null) {
                $dbResult = $dbConnection
                    ->select()
                    ->from($tableName, array('config', 'value'));
                foreach ($dbResult as $value) {
                    $formattedResult[$value['config']] = $value['value'];
                }
                $response['result'] = $formattedResult;
            }
            $result = json_encode($response['result']);
            header("HTTP/1.1 ".$response['status'], true, $response['status']);
            header('Content-Type: application/json', true);
            header('Content-Length: '.strlen($result));
            echo($result);
            exit();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
