<?php
namespace Automater\Automater\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'automater_product_id',
            [
                'type' => 'varchar',
                'input' => 'select',
                'source' => 'Automater\Automater\Model\Attribute\Source\AutomaterProduct',
                'label' => 'Product ID in Automater',
                'user_defined' => false,
                'group' => 'General',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'filterable' => false,
                'searchable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'visible_in_advanced_search' => false,
                'used_in_product_listing' => false,
                'is_html_allowed_on_front' => false,
                'required' => false,
            ]
        );
    }
}
