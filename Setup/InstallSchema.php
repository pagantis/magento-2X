<?php

namespace Clearpay\Clearpay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Clearpay_config';

    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Clearpay_logs';

    /** Concurrency tablename */
    const CONCURRENCY_TABLE = 'Clearpay_orders';

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()
                           ->newTable($installer->getTable(self::CONFIG_TABLE))
                           ->addColumn(
                               'id',
                               Table::TYPE_INTEGER,
                               null,
                               ['identity' => true, 'unsigned' => true, 'nullable' =>
                                   false, 'primary' => true],
                               'Entity ID'
                           )
                           ->addColumn('config', Table::TYPE_TEXT, 60, ['nullable' => false])
                           ->addColumn('value', Table::TYPE_TEXT, 1000, ['nullable' => false])
                           ->setComment('Clearpay config table');
        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
                           ->newTable($installer->getTable(self::ORDERS_TABLE))
                           ->addColumn(
                               'id',
                               Table::TYPE_INTEGER,
                               10,
                               array('primary'=>true, 'nullable' => false)
                           )
                           ->addColumn(
                               'order_id',
                               Table::TYPE_TEXT,
                               60,
                               array('primary'=>true, 'nullable' => true)
                           )
                           ->addColumn('mg_order_id', Table::TYPE_TEXT, 50)
                           ->addColumn('token', Table::TYPE_TEXT, 32)
                           ->addColumn('country_code', Table::TYPE_TEXT, 2)
                           ->setComment('Clearpay orders table');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
