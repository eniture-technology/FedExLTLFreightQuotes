<?php

namespace Eniture\FedExLTLFreightQuotes\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class OrderPlacebeforeSaveData
 *
 * @package Eniture\FedExLTLFreightQuotes\Observer
 */
class OrderPlacebeforeSaveData implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $coreSession;

    /**
     * OrderPlacebeforeSaveData constructor.
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $isMulti = '0';
            $multiShip = false;
            $order = $observer->getEvent()->getOrder();
            $quote = $order->getQuote();

            if (isset($quote)) {
                $isMulti = $quote->getIsMultiShipping();
            }

            $method = $order->getShippingMethod();

            if (strpos($method, 'ENFedExLTL') !== false) {
                $semiOrderDetailData = $this->coreSession->getSemiOrderDetailSession();
                $orderDetailData = $this->coreSession->getFedExLTLOrderDetailSession();
                if ($orderDetailData != null && $semiOrderDetailData == null) {
                    if (count($orderDetailData['shipmentData']) > 1) {
                        $multiShip = true;
                    }
                    $orderDetailData = $this->getData($order, $method, $orderDetailData, $multiShip);
                } elseif ($semiOrderDetailData) {
                    $orderDetailData = $semiOrderDetailData['fedexLTL'];
                    $this->coreSession->unsSemiOrderDetailSession();
                }
                $order->setData('order_detail_data', json_encode($orderDetailData));
                $order->save();
                if (!$isMulti) {
                    $this->coreSession->unsFedExLTLOrderDetailSession();
                }
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    private function getData($order, $method, $orderDetailData, $multiShip)
    {
        $liftGate = $resi = $hoAt = false;
        $shippingMethod = explode('_', $method);
        /*These Lines are added for compatibility only*/
        $lgArray = ['always' => 1, 'asOption' => '', 'residentialLiftgate' => ''];
        $orderDetailData['residentialDelivery'] = 0;
        /*These Lines are added for compatibility only*/

        $arr = (explode('+', $method));
        if (in_array('LG', $arr)) {
            $orderDetailData['liftGateDelivery'] = $lgArray;
            $liftGate = true;
        }
        if (in_array('R', $arr)) {
            $orderDetailData['residentialDelivery'] = 1;
            $resi = true;
        }
        if (in_array('T', $arr)) {
            $hoAt = true;
        }
        foreach ($orderDetailData['shipmentData'] as $key => $value) {
            if ($multiShip) {
                $quotes = reset($value['quotes']);
                if ($liftGate) {
                    $orderDetailData['shipmentData'][$key]['quotes'] = $quotes['liftgate'];
                } elseif ($hoAt) {
                    $orderDetailData['shipmentData'][$key]['quotes'] = $quotes['hoat'];
                } else {
                    $orderDetailData['shipmentData'][$key]['quotes'] = $quotes['simple'];
                }
            } else {
                $orderDetailData['shipmentData'][$key]['quotes'] = [

                    'code' => $shippingMethod[1],
                    'title' => str_replace("FedEx LTL Freight Quotes - ", "", $order->getShippingDescription()),
                    'rate' => number_format((float)$order->getShippingAmount(), 2, '.', '')
                ];
            }
        }
        return $orderDetailData;
    }
}
