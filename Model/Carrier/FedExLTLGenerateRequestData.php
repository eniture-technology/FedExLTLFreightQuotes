<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

use Magento\Store\Model\ScopeInterface;

/**
 * Class FedExLTLGenerateRequestData
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Carrier
 */
class FedExLTLGenerateRequestData
{
    /**
     * @var Object
     */
    private $registry;
    /**
     * @var
     */
    private $scopeConfig;
    /**
     * @var
     */
    private $moduleManager;
    /**
     * @var
     */
    private $objectManager;
    /**
     * @var
     */
    private $request;
    /**
     * @var string
     */
    private $FedexOneRatePricing = '0';

    /**
     * constructor of class that accepts request object
     * @param $scopeConfig
     * @param $registry
     * @param $moduleManager
     * @param $objectManager
     * @param $httpRequest
     */
    public function _init(
        $scopeConfig,
        $registry,
        $moduleManager,
        $objectManager,
        $httpRequest
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->request = $httpRequest;
    }

    /**
     * @param $request
     * @param $origin
     * @param $objectManager
     * @return array
     */
    public function generateEnitureArray()
    {
        return [
            'licenseKey' => $this->getConfigData('fedexLtlLicenseKey'),
            'serverName' => $this->request->getServer('SERVER_NAME'),
            'carrierMode' => 'pro',
            'quotestType' => 'ltl',
            'version' => '1.1.6',
            //This check is made on Paul/customer request
            'returnQuotesOnExceedWeight' => $this->getConfigData('weightExeeds') > 0 ? 10 : 0,
            'api' => $this->getApiInfoArr(),
        ];
    }

    /**
     * @param $request
     * @param $fedexLTLArr
     * @param $itemsArr
     * @param $cart
     * @return array
     */
    public function generateRequestArray(
        $request,
        $fedexLTLArr,
        $itemsArr,
        $cart
    ) {
        if (count($fedexLTLArr['originAddress']) > 1) {
            foreach ($fedexLTLArr['originAddress'] as $wh) {
                $whIDs[] = $wh['locationId'];
            }
            if (count(array_unique($whIDs)) > 1) {
                foreach ($fedexLTLArr['originAddress'] as $id => $wh) {
                    if (isset($wh['InstorPickupLocalDelivery'])) {
                        $fedexLTLArr['originAddress'][$id]['InstorPickupLocalDelivery'] = [];
                    }
                }
            }
        }
        $carriers = $this->registry->registry('enitureCarriers');

        $carriers['fedexLTL'] = $fedexLTLArr;
        $receiverAddress = $this->getReceiverData($request);
        $autoResidential = $liftGateWithAuto = '0';
        if ($this->autoResidentialDelivery()) {
            $autoResidential = '1';
            $liftGateWithAuto = $this->getConfigData('RADforLiftgate') ? '1' : '0';

            if ($this->registry->registry('radForLiftgate') === null) {
                $this->registry->register('radForLiftgate', $liftGateWithAuto);
            }
        }

        return [
            'apiVersion' => '2.0',
            'platform' => 'magento2',
            'binPackagingMultiCarrier' => $this->binPackSuspend(),

            'autoResidentials' => $autoResidential,
            'liftGateWithAutoResidentials' => $liftGateWithAuto,
            'FedexOneRatePricing' => $this->FedexOneRatePricing,

            'requestKey' => $cart->getQuote()->getId(),
            'carriers' => $carriers,
            'receiverAddress' => $receiverAddress,
            'commdityDetails' => $itemsArr
        ];
    }

