<?php

namespace DigitalOrigin\Pmt\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'pmt_config';

    /**
     * Variable which contains extra configuration. If you modify this array, modify it in UpdateData.php too
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
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        foreach ($this->defaultConfigs as $config => $value) {
            $setup->getConnection()->insert(self::CONFIG_TABLE, array('config'=>$config, 'value'=>$value));
        }
    }
}