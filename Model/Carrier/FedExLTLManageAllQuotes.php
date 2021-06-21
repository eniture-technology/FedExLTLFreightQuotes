<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

/**
 * Class FedExLTLManageAllQuotes
 * @package Eniture\FedExLTLFreightQuotes\Model\Carrier
 */
class FedExLTLManageAllQuotes
{

    /**
     * stores array of quotes
     * @var array
     */
    private $quotes;
    /**
     * @var
     */
    private $scopeConfig;
    /**
     * @var
     */
    private $registry;
    /**
     * @var
     */
    public $session;
    /**
     * @var
     */
    private $objectManager;
    /**
     * @var array
     */
    private $smallPackagesQuotes;
    /**
     * @var array
     */
    private $ltlPackagesQuotes;

    private $odwData;

    private $resiLabel = '';

    /**
     * @param $scopeConfig
     * @param $registry
     * @param $session
     * @param $objectManager
     */
    public function _init(
        $scopeConfig,
        $registry,
        $session,
        $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->session = $session;
        $this->objectManager = $objectManager;
    }

    /**
     * This function accepts all quotes data and sends to its respective module functions to
     * process and return final result array.
     * @param $quotes
     * @return array | bool
     */
    public function getQuotesResultArr($quotes)
    {
        $this->quotes = $quotes;
        $moduleTypesArr = $this->registry->registry('enitureModuleTypes');
        $quotesArr = (array)$this->quotes;
        $quotesArr = $this->removeErrorFromQuotes($quotesArr);

        $this->quotes = $quotesArr;

        $quotesCount = count($quotesArr);

        if ($quotesCount == 1) {
            return $this->fedExGetAllQuotes();
        } elseif ($quotesCount > 1) {
            $smallModulesArr = array_filter($moduleTypesArr, function ($value) {
                return ($value == 'small');
            });

            $ltlModulesArr = array_filter($moduleTypesArr, function ($value) {
                return ($value == 'ltl');
            });

            $this->smallPackagesQuotes = array_intersect_key($quotesArr, $smallModulesArr);
            $this->ltlPackagesQuotes = array_intersect_key($quotesArr, $ltlModulesArr);

            if (count($this->smallPackagesQuotes) == 0 || count($this->ltlPackagesQuotes) == 0) {
                return $this->fedExGetAllQuotes();
            } else {
                $multiShipmentValue = $this->checkMultiPackaging();

                if ($multiShipmentValue == 'all') {
                    return $this->fedExGetAllQuotes();
                } elseif ($multiShipmentValue == 'semi') {
                    $resultQuotes = $this->fedExGetAllQuotes(false, true);
                    return $this->getQuotesForMultiShipment($resultQuotes, $smallModulesArr, $ltlModulesArr);
                } else {
                    $resultQuotesArr = $this->fedExGetAllQuotes(true, true);
                    $smallQuotesArr = array_intersect_key($resultQuotesArr, $smallModulesArr);
                    $ltlQuotesArr = array_intersect_key($resultQuotesArr, $ltlModulesArr);
                    $this->setOdwData($smallQuotesArr, $ltlQuotesArr, []);
                    $minSmallRate = $this->findMinimumSmall($smallQuotesArr);
                    return $this->updateLtlQuotes($ltlQuotesArr, $minSmallRate);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * This function returns final quotes to show
     * @param array $resultQuotes
     * @param array $smallModulesArr
     * @param array $ltlModulesArr
     * @return array
     */
    public function getQuotesForMultiShipment(
        $resultQuotes,
        $smallModulesArr,
        $ltlModulesArr
    ) {
        $smallQuotesArr = array_intersect_key($resultQuotes, $smallModulesArr);
        $ltlQuotesArr = array_intersect_key($resultQuotes, $ltlModulesArr);
        $allLtlQuotesArr = $this->getAllQuotes($ltlQuotesArr);
        $allSmallQuotesArr = $this->getAllQuotes($smallQuotesArr);
        $commonQuotesArr = array_intersect_key($allLtlQuotesArr, $allSmallQuotesArr);
        $minimumCommonArr = $this->getMinimumCommonQuotes($commonQuotesArr, $resultQuotes);
        $this->setOdwData($smallQuotesArr, $ltlQuotesArr, $minimumCommonArr);
        $minimumSmallRate = $this->getMinimumSmallQuotesRate($minimumCommonArr, $smallQuotesArr);
        return $this->getLtlQuoteForMultishipping($minimumCommonArr, $ltlModulesArr, $ltlQuotesArr, $minimumSmallRate);
    }

    /**
     * This function returns Ltl quotes for multi shipping
     * @param array $minimumCommonArr
     * @param $ltlModulesArr
     * @param array $ltlQuotesArr
     * @param float $minimumSmallRate
     * @return array
     */
    public function getLtlQuoteForMultishipping($minimumCommonArr, $ltlModulesArr, $ltlQuotesArr, $minimumSmallRate)
    {
        $ltlQuotesFinalArr = [];

        // if ltl quotes return nothing
        if (!empty($ltlQuotesArr)) {
            foreach ($ltlQuotesArr as $mainKey => $originArr) {
                $ltlRate = $minimumSmallRate;

                foreach ($originArr as $key => $value) {
                    if (!array_key_exists($key, $minimumCommonArr)) {
                        $ltlRate = $ltlRate + $value['rate'];
                    }
                }
                $ltlQuotesFinalArr[$mainKey][] = [
                    'code' => $mainKey.'_Freight',
                    'title' => 'Freight ' . $this->resiLabel,
                    'rate' => $ltlRate
                ];
            }
        } else {
            if ($minimumSmallRate > 0) {
                foreach ($ltlModulesArr as $key => $value) {
                    $ltlQuotesFinalArr[$key][] = [
                        'code' => $key.'_Freight',
                        'title' => 'Freight ' . $this->resiLabel,
                        'rate' => $minimumSmallRate
                    ];
                }
            }
        }
        return $ltlQuotesFinalArr;
    }

    /**
     * This function returns minimum in common quotes
     * @param array $commonQuotesArr
     * @param array $resultQuotes
     * @return array
     */
    public function getMinimumCommonQuotes(
        $commonQuotesArr,
        $resultQuotes
    ) {
        if (!empty($commonQuotesArr)) {
            $minComQuotesArr = $commonQuotesArr;

            foreach ($resultQuotes as $mainKey => $originArr) {
                foreach ($originArr as $key => $value) {
                    if (empty($minComQuotesArr)) {
                        $minComQuotesArr[$key] = $value;
                    }
                    if (array_key_exists($key, $minComQuotesArr)) {
                        if ($value['rate'] == '0') {
                            if (isset($this->quotes[$mainKey][$key]->severity) && $this->quotes[$mainKey][$key]->severity == 'ERROR') {
                                continue;
                            }
                        }
                        if ($value['rate'] < $minComQuotesArr[$key]['rate']) {
                            $minComQuotesArr[$key] = $value;
                        }
                    }
                }
            }
        } else {
            $minComQuotesArr = [];
        }

        return $minComQuotesArr;
    }

    /**
     * This function returns the minimum from common and small quotes
     * @param array $minimumCommonArr
     * @param array $smallQuotes
     * @return float
     */
    public function getMinimumSmallQuotesRate(
        $minimumCommonArr,
        $smallQuotes
    ) {
        $minSmallQuotes = [];
        if (isset($smallQuotes) && !empty($smallQuotes)) {
            foreach ($smallQuotes as $originArr) {
                foreach ($originArr as $key => $value) {
                    if (!array_key_exists($key, $minimumCommonArr)) {
                        if (array_key_exists($key, $minSmallQuotes)) {
                            if ($value['rate'] < $minSmallQuotes[$key]['rate']) {
                                $minSmallQuotes[$key] = $value;
                            }
                        } else {
                            $minSmallQuotes[$key] = $value;
                        }
                    }
                }
            }
        }
        $minSmallQuotesArray = array_merge($minSmallQuotes, $minimumCommonArr);
        return array_sum(array_column($minSmallQuotesArray, 'rate'));
    }

    /**
     * This function put specific array to common array
     * @param array $quotesArr
     * @return array
     */
    public function getAllQuotes($quotesArr)
    {
        $newQuotesArr = [];
        foreach ($quotesArr as $value) {
            foreach ($value as $key => $originArr) {
                $newQuotesArr[$key] = $originArr;
            }
        }
        return $newQuotesArr;
    }

    /**
     * This function returns is quotes have multipackaging or not
     * @return string
     */
    public function checkMultiPackaging()
    {
        $ltlOriginArr = $smallOriginArr = $commonValuesArr = [];
        $multiPackage = 'no';

        foreach ($this->ltlPackagesQuotes as $mainKey => $mainValue) {
            foreach ($mainValue as $key => $value) {
                array_push($ltlOriginArr, $key);
            }
        }

        foreach ($this->smallPackagesQuotes as $mainKey => $mainValue) {
            foreach ($mainValue as $key => $value) {
                array_push($smallOriginArr, $key);
            }
        }

        $commonValuesArr = array_intersect($ltlOriginArr, $smallOriginArr);
        if (!empty($commonValuesArr)) {
            $multiPackage = 'semi';
            if (count($commonValuesArr) == count($ltlOriginArr) && count($commonValuesArr) == count($smallOriginArr)) {
                $multiPackage = 'all';
            }
        }

        return $multiPackage;
    }

    /**
     * This function removes errors from quotes array
     * @param $QuotesArr
     * @return array
     */
    public function removeErrorFromQuotes($QuotesArr)
    {
        $updatedArr = [];

        if (isset($QuotesArr->error) && $QuotesArr->error) {
            $updatedArr = $QuotesArr;
        }
        foreach ($QuotesArr as $mainKey => $mainValue) {
            if (isset($mainValue->severity) && $mainValue->severity == 'ERROR') {
                $updatedArr[$mainKey] = $mainValue;
            }

            if (isset($mainValue->error) && $mainValue->error) {
                $updatedArr[$mainKey] = $mainValue;
            }

            if ((is_object($mainValue) || is_array($mainValue)) && !empty($mainValue)) {
                foreach ($mainValue as $key => $value) {
                    if (isset($value->error) && $value->error == 1 && isset($value->dismissedProduct)) {
                        continue;
                    } elseif (isset($value->severity)
                        && $value->severity == 'ERROR'
                        && isset($value->dismissedProduct)
                    ) {
                        continue;
                    } else {
                        $updatedArr[$mainKey][$key] = $value;
                    }
                }
            }
        }
        return $updatedArr;
    }

    /**
     * This function add small min small quotes value in Ltl quotes
     * @param array $ltlQuotesArr
     * @param int $minSmallRate
     * @return array
     */
    public function updateLtlQuotes($ltlQuotesArr, $minSmallRate)
    {
        $updatedLtlQuotesArr = [];
        if (!empty($ltlQuotesArr)) {
            foreach ($ltlQuotesArr as $moduleKey => $moduleRates) {
                $finalRate = $minSmallRate;
                foreach ($moduleRates as $originKey => $originRates) {
                    $finalRate += $originRates['rate'];
                }
                $updatedLtlQuotesArr[$moduleKey][] = [
                    'code' => $moduleKey . '_Freight',
                    'title' => 'Freight ' . $this->resiLabel,
                    'rate' => $finalRate
                ];
            }
        } else {
            foreach ($this->ltlPackagesQuotes as $key => $value) {
                $updatedLtlQuotesArr[$key][] = [
                    'code' => $key.'_Freight',
                    'title' => 'Freight',
                    'rate' => $minSmallRate
                ];
            }
        }
        return $updatedLtlQuotesArr;
    }

    /**
     * This function finds the minimum rates value from small quotes
     * @param array $smallArr
     * @return int
     */
    public function findMinimumSmall($smallArr)
    {
        $smallArr = reset($smallArr);
        $counter = 1;
        $minimum = '0';
        foreach ($smallArr as $data) {
            if ($counter == 1) {
                $minimum = $data['rate'];
                $counter = 0;
            } else {
                if ($data['rate'] < $minimum) {
                    $minimum = $data['rate'];
                }
            }
        }
        return $minimum;
    }

    /**
     * This function gets quotes result from all active modules
     * @param boolean $getMinimum
     * @param bool $isMultishipment
     * @return array
     */
    public function fedExGetAllQuotes(
        $getMinimum = false,
        $isMultishipment = false
    ) {
        $helpersArr = $this->registry->registry('enitureHelpersCodes');
        $resultArr = [];
        foreach ($this->quotes as $key => $quote) {
            $helperId = $helpersArr[$key];
            $dataHelper = $this->objectManager->get("$helperId\Helper\Data");
            $smPkgResultData = $dataHelper->getQuotesResults($quote, $getMinimum, $isMultishipment, $this->scopeConfig);

            if ($smPkgResultData != false && $smPkgResultData !== null) {
                $resultArr[$key] = $smPkgResultData;
            }
        }
        return $resultArr;
    }

    /**
     * @param array $smallQuotesArr
     * @param array $ltlQuotesArr
     * @param array $minimumCommonArr
     */
    public function setOdwData($smallQuotesArr, $ltlQuotesArr, $minimumCommonArr)
    {
        $smallQuotesArr = $smallQuotesArr ?? [];
        $ltlQuotesArr = $ltlQuotesArr ?? [];
        $allQuotesArr = $smallQuotesArr + $ltlQuotesArr;
        if (!empty($allQuotesArr)) {
            foreach ($allQuotesArr as $module => $moduleQuote) {
                foreach ($moduleQuote as $origin => $data) {
                    $this->odwData[$module][$origin] = $minimumCommonArr[$origin] ?? $data;
                }
            }
        }
        $this->setOrderDetailData();
    }

    /**
     * @info: This function will set order detail widget session for semi case. OWD = Order Detail Widget
     */
    public function setOrderDetailData()
    {
        $this->addQuotesIndex();
        $orderDetail['residentialDelivery'] = 0;
        $setPkgForODWReg = $this->registry->registry('setPackageDataForOrderDetail') ?? [];

        foreach ($this->odwData as $module => $odwDatum) {
            $orderDetail[$module]['residentialDelivery'] = 0;
            $orderDetail[$module]['shipmentData'] = array_replace_recursive($setPkgForODWReg, $odwDatum);
        }
        // set order detail widget data
        $this->session->start();
        $this->session->setSemiOrderDetailSession($orderDetail);
    }

    public function addQuotesIndex()
    {
        $dataArray = [];
        foreach ($this->odwData as $module => $moduleData) {
            foreach ($moduleData as $key => $array) {
                $resi = $array['resi']['residential'] ?? false;
                $this->resiLabel = $array['resi']['label'];
                unset($array['resi']);
                $array['residentialDelivery'] = $resi;
                $dataArray[$key] = ['quotes' => $array];
            }
            $this->odwData[$module] = $dataArray;
        }
    }
}
