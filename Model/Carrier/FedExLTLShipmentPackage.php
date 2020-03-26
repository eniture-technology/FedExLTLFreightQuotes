<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Carrier;

use Eniture\FedExLTLFreightQuotes\Helper\EnConstants;
use Magento\Store\Model\ScopeInterface;

/**
 * Class FedExLTLShipmentPackage
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Carrier
 */
class FedExLTLShipmentPackage
{

    /**
     * @var
     */
    private $httpRequest;
    /**
     * @var
     */
    private $productLoader;
    /**
     * @var
     */
    private $dataHelper;
    /**
     * @var
     */
    private $scopeConfig;
    /**
     * @var
     */
    private $request;

    /**
     * @param $scopeConfig
     * @param $dataHelper
     * @param $productLoader
     * @param $httpRequest
     */
    public function _init(
        $scopeConfig,
        $dataHelper,
        $productLoader,
        $httpRequest
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->dataHelper = $dataHelper;
        $this->productLoader = $productLoader;
        $this->httpRequest = $httpRequest;
    }

    /**
     * function that returns address array
     * @param $request
     * @param $_product
     * @param $receiverZipCode
     * @return array
     */
    public function fedexLTLOriginAddress(
        $request,
        $_product,
        $receiverZipCode
    ) {
        $this->request = $request;
        $whQuery = $this->dataHelper->fetchWarehouseSecData('warehouse');
        $enableDropShip = $_product->getData('en_dropship');

        if ($enableDropShip) {
            $dropShipID = $_product->getData('en_dropship_location');
            $originList = $this->dataHelper->fetchWarehouseWithID('dropship', $dropShipID);
            if (!$originList) {
                $product = $this->productLoader->create()->load($_product->getEntityId());
                $product->setData('en_dropship', 0)->getResource()->saveAttribute($product, 'en_dropship');
                $origin = $whQuery;
            } else {
                $origin = $originList;
            }
        } else {
            $origin = $whQuery;
        }
        if ($origin !== null) {
            return $this->multiWarehouse($origin, $receiverZipCode);
        }
    }

    /**
     * This function returns the closest warehouse if multiple warehouse exists otherwise
     * return single.
     * @param $warehouseList
     * @param $receiverZipCode
     * @return array
     */
    public function multiWarehouse($warehouseList, $receiverZipCode)
    {
        $planNumber = $this->dataHelper->planInfo()['planNumber'];

        if (!empty($warehouseList)) {
            if (count($warehouseList) == 1) {
                $warehouseList = reset($warehouseList);
                return $this->fedexLTLOriginArray($warehouseList, $receiverZipCode, $planNumber);
            } elseif (count($warehouseList) > 1 && ($planNumber == 0 || $planNumber == 1)) {
                return $this->fedexLTLOriginArray($warehouseList[0], $receiverZipCode, $planNumber);
            }

            $response = $this->fedexLTLAddress($warehouseList);
            if (!empty($response)) {
                $originWithMinDist = (isset($response->origin_with_min_dist) && !empty($response->origin_with_min_dist)) ? (array)$response->origin_with_min_dist : [];
                return $this->fedexLTLOriginArray($originWithMinDist, $receiverZipCode, $planNumber);
            }
        }
    }

    /**
     * function that returns shortest origin managed array
     * @param array $shortOrigin
     * @param $receiverZipCode
     * @param $planNumber
     * @return array
     */
    public function fedexLTLOriginArray($shortOrigin, $receiverZipCode, $planNumber)
    {
        if (isset($shortOrigin) && count($shortOrigin) > 1) {
            $origin = isset($shortOrigin['origin']) ? $shortOrigin['origin'] : $shortOrigin;
            $zip = isset($origin['zipcode']) ? $origin['zipcode'] : $origin['zip'];
            $city = $origin['city'];
            $state = $origin['state'];
            $country = ($origin['country'] == "CN") ? "CA" : $origin['country'];
            $location = isset($origin['location']) ? $origin['location'] : 'warehouse';
            $locationId = isset($shortOrigin['id']) ? $shortOrigin['id'] : $shortOrigin['warehouse_id'];
            return [
                'location' => $location,
                'locationId' => $locationId,
                'senderZip' => $zip,
                'senderCity' => $city,
                'senderState' => $state,
                'senderCountryCode' => $country,
                'InstorPickupLocalDelivery' => $planNumber == 3 ? $this->instorePickupLdData($shortOrigin, $receiverZipCode) : '',
            ];
        }
    }

    /**
     * This function returns response from google api
     * @param $originAddress
     * @return array
     */
    public function fedexLTLAddress($originAddress)
    {
        $originAddress = $this->changeWarehouseIdKey($originAddress);
        $post = [
            'acessLevel' => 'MultiDistance',
            'address' => $originAddress,
            'originAddresses' => (isset($originAddress)) ? $originAddress : "",
            'destinationAddress' => [
                'city' => $this->request->getDestCity(),
                'state' => $this->request->getDestRegionCode(),
                'zip' => $this->request->getDestPostcode(),
                'country' => $this->request->getDestCountryId(),
            ],
            'ServerName' => $this->httpRequest->getServer('SERVER_NAME'),
            'eniureLicenceKey' => $this->scopeConfig->getValue('fedexltlconnsettings/first/fedexLtlLicenseKey', ScopeInterface::SCOPE_STORE),
        ];
        $curlRes = $this->dataHelper->sendCurlRequest(EnConstants::GOOGLE_URL, $post);

        if (!isset($curlRes->error)) {
            $response = $curlRes;
        } else {
            $response = [];
        }
        return $response;
    }

    /**
     * @param array $origins
     * @return array
     */
    public function changeWarehouseIdKey($origins)
    {
        $result = [];
        foreach ($origins as $key => $origin) {
            if ($origin['warehouse_id']) {
                $origin['id'] = $origin['warehouse_id'];
                unset($origin['warehouse_id']);
            }
            $result[$key] = $origin;
        }
        return $result;
    }

    /**
     * @param array $shortOrigin
     * @param string $receiverZipCode
     * @return array
     */
    public function instorePickupLdData($shortOrigin, $receiverZipCode)
    {
        $array = [];
        if (!empty($shortOrigin['in_store']) && $shortOrigin['in_store'] != 'null') {
            $inStore = json_decode($shortOrigin['in_store']);
            if ($inStore->enable_store_pickup == 1) {
                $array['inStorePickup'] = [
                    'addressWithInMiles' => $inStore->miles_store_pickup,
                    'postalCodeMatch' => $this->checkPostalCodeMatch($receiverZipCode, $inStore->match_postal_store_pickup),
                ];
            }
        }

        if (!empty($shortOrigin['local_delivery']) && $shortOrigin['local_delivery'] != 'null') {
            $locDel = json_decode($shortOrigin['local_delivery']);
            if ($locDel->enable_local_delivery == 1) {
                $array['localDelivery'] = [
                    'addressWithInMiles' => $locDel->miles_local_delivery,
                    'postalCodeMatch' => $this->checkPostalCodeMatch($receiverZipCode, $locDel->match_postal_local_delivery),
                    'suppressOtherRates' => $locDel->suppress_other,
                ];
            }
        }
        return $array;
    }

    /**
     * @param $receiverZipCode
     * @param $originZipCodes
     * @return bool
     */
    public function checkPostalCodeMatch($receiverZipCode, $originZipCodes)
    {
        $receiverZipCode = preg_replace('/\s+/', '', $receiverZipCode);
        $originZipCodes = preg_replace('/\s+/', '', $originZipCodes);
        return in_array($receiverZipCode, explode(',', $originZipCodes)) ? 1 : 0;
    }
}
