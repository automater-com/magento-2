<?php
namespace Automater\Automater\Cron;

use Automater\Automater\Helper\Data;
use Automater\Automater\Model\Proxy;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;

class Synchronizer
{
    protected $collectionFactory;
    protected $stockItemRepository;

    public function __construct(Data $helper, CollectionFactory $collectionFactory, Proxy $proxy, StockItemRepository $stockItemRepository)
    {
        $this->helper = $helper;
        $this->automater = $proxy;

        $this->collectionFactory  = $collectionFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    public function execute()
    {
        if (!$this->helper->isActive() || !$this->helper->isSynchro()) {
            return;
        }

        $products = $this->_prepareProducts();
        $automaterStocks = $this->_fetchAutomaterStocks();

        foreach ($products as $product) {
            if ($automaterProductId = $product->getAutomaterProductId()) {
                $qty = $automaterStocks[$automaterProductId];

                if ($qty === null) {
                    continue;
                }

                $this->_updateProductStock($product->getId(), $qty);
            }
        }
    }

    private function _prepareProducts()
    {
        $products = $this->collectionFactory->create();

        $products->addAttributeToFilter("automater_product_id", ["notnull" => true]);

        return $products;
    }

    private function _fetchAutomaterStocks()
    {
        $automaterStocks   = [];
        $automaterProducts = $this->automater->getAllProducts();

        foreach ($automaterProducts as $product) {
            $automaterStocks[$product->getId()] = $product->getAvailableCodes();
        }

        return $automaterStocks;
    }

    private function _updateProductStock($productId, $qty)
    {
        $stockItem = $this->stockItemRepository->get($productId);

        if ($stockItem->getId() && $stockItem->getManageStock()) {
            $stockItem->setQty($qty);
            $stockItem->setIsInStock( (bool) ($qty > 0));
            $stockItem->save();
        }
    }
}
