<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Test;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Eniture\FedExLTLFreightQuotes\Helper\EnConstants;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class TestConnection
 *
 * @package Eniture\FedExLTLFreightQuotes\Controller\Test
 */
class TestConnection extends Action
{

    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * TestConnection constructor.
     *
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->request = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $credentials = [];
        foreach ($this->getRequest()->getParams() as $key => $data) {
            $credentials[$key] = filter_var($data);
        }
        $postData = [
            'testConnectionCarrier' => 'fedex',
            'AccountNumber' => $credentials['AccountNumber'],
            'MeterNumber' => $credentials['MeterNumber'],
            'password' => $credentials['password'],
            'key' => $credentials['key'],
            'shippingChargesAccount' => $credentials['shippingChargesAccount'],
            'billingLineAddress' => $credentials['billingLineAddress'],
            'billingCountry' => $credentials['billingCountry'],
            'billingCity' => $credentials['billingCity'],
            'billingState' => $credentials['billingState'],
            'billingZip' => $credentials['billingZip'],
            'physicalAddress' => $credentials['physicalAddress'],
            'physicalCountry' => $credentials['physicalCountry'],
            'physicalCity' => $credentials['physicalCity'],
            'physicalStateOrProvinceCode' => $credentials['physicalStateOrProvinceCode'],
            'physicalPostalCode' => $credentials['physicalPostalCode'],
            'third_party_account' => $credentials['thirdPartyAccount'],
            'licence_key' => $credentials['licence_key'],
            'plateform' => 'magento2',
            'carrier_mode' => 'test',
            'carrierName' => 'fedex',
            'sever_name' => $this->getStoreUrl(),
            'accountType' => (!empty($credentials['thirdPartyAccount'])) ? 'thirdParty' : 'shipper',
        ];

        $response = $this->dataHelper->sendCurlRequest(EnConstants::TEST_CONN_URL, $postData);
        $result = $this->testConnResponse($response);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($result);
    }

    /**
     * @param $data
     * @return false|string
     */
    public function testConnResponse($data)
    {
        $response = [];
        $successMsg = 'The test resulted in a successful connection.';
        $errorMsg = 'The credentials entered did not result in a successful test. Confirm your credentials and try again.';

        if (isset($data->severity) && $data->severity == 'ERROR') {
            $response['error'] = $errorMsg;
        } elseif (isset($data->error) && !is_int($data->error)) {
            $response['error'] = $data->error;
        } else {
            $response['success'] = $successMsg;
        }
        return json_encode($response);
    }

    /**
     * This function returns the Current Store Url
     * @return string
     */
    public function getStoreUrl()
    {
        // It will be written to return Current Store Url in multi-store view
        return $this->getRequest()->getServer('SERVER_NAME');
    }
}
