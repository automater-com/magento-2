<?php
namespace Automater\Automater\Model\Attribute\Source;

use Automater\Automater\Helper\Data;
use Automater\Automater\Model\Proxy;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class AutomaterProduct extends AbstractSource
{
    public function __construct(Data $helper, Proxy $automater)
    {
        $this->helper = $helper;
        $this->automater = $automater;
    }

    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [];
            $this->_options[] = [
                'label' => __('-- Please Select --'),
                'value' => '',
            ];
        }

        if (!$this->helper->isActive()) {
            return $this->_options;
        }

        $products = $this->automater->getAllProducts();

        $this->_transformProductsToOptions($products);

        return $this->_options;
    }

    private function _transformProductsToOptions($products)
    {
        foreach ($products as $product) {
            $this->_options[] = [
                'label' => $product->getName(),
                'value' => $product->getId(),
            ];
        }
    }
}
