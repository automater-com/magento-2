<?php
namespace Automater\Automater\Observer;

use Automater\Automater\Helper\Data;
use Automater\Automater\Model\Proxy;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;

use Psr\Log\LoggerInterface;

class CreateTransaction implements ObserverInterface
{
    protected $logger;
    protected $storeManager;
    protected $productFactory;

    private $_automater;

    public function __construct(Data $helper, LoggerInterface $logger, StoreManagerInterface $storeManager, ProductFactory $productFactory, Proxy $automater)
    {
        $this->helper = $helper;
        $this->logger = $logger;

        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;

        $this->_automater = $automater;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isActive()) {
            return;
        }

        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        $items = $order->getAllVisibleItems();

        $result = [];
        $result[] = __("Automater codes:");

        $products = $this->_validateItems($items, $result);

        $this->_createAutomaterTransaction($products, $order, $result);

        $this->_setOrderStatus($result, $order);
    }

    private function _validateItems($items, &$result)
    {
        $products = [];

        foreach ($items as $item) {
            try {
                $automaterProductId = $this->productFactory
                    ->create()
                    ->loadByAttribute('sku', $item->getSku())
                    ->getAutomaterProductId();

                if (!$automaterProductId) {
                    $result[] = __("Product not managed by automater: %s [%s]", $item->getName(), $item->getSku());
                    continue;
                }

                $qty = (int) $item->getQtyOrdered();

                if (is_nan($qty) || $qty <= 0) {
                    $result[] = __("Invalid quantity of product: %s [%s]", $item->getName(), $item->getSku());
                    continue;
                }

                if (!isset($products[$automaterProductId])) {
                    $products[$automaterProductId]['qty'] = 0;
                    $products[$automaterProductId]['price'] = $item->getPrice();
                    $products[$automaterProductId]['currency'] = $this->storeManager->getStore()->getCurrentCurrencyCode();
                }

                $products[$automaterProductId]['qty'] += $qty;

            } catch (Exception $e) {
                $result[] = $e->getMessage() . __(": %s [%s]", $item->getName(), $item->getSku());
            }
        }

        return $products;
    }

    private function _createAutomaterTransaction($products, $order, &$result)
    {
        if (count($products)) {
            $email = $order->getBillingAddress()->getEmail();
            $phone = $order->getBillingAddress()->getTelephone();
            $label = sprintf("Order from %s, id: #%s", $this->storeManager->getStore()->getBaseUrl(), $order->getIncrementId());

            try {
                $response = $this->_automater->createTransaction($products, $email, $phone, $label);

                if ($response && $automaterCartId = $response->getCartId()) {
                    $order->setAutomaterCartId($automaterCartId);
                    $order->save();

                    $result[] = __("Created cart number: %1", $automaterCartId);
                }

            } catch (UnauthorizedException $exception) {
                $this->handleException($result, 'Invalid API key');
            } catch (TooManyRequestsException $e) {
                $this->handleException($result, 'Too many requests to Automater: ' . $e->getMessage());
            } catch (NotFoundException $e) {
                $this->handleException($result, 'Not found - invalid params');
            } catch (ApiException $e) {
                $this->handleException($result, $e->getMessage());
            } catch (Exception $e) {
                $this->handleException($result, $e->getMessage());
            }
        }
    }

    protected function handleException(array &$result, $exceptionMessage)
    {
        $this->logger->critical('Automater: ' . $exceptionMessage);
        $result[] = 'Automater: ' . $exceptionMessage;
    }

    private function _setOrderStatus($status, $order)
    {
        $order->addStatusHistoryComment(implode('<br>', $status));
        $order->save();
    }

}

?>
