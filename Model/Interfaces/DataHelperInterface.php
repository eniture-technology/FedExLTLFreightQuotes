<?php
/*
 * Dated: 03 Fab. 2020
 * This interface is defined for Data Helper class.
 * Author: Eniture
 * */

namespace Eniture\FedExLTLFreightQuotes\Model\Interfaces;

interface DataHelperInterface
{
    /**
     * =======================================================
     * *********** Warehouse & DropShips Section *************
     * =======================================================
     * */

    /**
     * @param $location
     * @return array
     */
    public function fetchWarehouseSecData($location);

    /**
     * @param $location
     * @param $warehouseId
     * @return array
     */
    public function fetchWarehouseWithID($location, $warehouseId);

    /**
     * @param $data
     * @param $whereClause
     * @return bool
     */
    public function updateWarehouseData($data, $whereClause);

    /**
     * @param $data
     * @param $id
     * @return array
     */
    public function insertWarehouseData($data, $id);

    /**
     * @param $data
     * @return bool
     */
    public function deleteWarehouseSecData($data);

    /**
     * @param $inputData
     * @return array
     */
    public function originArray($inputData);

    /**
     * @param $getWarehouse
     * @param $validateData
     * @return string
     */
    public function checkUpdateInStorePickupDelivery($getWarehouse, $validateData);

    /**
     * @param $quotesArray
     * @param $inStoreLd
     * @return mixed
     */
    public function inStoreLocalDeliveryQuotes($quotesArray, $inStoreLd);

    /**
     * @param $data
     * @return array
     */
    public function getWarehouseData($data);

    /**
     * =======================================================
     * ******************** Plans Section ********************
     * =======================================================
     * */

    /**
     * @return array
     */
    public function planInfo();

    /**
     * @return string
     */
    public function setPlanNotice();

    /**
     * @param $planPackage
     * @return string
     */
    public function displayPlanMessages($planPackage);

    /**
     * @return int
     */
    public function whPlanRestriction();

    /**
     * =======================================================
     * ************* Order detail widget Section *************
     * =======================================================
     * */

    /**
     * @param array $servicesArr
     * @param $hazShipmentArr
     * @return mixed
     */
    public function setOrderDetailWidgetData(array $servicesArr, $hazShipmentArr);

    /**
     * =======================================================
     * ********* Settings and Configuration Section **********
     * =======================================================
     * */

    /**
     * @param $url
     * @param $postData
     * @param bool $isAssocArray
     * @return array
     */
    public function sendCurlRequest($url, $postData, $isAssocArray = false);

    /**
     * @return mixed
     */
    public function quoteSettingsData();

    /**
     * @param $confPath
     * @return mixed
     */
    public function getConfigData($confPath);

    /**
     * =======================================================
     * ********************** RAD Section ********************
     * =======================================================
     * */
    /**
     * @param $service
     * @return string
     */
    public function getAutoResidentialTitle($service);

    /**
     * =======================================================
     * ****************** Get Quotes Section *****************
     * =======================================================
     * */

    /**
     * @param $quotes
     * @param $getMinimum
     * @param $isMultiShipmentQuantity
     * @param $scopeConfig
     * @return mixed
     */
    public function getQuotesResults($quotes, $getMinimum, $isMultiShipmentQuantity, $scopeConfig);

    /**
     * @param $cost
     * @return int
     */
    public function calculateHandlingFee($cost);


    /**
     * =======================================================
     * ****************** Validation Section *****************
     * =======================================================
     * */

    /**
     * @param $whCollection
     * @return array
     */
    public function purifyCollectionData($whCollection);

    /**
     * @param $sPostData
     * @return array
     */
    public function validatedPostData($sPostData);
}
