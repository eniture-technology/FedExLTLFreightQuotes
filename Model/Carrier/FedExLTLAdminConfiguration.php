<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;

/**
 * Class FedExLTLAdminConfiguration
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Carrier
 */
class FedExLTLAdminConfiguration
{

    /**
     * @var object
     */
    private $registry;

    /**
     * @var Object
     */
    private $scopeConfig;

    /**peConfig
     * @param $scopeConfig
     * @param $registry ,
     */
    public function _init(
        $scopeConfig,
        $registry
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->setCarriersAndHelpersCodesGlobally();
        $this->myUniqueLineItemAttribute();
    }

    /**
     * This function set unique Line Item Attributes of carriers
     */
    public function myUniqueLineItemAttribute()
    {
        $lineItemAttArr = [];
        if ($this->registry->registry('UniqueLineItemAttributes') === null) {
            $this->registry->register('UniqueLineItemAttributes', $lineItemAttArr);
        }
    }

    /**
     * This function is for set carriers codes and helpers code globally
     */
    public function setCarriersAndHelpersCodesGlobally()
    {
        $this->setCodesGlobally('enitureCarrierCodes', 'ENFedExLTL');
        $this->setCodesGlobally('enitureCarrierTitle', 'Fedex LTL Freight Quotes');
        $this->setCodesGlobally('enitureHelpersCodes', '\Eniture\FedExLTLFreightQuotes');
        $this->setCodesGlobally('enitureActiveModules', $this->checkModuleIsEnabled());
        $this->setCodesGlobally('enitureModuleTypes', 'ltl');
    }

    /**
     * return if this module is enable or not
     * @return boolean
     */
    public function checkModuleIsEnabled()
    {
        return $this->scopeConfig->getValue("carriers/ENFedExLTL/active", ScopeInterface::SCOPE_STORE);
    }

    /**
     * This function sets Codes Globally e.g carrier code or helper code
     * @param $globArrayName
     * @param $arrValue
     */
    public function setCodesGlobally($globArrayName, $arrValue)
    {
        if ($this->registry->registry($globArrayName) === null) {
            $codesArray = [];
            $codesArray['fedexLTL'] = $arrValue;
            $this->registry->register($globArrayName, $codesArray);
        } else {
            $codesArray = $this->registry->registry($globArrayName);
            $codesArray['fedexLTL'] = $arrValue;
            $this->registry->unregister($globArrayName);
            $this->registry->register($globArrayName, $codesArray);
        }
    }
}
