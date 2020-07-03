<?php
namespace Automater\Automater\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_ACTIVE = 'automater/api_configuration/active';
    const XML_PATH_SYNCHRO = 'automater/api_configuration/synchro';
    const XML_PATH_API_KEY = 'automater/api_configuration/api_key';
    const XML_PATH_API_SECRET = 'automater/api_configuration/api_secret';

    public function isActive()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function isSynchro()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SYNCHRO, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiSecret()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
