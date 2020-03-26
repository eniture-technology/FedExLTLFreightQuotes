<?php
/**
 * FedEx LTL Freight Quotes
 * @package     FedEx LTL Freight Quotes
 * @author      Eniture-Technology
 */

namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

/**
 * Class FedExLTLSetCarriersGlobaly
 *
 * Class for set carriers globally
 */
class FedExLTLSetCarriersGlobaly
{
    /**
     * @var
     */
    public $dataHelper;
    /**
     * @var
     */
    public $registry;

    /**
     * constructor of class
     * @param $dataHelper
     */
    public function _init($dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param $FedExArr
     * @param $registry
     * @return bool
     */
    public function manageCarriersGlobaly(
        $FedExArr,
        $registry
    ) {
        $this->registry = $registry;
        if ($this->registry->registry('enitureCarriers') === null) {
            $enitureCarriersArray = [];
            $enitureCarriersArray['fedexLTL'] = $FedExArr;
            $this->registry->register('enitureCarriers', $enitureCarriersArray);
        } else {
            $carriersArr = $this->registry->registry('enitureCarriers');
            $carriersArr['fedexLTL'] = $FedExArr;
            $this->registry->unregister('enitureCarriers');
            $this->registry->register('enitureCarriers', $carriersArr);
        }

        $activeEnModulesCount = $this->getActiveEnitureModulesCount();
        if (count($this->registry->registry('enitureCarriers')) < $activeEnModulesCount) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * function that return count of active Eniture modules
     * @return int
     */
    public function getActiveEnitureModulesCount()
    {
        $activeModules = array_keys($this->dataHelper->getActiveCarriersForENCount());
        $activeEnModulesArr = array_filter($activeModules, function ($moduleName) {
            if (substr($moduleName, 0, 2) == 'EN') {
                return true;
            }
            return false;
        });
        return count($activeEnModulesArr);
    }

    /**
     * This function accepts all quotes data and sends to its respective module functions to
     * process and return final result array.
     * @param $quotes
     * @return array
     */
    public function manageQuotes($quotes)
    {
        $helpersArr = $this->registry->registry('enitureHelpersCodes');
        $resultArr = [];
        foreach ($quotes as $key => $quote) {
            $helperId = $helpersArr[$key];
            $FedExResultData = $this->registry->helper($helperId)->getQuotesResults($quote);
            if ($FedExResultData != false) {
                $resultArr[$key] = $FedExResultData;
            }
        }
        return $resultArr;
    }
}
