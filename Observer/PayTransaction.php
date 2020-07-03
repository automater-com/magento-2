<?php
namespace Automater\Automater\Observer;

use Automater\Automater\Helper\Data;
use Automater\Automater\Model\Proxy;

use Psr\Log\LoggerInterface;

use Magento\Framework\Event\ObserverInterface;

class PayTransaction implements ObserverInterface
{
    private $_automater;

    protected $logger;

    public function __construct(Data $helper, Proxy $automater, LoggerInterface $logger)
    {
        $this->helper = $helper;
        $this->logger = $logger;

        $this->_automater = $automater;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isActive()) {
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        $result = [];
        $result[] = __("Automater payment:");

        $automaterCartId = $order->getAutomaterCartId();

        if (!$automaterCartId) {
            return;
        }

        $paymentId = $order->getIncrementId();
        $amount = $order->getSubtotal();
        $description = $order->getPayment()->getMethodInstance()->getTitle();

        try {
            $response = $this->_automater->createPayment($automaterCartId, $paymentId, $amount, $description);

            if ($response) {
                $result[] = __("Paid successfully: %s", $automaterCartId);
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

        $this->_setOrderStatus($result, $order);
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
