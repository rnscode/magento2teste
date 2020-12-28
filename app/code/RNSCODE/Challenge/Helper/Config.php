<?php
namespace RNSCODE\Challenge\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    public const XML_PATH_INTEGRATION = 'integration_config/';

    private function getConfigValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId);
    }

    public function isModuleEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigValue(self::XML_PATH_INTEGRATION . 'general/enabled', $storeId);
    }

    public function getAPIKey($storeId = null): string
    {
        return $this->getConfigValue(self::XML_PATH_INTEGRATION . 'general/api_key', $storeId) ?? '';
    }

    public function getEndpoint($storeId = null): string
    {
        return $this->getConfigValue(self::XML_PATH_INTEGRATION . 'general/endpoint_uri', $storeId) ?? '';
    }
}
