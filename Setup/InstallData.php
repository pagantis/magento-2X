<?php

namespace Clearpay\Clearpay\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Clearpay_config';

    /**
     * Variable which contains extra configuration. If you modify this array, modify it in UpdateData.php too
     * @var array $defaultConfigs
     */
    public $defaultConfigs = array(
       'CLEARPAY_TITLE'=>'Or 4 interest-free payments of %s with ',
       'CLEARPAY_TITLE_EXTRA' => 'Instant approval decision - 4 interest-free payments of %s',
       'CLEARPAY_TITLE_MOREINFO_1' => 'You will be redirected to Clearpay website to fill out your payment information.',
       'CLEARPAY_TITLE_MOREINFO_2' => 'You will be redirected to our site to complete your order.',
       'CLEARPAY_TITLE_MOREINFO_3' => 'Please note: Clearpay can only be used as a payment method for orders with a shipping and billing address within the UK.',
       'CLEARPAY_URL_OK'=>'',
       'CLEARPAY_URL_KO'=>'',
       'CLEARPAY_ALLOWED_COUNTRIES' => 'a:3:{i:0;s:2:"es";i:1;s:2:"it";i:2;s:2:"fr";}',
       'CLEARPAY_AMOUNT_SELECTOR' => 'div.product-info-price span.price',
       'CLEARPAY_ONCLICK_SELECTOR' => '.product-options-wrapper'
    );

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $prefixedTableName = $setup->getConnection()->getTableName(self::CONFIG_TABLE);
        if ($setup->tableExists($prefixedTableName)) {
            foreach ($this->defaultConfigs as $config => $value) {
                $setup->getConnection()
                      ->insert($prefixedTableName, array('config' => $config, 'value' => $value));
            }
        }
    }
}