    /**
     * @return string
     */
    public function binPackSuspend()
    {
        $return = "0";
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $return = $this->scopeConfig->getValue("binPackaging/suspend/value", ScopeInterface::SCOPE_STORE) == "no" ? "1" : "0";
        }
        return $return;
    }

    /**
     * @return int
     */
    public function autoResidentialDelivery()
    {
        $autoDetectResidential = 0;
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $suspendPath = "resaddressdetection/suspend/value";
            $autoResidential = $this->scopeConfig->getValue($suspendPath, ScopeInterface::SCOPE_STORE);
            if ($autoResidential != null && $autoResidential == 'no') {
                $autoDetectResidential = 1;
            }
        }
        if ($this->registry->registry('autoDetectResidential') === null) {
            $this->registry->register('autoDetectResidential', $autoDetectResidential);
        }

        return $autoDetectResidential;
    }

    /**
     * @return array
     */
    public function getApiInfoArr()
    {
        if ($this->autoResidentialDelivery()) {
            $residential = 'N';
        } else {
            $residential = ($this->getConfigData('residentialDlvry')) ? 'Y' : 'N';
        }
        $liftGate = ($this->getConfigData('liftGateDlvry') || $this->getConfigData('OfferLiftgateAsAnOption')) ? ['LIFTGATE_DELIVERY'] : [];

        $percentDiscount = $this->getConfigData('fedexLtlDiscounts') == 'promotion' ? $this->getConfigData('discountPercent') : '';

        return [
            'key' => $this->getConfigData('fedexLtlAuthenticationKey'),
            'password' => $this->getConfigData('fedexLtlPassword'),
            'AccountNumber' => $this->getConfigData('fedexLtlAccountNumber'),
            'MeterNumber' => $this->getConfigData('fedexLtlMeterNumber'),
            'billingLineAddress' => $this->getConfigData('fedexLtlBillingAddress'),
            'billingCity' => $this->getConfigData('fedexLtlBillingCity'),
            'billingState' => $this->getConfigData('fedexLtlBillingState'),
            'billingZip' => $this->getConfigData('fedexLtlBillingZip'),
            'billingCountry' => $this->getConfigData('fedexLtlBillingCountry'),
            'shippingChargesBy' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
            'shippingChargesAccount' => $this->getConfigData('fedexLtlShipperAccountNumber'),
            'thirdPartyAccount' => $this->getConfigData('fedexLtlThirdPartyAccountNumber'),
            'freightAccountType' => 'SENDER', //   SENDER / THIRD_PARTY
            'accountType' => $this->getConfigData('fedexLtlThirdPartyAccountNumber') !== null ? 'thirdParty' : 'shipper',
            'residentialDelivery' => $residential,
            'holdAtTerminal' => $this->getConfigData('HoldAtTerminal'),
            'prefferedCurrency' => $this->registry->registry('baseCurrency'),
            'percentDiscount' => $percentDiscount,
            'shipmentDate' => date('m/d/Y'),
            'transactionId' => time(),
            'role' => 'SHIPPER',
            'paymentType' => 'PREPAID',
            'collectTermsType' => 'STANDARD',
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '18',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'accessorial' => $liftGate,
        ];
    }

    /**
     * @param $fieldId
     * @return mixed
     */
    public function getConfigData($fieldId)
    {
        $secThreeIds = ['residentialDlvry', 'fedexLtlDiscounts', 'discountPercent', 'liftGateDlvry', 'OfferLiftgateAsAnOption', 'RADforLiftgate', 'HoldAtTerminal', 'weightExeeds'];
        if (in_array($fieldId, $secThreeIds)) {
            $sectionId = 'fedexLtlQuoteSetting';
            $groupId = 'third';
        } else {
            $sectionId = 'fedexltlconnsettings';
            $groupId = 'first';
        }

        return $this->scopeConfig->getValue("$sectionId/$groupId/$fieldId", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $request
     * @return array
     */
    public function getReceiverData($request)
    {
        $addressType = $this->scopeConfig->getValue("resaddressdetection/addressType/value", ScopeInterface::SCOPE_STORE);
        return [
            'addressLine' => $request->getDestStreet(),
            'receiverCity' => $request->getDestCity(),
            'receiverState' => $request->getDestRegionCode(),
            'receiverZip' => preg_replace('/\s+/', '', $request->getDestPostcode()),
            'receiverCountryCode' => $request->getDestCountryId(),
            'defaultRADAddressType' => $addressType ?? 'residential', //get value from RAD
        ];
    }
}
