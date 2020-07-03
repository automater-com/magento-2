<?php
namespace Automater\Automater\Model;

use Automater\Automater\Helper\Data;

use AutomaterSDK\Client\Client;
use AutomaterSDK\Exception\ApiException;
use AutomaterSDK\Exception\NotFoundException;
use AutomaterSDK\Exception\TooManyRequestsException;
use AutomaterSDK\Exception\UnauthorizedException;
use AutomaterSDK\Request\Entity\TransactionProduct;
use AutomaterSDK\Request\PaymentRequest;
use AutomaterSDK\Request\ProductsRequest;
use AutomaterSDK\Request\TransactionRequest;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

use Psr\Log\LoggerInterface;

class Proxy extends AbstractModel
{
    private $_instance;

    protected $store;
    protected $storeManager;

    protected $logger;

    public function __construct(Data $helper, LoggerInterface $logger, StoreInterface $store, StoreManagerInterface $storeManager)
    {
        $this->helper = $helper;
        $this->logger = $logger;

        $this->store = $store;
        $this->storeManager = $storeManager;
    }

    private function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Client($this->helper->getApiKey(), $this->helper->getApiSecret());
        }

        return $this->_instance;
    }

    public function getCountForProduct($productId)
    {
        try {
            $product = $this->_getInstance()->getProductDetails($productId);

            return (int) $product->getAvailableCodes();
        } catch (UnauthorizedException $exception) {
            $this->logger->critical('Automater: Invalid API key', ['exception' => $exception]);
        } catch (TooManyRequestsException $exception) {
            $this->logger->critical('Automater: Too many requests to Automater: ', ['exception' => $exception]);
        } catch (NotFoundException $exception) {
            $this->logger->critical('Automater: Not found - invalid params', ['exception' => $exception]);
        } catch (ApiException $exception) {
            $this->logger->critical('Automater: ', ['exception' => $exception]);
        }

        return 0;
    }

    public function createTransaction($products, $email, $phone, $label)
    {
        $transactionRequest = new TransactionRequest();

        switch (strtolower(substr($this->store->getLocaleCode(), 0, 2))) {
            case 'pl':
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_PL);
                break;
            case 'en':
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_EN);
                break;
            default:
                $transactionRequest->setLanguage(TransactionRequest::LANGUAGE_EN);
                break;
        }

        if ($email) {
            $transactionRequest->setEmail($email);
            $transactionRequest->setSendStatusEmail(TransactionRequest::SEND_STATUS_EMAIL_TRUE);
        }

        $transactionRequest->setPhone($phone);
        $transactionRequest->setCustom($label);

        $transactionProducts = [];

        foreach ($products as $product_id => $product) {
            $transactionProduct = new TransactionProduct();
            $transactionProduct->setId($product_id);
            $transactionProduct->setQuantity($product['qty']);
            $transactionProduct->setPrice($product['price']);
            $transactionProduct->setCurrency($product['currency']);
            $transactionProducts[] = $transactionProduct;
        }

        $transactionRequest->setProducts($transactionProducts);

        return $this->_getInstance()->createTransaction($transactionRequest);
    }

    public function createPayment($cartId, $paymentId, $amount, $description)
    {
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPaymentId($paymentId);
        $paymentRequest->setCurrency($this->storeManager->getStore()->getCurrentCurrencyCode());
        $paymentRequest->setAmount($amount);
        $paymentRequest->setDescription($description);

        return $this->_getInstance()->postPayment($cartId, $paymentRequest);
    }

    public function getAllProducts()
    {
        $productsResponse = $this->getProducts(1);
        $data = $productsResponse->getData()->toArray();

        for ($page = 2; $page <= $productsResponse->getPagesCount(); $page++) {
            $productsResponse = $this->getProducts($page);
            if ($productsResponse) {
                $data = array_merge($data, $productsResponse->getData()->toArray());
            }
        }

        return $data;
    }

    protected function getProducts($page)
    {
        $client = $this->_getInstance();

        $productRequest = new ProductsRequest();
        $productRequest->setType(ProductsRequest::TYPE_SHOP);
        $productRequest->setStatus(ProductsRequest::STATUS_ACTIVE);
        $productRequest->setPage($page);
        $productRequest->setLimit(100);

        try {
            return $client->getProducts($productRequest);
        } catch (UnauthorizedException $exception) {
            $this->logger->critical('Automater: Invalid API key', ['exception' => $exception]);
        } catch (TooManyRequestsException $exception) {
            $this->logger->critical('Automater: Too many requests to Automater:', ['exception' => $exception]);
        } catch (NotFoundException $exception) {
            $this->logger->critical('Automater: Not found - invalid params', ['exception' => $exception]);
        } catch (ApiException $exception) {
            $this->logger->critical('Automater: ', ['exception' => $exception]);
        }

        return false;
    }
}
