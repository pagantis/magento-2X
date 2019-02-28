<?php

namespace DigitalOrigin\Pmt\Helper;

use Magento\Framework\App\ResourceConnection;

class ExtraConfig
{
    /** Config tablename */
    const CONFIG_TABLE = 'pmt_config';

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /**
     * ExtraConfig constructor.
     *
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->dbObject = $resource;
    }

    /**
     * @return array
     */
    public function getExtraConfig()
    {
        $data = array();
        $dbConnection = $this->dbObject->getConnection();
        $result = $dbConnection->fetchAll("select * from ".self::CONFIG_TABLE);
        if (count($result)) {
            foreach ($result as $value) {
                $data[$value['config']] = $value['value'];
            }
        }

        return $data;
    }
}