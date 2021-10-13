<?php
namespace CardknoxDevelopment\Cardknox\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
       
        if (version_compare($context->getVersion(), '2.0.13', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote_payment'),
                'ip_address',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'IP Address'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_payment'),
                'ip_address',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'IP Address'
                ]
            );
        }
        $installer->endSetup();
    }
}
