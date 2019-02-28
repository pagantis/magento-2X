<?php

namespace DigitalOrigin\Pmt\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use DigitalOrigin\Pmt\Helper\Config;

class UpgradeData implements UpgradeDataInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'pmt_config';

    /** @var Config */
    public $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //Any version lower than 7.2.0: public_key => pmt_public_key, secret_key => pmt_private_key //TODO
        /*if (version_compare($context->getVersion(), '7.2.0') > 0) {
            print_r($this->config, true);
        }*/

        if (version_compare($context->getVersion(), '7.2.0') < 0) {
            $newConfigs = array(
                /* INSERT NEW CONFIGS PARAMS HERE:config=>'<config>','value'=>'<value>'*/);
            foreach ($newConfigs as $config => $value) {
                $setup->getConnection()->insert(self::CONFIG_TABLE, array('config'=>$config, 'value'=>$value));
            }
        }

        $setup->endSetup();
    }
}