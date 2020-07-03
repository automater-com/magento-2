<?php
namespace Automater\Automater\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $tableName = $installer->getTable('sales_order');

        if ($connection->tableColumnExists($tableName, 'automater_cart_id') === false) {
            $connection->addColumn(
                    $setup->getTable('sales_order'),
                    'automater_cart_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Automater Cart ID'
                    ]
                );
        }

        $installer->endSetup();
    }
}
