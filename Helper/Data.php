<?php

namespace Eniture\FedExLTLFreightQuotes\Helper;

use Eniture\FedExLTLFreightQuotes\Model\Carrier\WordToNumberConversion;
use Eniture\FedExLTLFreightQuotes\Model\Interfaces\DataHelperInterface;
use Eniture\FedExLTLFreightQuotes\Model\WarehouseFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\Manager;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;

/**
 * Class Data
 * @package Eniture\FedExLTLFreightQuotes\Helper
 */
class Data extends AbstractHelper implements DataHelperInterface
{
    /**
     * @var Modulemanager Object
     */
    private $moduleManager;
    /**
     * @var Conn Object
     */
    private $connection;
    /**
     * @var Warehouse Table
     */
    private $WHTableName;
    /**
     * @var ship Config Object
     */
    private $shippingConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var SessionManagerInterface
     */
    private $coreSession;
    /**
     * @var
     */
    private $originZip;
    /**
     * @var
     */
    private $residentialDelivery;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var Currency
     */
    private $currenciesModel;
    /**
     * @var WordToNumberConversion
     */
    private $wordToNumberConversion;
    /**
     * @var Country
     */
    private $warehouseFactory;
    /**
     * @var
     */
    private $configSettings;
    /**
     * @var int
     */
    public $canAddWh = 1;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    public $cacheManager;
    /**
     * @var Context
     */
    private $context;

    /**
     * @var bool
     */
    private $isResi = false;

    /**
     * @var string
     */
    private $hoatLabel = '';
    /**
     * @var bool
     */
    private $isMultiShipment = false;

    /**
     * Data constructor.
     * @param Context $context
     * @param Manager $moduleManager
     * @param ResourceConnection $resource
     * @param Config $shippingConfig
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param Currency $currencyModel
     * @param PriceCurrencyInterface $priceCurrency
     * @param Registry $registry
     * @param SessionManagerInterface $coreSession
     * @param WordToNumberConversion $wordToNumberConversion
     * @param WarehouseFactory $warehouseFactory
     * @param Curl $curl
     * @param CacheManager $cacheManager
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        ResourceConnection $resource,
        Config $shippingConfig,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        Currency $currencyModel,
        PriceCurrencyInterface $priceCurrency,
        Registry $registry,
        SessionManagerInterface $coreSession,
        WordToNumberConversion $wordToNumberConversion,
        WarehouseFactory $warehouseFactory,
        Curl $curl,
        CacheManager $cacheManager
    ) {
        $this->moduleManager            = $moduleManager;
        $this->connection               = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->WHTableName              = $resource->getTableName('warehouse');
        $this->shippingConfig           = $shippingConfig;
        $this->storeManager             = $storeManager;
        $this->currencyFactory          = $currencyFactory;
        $this->currenciesModel          = $currencyModel;
        $this->priceCurrency            = $priceCurrency;
        $this->registry                 = $registry;
        $this->coreSession              = $coreSession;
        $this->wordToNumberConversion   = $wordToNumberConversion;
        $this->warehouseFactory         = $warehouseFactory;
        $this->context                  = $context;
        $this->curl                     = $curl;
        $this->registry                 = $registry;
        $this->coreSession              = $coreSession;
        $this->cacheManager             = $cacheManager;
        parent::__construct($context);
    }

    /**
     * =======================================================
     * *********** Warehouse & DropShips Section *************
     * =======================================================
     * */

    /**
     * @param string $location
     * @return array
     */
    public function fetchWarehouseSecData($location)
    {
        $whCollection = $this->warehouseFactory->create()->getCollection()->addFilter('location', ['eq' => $location]);
        return $this->purifyCollectionData($whCollection);
    }

