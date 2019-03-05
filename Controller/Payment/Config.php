<?php
namespace DigitalOrigin\Pmt\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Config extends Action implements CsrfAwareActionInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'pmt_config';

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /**
     * Variable which contains extra configuration.
     * @var array $defaultConfigs
     */
    public $defaultConfigs = array('PMT_TITLE'=>'Instant Financing',
                                   'PMT_SIMULATOR_DISPLAY_TYPE'=>'pmtSDK.simulator.types.SIMPLE',
                                   'PMT_SIMULATOR_DISPLAY_SKIN'=>'pmtSDK.simulator.skins.BLUE',
                                   'PMT_SIMULATOR_DISPLAY_POSITION'=>'hookDisplayProductButtons',
                                   'PMT_SIMULATOR_START_INSTALLMENTS'=>3,
                                   'PMT_SIMULATOR_MAX_INSTALLMENTS'=>12,
                                   'PMT_SIMULATOR_CSS_POSITION_SELECTOR'=>'default',
                                   'PMT_SIMULATOR_DISPLAY_CSS_POSITION'=>'pmtSDK.simulator.positions.INNER',
                                   'PMT_SIMULATOR_CSS_PRICE_SELECTOR'=>'default',
                                   'PMT_SIMULATOR_CSS_QUANTITY_SELECTOR'=>'default',
                                   'PMT_FORM_DISPLAY_TYPE'=>0,
                                   'PMT_DISPLAY_MIN_AMOUNT'=>1,
                                   'PMT_URL_OK'=>'',
                                   'PMT_URL_KO'=>'',
                                   'PMT_TITLE_EXTRA' => 'Paga hasta en 12 cómodas cuotas con Paga+Tarde. Solicitud totalmente 
                            online y sin papeleos,¡y la respuesta es inmediata!'
    );

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
                                "config='$config'"
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

            $formattedResult = array();
            if ($response['status']==null) {
                $dbResult = $dbConnection->fetchAll("select * from $tableName");
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

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