    /**
     * @param $location
     * @param $warehouseId
     * @return array
     */
    public function fetchWarehouseWithID($location, $warehouseId)
    {
        try {
            $whFactory = $this->warehouseFactory->create();
            $dsCollection = $whFactory->getCollection()
                ->addFilter('location', ['eq' => $location])
                ->addFilter('warehouse_id', ['eq' => $warehouseId]);
            return $this->purifyCollectionData($dsCollection);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param $data
     * @param $whereClause
     * @return int
     */
    public function updateWarehouseData($data, $whereClause)
    {
        return $this->connection->update("$this->WHTableName", $data, "$whereClause");
    }

    /**
     * @param $data
     * @param $id
     * @return array
     */
    public function insertWarehouseData($data, $id)
    {
        $insertQry = $this->connection->insert("$this->WHTableName", $data);
        if ($insertQry == 0) {
            $lastId = $id;
        } else {
            $lastId = $this->connection->lastInsertId();
        }
        return ['insertId' => $insertQry, 'lastId' => $lastId];
    }

    /**
     * @param $data
     * @return int
     */
    public function deleteWarehouseSecData($data)
    {
        try {
            $response = $this->connection->delete("$this->WHTableName", $data);
        } catch (\Throwable $e) {
            $response = 0;
        }
        return $response;
    }

    /**
     * Data Array
     * @param $inputData
     * @return array
     */

    public function originArray($inputData)
    {
        $dataArr = [
            'city' => $inputData['city'],
            'state' => $inputData['state'],
            'zip' => $inputData['zip'],
            'country' => $inputData['country'],
            'location' => $inputData['location'],
            'nickname' => $inputData['nickname'] ?? '',
            'in_store' => 'null',
            'local_delivery' => 'null',
        ];
        $plan = $this->planInfo();
        if ($plan['planNumber'] == 3) {
            $suppressOption = ($inputData['ld_sup_rates'] === 'on') ? 1 : 0;
            //if (isset($inputData['instore_enable'])) {
            $pickupDeliveryArr = [
                'enable_store_pickup' => ($inputData['instore_enable'] === 'on') ? 1 : 0,
                'miles_store_pickup' => $inputData['is_within_miles'],
                'match_postal_store_pickup' => $inputData['is_postcode_match'],
                'checkout_desc_store_pickup' => $inputData['is_checkout_descp'],
                'suppress_other' => $suppressOption,
            ];
            $dataArr['in_store'] = json_encode($pickupDeliveryArr);

            //if ($inputData['ld_enable'] === 'on') {
            $localDeliveryArr = [
                'enable_local_delivery' => ($inputData['ld_enable'] === 'on') ? 1 : 0,
                'miles_local_delivery' => $inputData['ld_within_miles'],
                'match_postal_local_delivery' => $inputData['ld_postcode_match'],
                'checkout_desc_local_delivery' => $inputData['ld_checkout_descp'],
                'fee_local_delivery' => $inputData['ld_fee'],
                'suppress_other' => $suppressOption,
            ];
            $dataArr['local_delivery'] = json_encode($localDeliveryArr);
        }
        return $dataArr;
    }

    /**
     *
     * @param array $getWarehouse
     * @param array $validateData
     * @return string
     */
    public function checkUpdateInStorePickupDelivery($getWarehouse, $validateData)
    {
        $update = 'no';
        if (empty($getWarehouse)) {
            return $update;
        }
        $newData = [];
        $oldData = [];
        $getWarehouse = reset($getWarehouse);
        unset($getWarehouse['warehouse_id']);
        unset($getWarehouse['nickname']);
        unset($validateData['nickname']);

        foreach ($getWarehouse as $key => $value) {
            if (empty($value) || $value === null) {
                $newData[$key] = 'empty';
            } else {
                $oldData[$key] = trim($value);
            }
        }

        $whData = array_merge($newData, $oldData);
        $diff1 = array_diff($whData, $validateData);
        $diff2 = array_diff($validateData, $whData);

        if ((is_array($diff1) && !empty($diff1)) || (is_array($diff2) && !empty($diff2))) {
            $update = 'yes';
        }
        return $update;
    }

    /**
     * @param array $quotesArray
     * @param $inStoreLd
     * @return array
     */
    public function inStoreLocalDeliveryQuotes($quotesArray, $inStoreLd)
    {
        $data = $this->registry->registry('shipmentOrigin');
        foreach ($data as $array) {
            $warehouseData = $this->getWarehouseData($array);
            /**
             * Quotes array only to be made empty if Suppress other rates is ON and In-store
             *  Pickup or Local Delivery also carries some quotes. Else if In-store Pickup or
             *  Local Delivery does not have any quotes i.e Postal code or within miles does
             *  not match then the Quotes Array should be returned as it is.
             * */
            if (isset($warehouseData['suppress_other']) && $warehouseData['suppress_other']) {
                if ((isset($inStoreLd->inStorePickup->status) && $inStoreLd->inStorePickup->status == 1) ||
                    (isset($inStoreLd->localDelivery->status) && $inStoreLd->localDelivery->status == 1)
                ) {
                    $quotesArray = [];
                }
            }
            if (isset($inStoreLd->inStorePickup->status) && $inStoreLd->inStorePickup->status == 1) {
                $quotesArray[] = [
                    'code' => 'INSP',
                    'rate' => 0,
                    'transitTime' => '',
                    'title' => $warehouseData['inStoreTitle'],
                ];
            }

            if (isset($inStoreLd->localDelivery->status) && $inStoreLd->localDelivery->status == 1) {
                $quotesArray[] = [
                    'code' => 'LOCDEL',
                    'rate' => $warehouseData['fee_local_delivery'] ?? 0,
                    'transitTime' => '',
                    'title' => $warehouseData['locDelTitle'],
                ];
            }
        }
        return $quotesArray;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getWarehouseData($data)
    {
        $return = [];
        $whCollection = $this->fetchWarehouseWithID($data['location'], $data['locationId']);
        $inStore = json_decode($whCollection[0]['in_store'], true);
        $locDel = json_decode($whCollection[0]['local_delivery'], true);

        if ($inStore) {
            $inStoreTitle = $inStore['checkout_desc_store_pickup'];
            if (empty($inStoreTitle)) {
                $inStoreTitle = "In-store pick up";
            }
            $return['inStoreTitle'] = $inStoreTitle;
            $return['suppress_other'] = $inStore['suppress_other'] == '1' ? true : false;
        }
        if ($locDel) {
            $locDelTitle = $locDel['checkout_desc_local_delivery'];
            if (empty($locDelTitle)) {
                $locDelTitle = "Local delivery";
            }
            $return['locDelTitle'] = $locDelTitle;
            $return['fee_local_delivery'] = $locDel['fee_local_delivery'];
            $return['suppress_other'] = $locDel['suppress_other'] == '1' ? true : false;
        }
        return $return;
    }

    /**
     * =======================================================
     * ***************** Validation Section ******************
     * =======================================================
     * */

    /**
     * @param $whCollection
     * @return array
     */
    public function purifyCollectionData($whCollection)
    {
        $warehouseSecData = [];
        foreach ($whCollection as $wh) {
            $warehouseSecData[] = $wh->getData();
        }
        return $warehouseSecData;
    }

    /**
     * validate Input Post
     * @param $sPostData
     * @return mixed
     */
    public function validatedPostData($sPostData)
    {
        $dataArray = ['city', 'state', 'zip', 'country'];
        $data = [];
        foreach ($sPostData as $key => $tag) {
            $preg = '/[#$%@^&_*!()+=\-\[\]\';,.\/{}|":<>?~\\\\]/';
            $check_characters = (in_array($key, $dataArray)) ? preg_match($preg, $tag) : '';

            if ($check_characters != 1) {
                if ($key === 'city' || $key === 'nickname' || $key === 'in_store' || $key === 'local_delivery') {
                    $data[$key] = $tag;
                } else {
                    $data[$key] = preg_replace('/\s+/', '', $tag);
                }
            } else {
                $data[$key] = 'Error';
            }
        }

        return $data;
    }

    /**
     * @param array $servicesArr
     * @param $hazShipmentArr
     */
    public function setOrderDetailWidgetData(array $servicesArr, $hazShipmentArr)
    {
        $setPkgForOrderDetailReg = $this->registry->registry('setPackageDataForOrderDetail') ?? [];
        $planNumber = $this->planInfo()['planNumber'];

        if ($planNumber > 1 && $setPkgForOrderDetailReg && $hazShipmentArr) {
            foreach ($hazShipmentArr as $origin => $value) {
                foreach ($setPkgForOrderDetailReg[$origin]['item'] as $key => $data) {
                    $setPkgForOrderDetailReg[$origin]['item'][$key]['isHazmatLineItem'] = $value;
                }
            }
        }
        $orderDetail['shipmentData'] = array_replace_recursive($setPkgForOrderDetailReg, $servicesArr);
        // set order detail widget data
        $this->coreSession->start();
        $this->coreSession->setFedExLTLOrderDetailSession($orderDetail);
    }

    /**
     * =======================================================
     * ********* Settings and configuration Section **********
     * =======================================================
     * */

    /**
     * setting properties dynamically
     */
    public function quoteSettingsData()
    {
        $fields = [
            'fedexLtlLabelAs' => 'fedexLtlLabelAs',
            'fedexLtlQuoteServices' => 'fedexLtlQuoteServices',
            'hndlngFee' => 'hndlngFee',
            'symbolicHndlngFee' => 'symbolicHndlngFee',
            'fedexLtlDiscounts' => 'fedexLtlDiscounts',
            'discountPercent' => 'discountPercent',
            'showDlvryEstimate' => 'showDlvryEstimate',
            'residentialDlvry' => 'residentialDlvry',
            'OfferLiftgateAsAnOption' => 'OfferLiftgateAsAnOption',
            'RADforLiftgate' => 'RADforLiftgate',
            'liftGate' => 'liftGateDlvry',
        ];
        foreach ($fields as $key => $field) {
            $this->$key = $this->configSettings[$field] ?? '';
        }
        $this->resiLabel = ' with residential delivery';
        $this->lgLabel = ' with lift gate delivery';
        $this->resiLgLabel = ' with residential delivery and lift gate delivery';
    }

    /**
     * @param $confPath
     * @return mixed
     */
    public function getConfigData($confPath)
    {
        return $this->scopeConfig->getValue($confPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * This function send request and return response
     * $isAssocArray Parameter When TRUE, then returned objects will
     * be converted into associative arrays, otherwise its an object
     * @param string $url
     * @param array $postData
     * @param bool $isAssocArray
     * @return object|array
     */
    public function sendCurlRequest($url, $postData, $isAssocArray = false)
    {
        $fieldString = http_build_query($postData);
        try {
            $this->curl->post($url, $fieldString);
            $output = $this->curl->getBody();
            $result = json_decode($output, $isAssocArray);
        } catch (\Throwable $e) {
            $result = [];
        }
        return $result;
    }

    public function clearCache()
    {
        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
        // or this
        $this->cacheManager->clean($this->cacheManager->getAvailableTypes());
    }

    /**
     * @param $moduleName
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        $moduleManager = $this->context->getModuleManager();
        if ($moduleManager->isEnabled($moduleName)) {
            return true;
        }
        return false;
    }

    /**
     * =======================================================
     * ******************** RAD Section **********************
     * =======================================================
     * */

    /**
     * @param string $resi
     * @return string
     */
    public function getAutoResidentialTitle($resi)
    {
        if ($this->isModuleEnabled('Eniture_ResidentialAddressDetection')) {
            $isRadSuspend = $this->getConfigData("resaddressdetection/suspend/value");
            if ($this->residentialDlvry == "1") {
                $this->residentialDlvry = $isRadSuspend == "no" ? '0' : '1';
            } else {
                $this->residentialDlvry = $isRadSuspend == "no" ? '0' : $this->residentialDlvry;
            }
            if ($this->residentialDlvry == null || $this->residentialDlvry == '0') {
                if ($resi == 'r') {
                    $this->isResi = true;
                }
            }
        }
    }

    /**
     * ==================================================
     * =============Plan Section=========================
     * ==================================================
     * */

    /**
     * @return string
     */
    public function setPlanNotice()
    {
        $planPackage = $this->planInfo();
        if ($planPackage['storeType'] === null) {
            $planPackage = [];
        }
        return $this->displayPlanMessages($planPackage);
    }

    /**
     * @param $planPackage
     * @return Phrase
     */
    public function displayPlanMessages($planPackage)
    {
        $planMsg = __('Eniture - Fedex LTL Freight Quotes plan subscription is inactive. Please activate plan subscription from <a target="_blank" href="https://eniture.com/magento2-fedex-ltl-freight/">here</a>.');
        if (isset($planPackage) && !empty($planPackage)) {
            if ($planPackage['planNumber'] !== null && $planPackage['planNumber'] != '-1') {
                $planMsg = __('Eniture - Fedex LTL Freight Quotes is currently on the ' . $planPackage['planName'] . '. Your plan will expire within ' . $planPackage['expireDays'] . ' days and plan renews on ' . $planPackage['expiryDate'] . '.');
            }
        }
        return $planMsg;
    }

    /**
     * Get Plan detail
     * @return array
     */
    public function planInfo()
    {
        $planData = $this->coreSession->getPlanDetail();
        if ($planData == null) {
            $appData = $this->getConfigData("eniture/" . EnConstants::APP_CODE);
            $plan = $appData["plan"] ?? '-1';
            $storeType = $appData["storetype"] ?? '';
            $expireDays = $appData["expireday"] ?? '';
            $expiryDate = $appData["expiredate"] ?? '';
            $planName = "";
            switch ($plan) {
                case 3:
                    $planName = "Advanced Plan";
                    break;
                case 2:
                    $planName = "Standard Plan";
                    break;
                case 1:
                    $planName = "Basic Plan";
                    break;
                case 0:
                    $planName = "Trial Plan";
                    break;
            }
            $planData = [
                'planNumber' => $plan,
                'planName' => $planName,
                'expireDays' => $expireDays,
                'expiryDate' => $expiryDate,
                'storeType' => $storeType
            ];
            $this->coreSession->setPlanDetail($planData);
        }
        return $planData;
    }

    /**
     * @param null $msg
     * @param bool $type
     * @return array
     */
    public function generateResponse($msg = null, $type = false)
    {
        $defaultError = 'Something went wrong. Please try again!';
        return [
            'error' => ($type == true) ? 1 : 0,
            'msg' => ($msg != null) ? $msg : $defaultError
        ];
    }

    /**
     * @return int
     */
    public function whPlanRestriction()
    {
        $planArr = $this->planInfo();
        $warehouses = $this->fetchWarehouseSecData('warehouse');
        $planNumber = isset($planArr['planNumber']) ? $planArr['planNumber'] : '';
        if ($planNumber < 2 && count($warehouses)) {
            $this->canAddWh = 0;
        }
        return $this->canAddWh;
    }

    /**
     * function to return the Store Base Currency
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * =====================================================
     * =================Hold at terminal====================
     * =====================================================
     * */

    /**
     * @param $servicesArray
     * @param $count
     * @return array
     */
    public function holdAtTerminalResults($servicesArray, $count)
    {
        $distance = $hoAtAddress = '';
        $hoAt = $servicesArray->holdAtTerminalResponse;
        $rate = $this->getHoatRate($hoAt->totalNetCharge);
        if ($rate <= 0) {
            return [];
        }
        $methodCode = $this->isMultiShipment ? 'Freight' : $servicesArray->serviceType;
        $title = $this->getMethodName($methodCode);

        if ($hoAt->distance->Value > 0) {
            $distance = $hoAt->distance->Value . ' miles away |';
        }
        if (is_array($hoAt->address->StreetLines)) {
            foreach ($hoAt->address->StreetLines as $key => $value) {
                $addSpace = '';
                if ($key > 0) {
                    $addSpace = ' ';
                }
                $hoAtAddress .= $addSpace . $value;
            }
        } elseif (is_string($hoAt->address->StreetLines)) {
            $hoAtAddress = $hoAt->address->StreetLines;
        }
        $address = $hoAtAddress . ', ' . $hoAt->address->City . ' ' . $hoAt->address->StateOrProvinceCode . ' ' . $hoAt->address->PostalCode . ' |';

        $tel = 'T: ' . $hoAt->custServicePhoneNbr->PhoneNumber;
        $this->hoatLabel = ' (T)' . ' Hold at terminal  | ' . $distance . $address . $tel;

        return [
            'code' => 'HOAT' . $count . '+T',
            'rate' => $rate,
            'title' => $title . $this->hoatLabel
        ];
    }

    /**
     * @param $shippingRate
     * @return float|int|mixed
     */
    public function getHoatRate($shippingRate)
    {
        $fee = $this->getConfigData("fedexLtlQuoteSetting/third/holdAtTerminalFee");
        if ($fee) {
            $symbol = $this->getConfigData("fedexLtlQuoteSetting/third/holdAtTerminalFeeSymbol");
            if ($symbol != 'flat') {
                $shippingRate += ($shippingRate * $fee) / 100;
            } else {
                $shippingRate += $fee;
            }
        }
        return $shippingRate;
    }

    /**
     * ===========================================================
     * ==================Get Quotes Section=======================
     * ===========================================================
     * */

    /**
     * @param object $quotes
     * @param bool $getMinimum
     * @param bool $isMultiShipmentQuantity (this param will be true when semi case will be executed)
     * @param object $scopeConfig
     * @return array
     *
     * @info: This function will compile all quotes according to the origin.
     * After getting from quotes almost all type of compilation happened in this function
     */
    public function getQuotesResults($quotes, $getMinimum, $isMultiShipmentQuantity, $scopeConfig)
    {
        $this->configSettings = $this->getConfigData('fedexLtlQuoteSetting/third');
        $this->quoteSettingsData();
        $allConfigServices = $this->getAllConfigServicesArray();
        if ($isMultiShipmentQuantity) {
            return $this->getOriginsMinimumQuotes($quotes, $allConfigServices);
        }
        $allQuotes = $odwArr = $hazShipmentArr = [];
        $count = 0;
        $lgQuotes = false;
        $this->isMultiShipment = (count($quotes) > 1) ? true : false;
        foreach ($quotes as $origin => $quote) {
            if (isset($quote->severity)) {
                if (!$this->isMultiShipment && isset($quote->InstorPickupLocalDelivery) && !empty($quote->InstorPickupLocalDelivery)) {
                    return $this->instoreLocalDeliveryQuotes([], $quote->InstorPickupLocalDelivery);
                }
                return [];
            }
            if ($count == 0) { //To be checked only once
                $isRad = $quote->autoResidentialsStatus ?? '';
                $this->getAutoResidentialTitle($isRad);
                $inStoreLdData = $quote->InstorPickupLocalDelivery ?? false;
                unset($quote->InstorPickupLocalDelivery);
                $lgQuotes = ($this->liftGate || $this->OfferLiftgateAsAnOption || ($this->isResi && $this->RADforLiftgate)) ? true : false;
            }

            $originQuotes = [];
            if (isset($quote->q)) {
                if (isset($quote->hazardousStatus)) {
                    $hazShipmentArr[$origin] = $quote->hazardousStatus == 'y' ? 'Y' : 'N';
                }
                $serviceCounter = 0;
                $arraySorting = [];
                foreach ($quote->q as $key => $data) {
                    if (isset($data->serviceType) && in_array($data->serviceType, $allConfigServices)) {
                        $access = $this->getAccessorialCode();
                        $price = $this->calculatePrice($data);
                        $arraySorting[$key] = $price;
                        $transitTime = $data->transitTime ? $this->wordToNumberConversion->wordsToNumber(strtolower(str_replace('_DAYS', '', $data->transitTime))) : '';
                        $title = $this->getTitle($data->serviceType, false, false, $transitTime);
                        $originQuotes[$key]['simple']['code'] = $data->serviceType . $access;
                        $originQuotes[$key]['simple']['rate'] = $price;
                        $originQuotes[$key]['simple']['title'] = $title;
                        if ($lgQuotes) {
                            $lgAccess = $this->getAccessorialCode(true);
                            $lgPrice = $this->calculatePrice($data, true);
                            $lgTitle = $this->getTitle($data->serviceType, true, false, $transitTime);
                            $originQuotes[$key]['liftgate']['code'] = $data->serviceType . $lgAccess;
                            $originQuotes[$key]['liftgate']['rate'] = $lgPrice;
                            $originQuotes[$key]['liftgate']['title'] = $lgTitle;
                        }
                        /**
                         * ========Hold at terminal===========
                         * */
                        if (isset($data->holdAtTerminalResponse) && !empty($data->holdAtTerminalResponse)) {
                            $serviceCounter++;
                            $originQuotes[$key]['hoat'] = $hoAtArray = $this->holdAtTerminalResults($data, $serviceCounter);
                        }
                    }
                }
                if ($this->isMultiShipment) {
                    $originQuotes = $this->getMinimumQuotes($arraySorting, $originQuotes);
                }
            }
            if ($originQuotes !== null) {
                if (count($originQuotes) > 1) {
                    foreach ($originQuotes as $k => $service) {
                        $allQuotes['simple'][] = $service['simple'];
                        ($lgQuotes) ? $allQuotes['liftgate'][] = $service['liftgate'] : null;
                        isset($service['hoat']) ? $allQuotes['hoat'][] = $service['hoat'] : null;
                    }
                } else {
                    $service = reset($originQuotes);
                    $allQuotes['simple'][] = $service['simple'];
                    ($lgQuotes) ? $allQuotes['liftgate'][] = $service['liftgate'] : null;
                    (isset($service['hoat'])) ? $allQuotes['hoat'][] = $service['hoat'] : null;
                }
            }
            if ($this->isMultiShipment) {
                $odwArr[$origin]['quotes'] = $originQuotes;
            }
            $count++;
        }
        $this->setOrderDetailWidgetData($odwArr, $hazShipmentArr);
        $allQuotes = $this->getFinalQuotesArray($allQuotes);
        if (!$this->isMultiShipment && isset($inStoreLdData) && !empty($inStoreLdData)) {
            $allQuotes = $this->instoreLocalDeliveryQuotes($allQuotes, $inStoreLdData);
        }
        return $allQuotes;
    }

    /**
     * @param $rates
     * @param $services
     * @return array
     */
    public function getMinimumQuotes($rates, $services)
    {
        asort($rates);
        $cheapest = array_slice($rates, 0, 1, true);
        return array_intersect_key($services, $cheapest);
    }

    /**
     * @param object $data
     * @param bool $lgOption
     * @param bool $getCost
     * @return float
     *
     * @info: This function will calculate all prices and return price against a specific service
     */
    public function calculatePrice($data, $lgOption = false, $getCost = false)
    {
        $lgCost = $lgOption ? 0 : $this->getLiftgateCost($data, $getCost);
        $basePrice = (float)$data->totalNetCharge->Amount;
        $basePrice = $basePrice - $lgCost;
        $basePrice = $this->calculateHandlingFee($basePrice);
        return $basePrice;
    }

    /**
     * @param $quotes
     * @param bool $getCost
     * @return float
     */
    public function getLiftgateCost($quotes, $getCost = false)
    {
        $lgCost = 0;
        if (!(($this->isResi && $this->RADforLiftgate) || $this->liftGate) || $getCost) {
            if (isset($quotes->surcharges)) {
                foreach ($quotes->surcharges as $key => $surcharge) {
                    if (isset($surcharge->SurchargeType) && $surcharge->SurchargeType == 'LIFTGATE_DELIVERY') {
                         $lgCost = $surcharge->Amount->Amount;
                    }
                }
            }
        }
        return $lgCost;
    }

    /**
     * @param $serviceName
     * @param bool $lgOption
     * @param bool $from
     * @param string $deliveryEstimate
     * @return string
     *
     * @info: This function will compile name of a service and return service name according to the settings enabled.
     */
    public function getTitle($serviceName, $lgOption = false, $from = false, $deliveryEstimate = '')
    {
        $serviceTitle = $this->getMethodName($serviceName);
        if ($this->isMultiShipment && !$from) {
            return $serviceTitle;
        }
        $deliveryEstimateLabel = (!empty($deliveryEstimate) && $this->showDlvryEstimate) ? ' (Estimated transit time of ' . $deliveryEstimate . ' business days)' : '';
        $accessTitle = '';
        if ($lgOption === true || $this->RADforLiftgate) {
            if ($lgOption && $this->liftGate == '0') {
                $accessTitle = $this->isResi ? $this->resiLgLabel : $this->lgLabel;
            }
            if ($this->liftGate == 1 && $this->isResi) {
                $accessTitle = $this->resiLabel;
            }
            if ($this->RADforLiftgate && $this->isResi) {
                $accessTitle = $this->resiLgLabel;
            }
        } elseif ($this->isResi) {
            $accessTitle = $this->resiLabel;
        }
        return $serviceTitle . $accessTitle . $deliveryEstimateLabel;
    }

    /**
     * @param $serviceCode
     * @return string
     */
    public function getMethodName($serviceCode)
    {
        $prepend = isset($this->fedexLtlLabelAs) && $this->fedexLtlLabelAs !== '' ? $this->fedexLtlLabelAs : 'FedEx Freight';
        switch ($serviceCode) {
            case 'FEDEX_FREIGHT_ECONOMY':
                $methodName = ' Economy';
                break;
            case 'FEDEX_FREIGHT_PRIORITY':
                $methodName = ' Priority';
                break;
            default:
                $methodName = preg_replace('/([A-Z])/', ' $1', $serviceCode);
                break;
        }
        return  $prepend . $methodName;
    }

    /**
     * @param bool $lgOption
     * @return string
     *
     * @info: This will return specific code according to the accessorials for appending with the service code.
     */
    public function getAccessorialCode($lgOption = false)
    {
        $access = '';
        if ($this->residentialDlvry == '1' || $this->isResi) {
            $access .= '+R';
        }
        if (($lgOption || $this->liftGate == '1') || ($this->RADforLiftgate && $this->isResi)) {
            $access .= '+LG';
        }

        return $access;
    }

    /**
     * @param $quotes
     * @return array
     *
     * @info: This function will arrange array of quotes according to the accessorials.
     * This function will handle single shipment and multi shipment both for return final array.
     */
    public function getFinalQuotesArray($quotes)
    {
        $holdAtTerminalArr = $quotes['hoat'] ?? [];
        $lfg = $this->liftGate == 1 || ($this->isResi && $this->RADforLiftgate);
        if ($this->isMultiShipment == false) {
            if (isset($quotes['liftgate']) && $this->OfferLiftgateAsAnOption == 1 && ($this->RADforLiftgate == 0 || $this->isResi == 0)) {
                /**
                 * Condition for lift gate as an option
                 * */
                return array_merge($quotes['simple'], $quotes['liftgate'], $holdAtTerminalArr);
            } elseif ($lfg) {
                /**
                 * Condition for Always lift gate and lift gate for residential (Single Shipment)
                 * */
                return array_merge($quotes['liftgate'], $holdAtTerminalArr);
            } else {
                return array_merge($quotes['simple'], $holdAtTerminalArr);
            }
        } elseif ($lfg) {
            /**
             * Condition for always lift gate and lift gate for residential (Multi Shipment)
             * */
            unset($quotes['simple']);
        }
        return $this->organizeQuotesArray($quotes);
    }

    /**
     * @param $quotes
     * @return array
     */
    public function organizeQuotesArray($quotes)
    {
        $quotesArr = [];
        foreach ($quotes as $key => $value) {
            if ($this->isMultiShipment) {
                $rate = 0;
                $code = $title = '';
                $isLiftGate = $key == 'liftgate' ? true : false;
                foreach ($value as $key2 => $data) {
                    $rate += $data['rate'];
                    $code = $data['code'];
                    $title = $key == 'hoat' ? $data['title'] : $this->getTitle('Freight', $isLiftGate, true);
                }
                $quotesArr[] = [
                    'code' => $code,
                    'rate' => $rate,
                    'title' => $title
                ];
            } else {
                $quotesArr[] = reset($value);
            }
        }
        return $quotesArr;
    }

    /**
     * @param $quotes
     * @param $allConfigServices
     * @return array
     */
    public function getOriginsMinimumQuotes($quotes, $allConfigServices)
    {
        $services = $this->fedexCarriersWithTitle();
        $minIndexArr = [];
        $resiArr = ['residential' => false, 'label' => ''];
        foreach ($quotes as $key => $quote) {
            $minInQ = [];
            $counter = 0;
            $isRad = $quote->autoResidentialsStatus ?? '';
            $autoResTitle = $this->getAutoResidentialTitle($isRad);
            if ($this->residentialDlvry == "1" || $autoResTitle != '') {
                $resiArr = ['residential' => true, 'label' => $autoResTitle];
            }
            if (isset($quote->q)) {
                foreach ($quote->q as $serKey => $availSer) {
                    if (isset($availSer->serviceType) && in_array($availSer->serviceType, $allConfigServices)) {
                        $liftGateCharge     = $this->getLiftgateCost($availSer);
                        $rate               = $availSer->totalNetCharge->Amount - $liftGateCharge;
                        $totalCost          = $this->calculateHandlingFee($rate);
                        $currentService = $services[$availSer->serviceType];
                        $currentArray = ['code'=> str_replace('_', '', $availSer->serviceType),
                            'rate' => $totalCost,
                            'title' => $currentService . $autoResTitle,
                            'resi' => $resiArr];
                        if ($counter == 0) {
                            $minInQ = $currentArray;
                        } else {
                            $minInQ = ($currentArray['rate'] < $minInQ['rate'] ? $currentArray : $minInQ);
                        }
                        $counter ++;
                    }
                }
                if ($minInQ['rate'] > 0) {
                    $minIndexArr[$key] = $minInQ;
                }
            }
        }
        return $minIndexArr;
    }

    /**
     * @return array
     */
    public function getAllConfigServicesArray()
    {
        return explode(',', $this->fedexLtlQuoteServices);
    }

    /**
     * fedex carrier codes with title
     * @return array
     */
    public function fedexCarriersWithTitle()
    {
        return [
            'FEDEX_FREIGHT_ECONOMY'     =>'FedEx Freight Economy',
            'FEDEX_FREIGHT_PRIORITY'    => 'FedEx Freight Priority'
        ];
    }

    /**
     * @param $totalPrice
     * @return float|int
     */
    public function calculateHandlingFee($totalPrice)
    {
        $handlingFeeMarkup = $this->hndlngFee;
        $symbolicHandlingFee = $this->symbolicHndlngFee;

        if (strlen($handlingFeeMarkup) > 0) {
            if ($symbolicHandlingFee == '%') {
                $percentVal = $handlingFeeMarkup / 100 * $totalPrice;
                $grandTotal = $percentVal + $totalPrice;
            } else {
                $grandTotal = $handlingFeeMarkup + $totalPrice;
            }
        } else {
            $grandTotal = $totalPrice;
        }
        return $grandTotal;
    }

    /**
     * @return AbstractCarrierInterface[]
     */
    public function getActiveCarriersForENCount()
    {
        return $this->shippingConfig->getActiveCarriers();
    }

    /**
     * @return array
     */
    public function quoteSettingFieldsToRestrict()
    {
        $restriction = [];
        $currentPlan = $this->planInfo()['planNumber'];
        $restricted = [
            'HoldAtTerminal'
        ];
        switch ($currentPlan) {
            case 3:
                break;
            default:
                $restriction = [
                    'advance' => $restricted
                ];
                break;
        }
        return $restriction;
    }
}
