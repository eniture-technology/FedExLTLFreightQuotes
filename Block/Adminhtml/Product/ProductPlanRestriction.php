<?php

namespace Eniture\FedExLTLFreightQuotes\Block\Adminhtml\Product;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Eniture\FedExLTLFreightQuotes\Helper\EnConstants;
use \Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Shipping\Model\Config;

/**
 * Class ProductPlanRestriction
 * @package Eniture\FedExLTLFreightQuotes\Block\Adminhtml\Product
 */
class ProductPlanRestriction extends Field
{
    /**
     *
     */
    const PRODUCT_TEMPLATE = 'product/productplanrestriction.phtml';

    /**
     * @var string
     */
    public $enable = 'no';
    /**
     * @var Config
     */
    private $shipconfig;
    /**
     * @var Data
     */
    public $dataHelper;


    /**
     * ProductPlanRestriction constructor.
     * @param Context $context
     * @param Config $shipconfig
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $shipconfig,
        Data $dataHelper,
        array $data = []
    ) {
        $this->context = $context;
        $this->shipconfig = $shipconfig;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::PRODUCT_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function planMsg($planInfo)
    {
        $data = ['hazmat' => ['count' => 'hazCount',
            'enabled' => 'hazEnCount',
            'return' => 'hazmatMsg'],
            'insurance' => ['count' => 'insCount',
                'enabled' => 'insEnCount',
                'return' => 'insuranceMsg']
        ];
        $return = [];
        foreach ($data as $key => $value) {
            if ($planInfo[$value['count']] == $planInfo[$value['enabled']]) {
                $return[$value['return']] = null;
            } elseif ($planInfo[$value['enabled']] == 0) {
                $return[$value['return']] = '';
            } else {
                $return[$value['return']] = $this->setPlanMsg($planInfo['data'], $key);
            }
        }
        return $return;
    }

    public function setPlanMsg($msgInfo, $index)
    {
        $msg = $planMsg = "";
        foreach ($msgInfo as $res) {
            if (isset($res[$index])) {
                if ($res[$index] == 'Enabled') {
                    $planMsg = ' ' . $res['label'] . ' : <b>' . $res[$index] . '</b>.<br>';
                }
                if ($res[$index] == 'Disabled') {
                    $planMsg = ' ' . $res['label'] . ' : Upgrade to <b>Standard Plan</b> to enable.<br>';
                }
                $msg .= $planMsg;
            }
        }
        return $msg;
    }

    public function getPlanInfo()
    {
        $numLTL = $numSmPkg = $hazEn = $insEn = 0;
        $activeCarriers = array_keys($this->shipconfig->getActiveCarriers());
        foreach ($activeCarriers as $carrierCode) {
            $hazmat = $insurance = 'Disabled';
            $enCarrier = substr($carrierCode, 0, 2);
            if ($enCarrier == 'EN') {
                $carrierLabel = $this->getConfiguration($carrierCode, 'label');
                $carrierPlan = $this->getConfiguration($carrierCode, 'plan');

                $restriction['data'][$carrierCode] = [
                    'label' => $carrierLabel,
                    'plan' => $carrierPlan
                ];
                if (strpos($carrierCode, 'LTL') !== false) {
                    $numLTL++;
                }
                if (strpos($carrierCode, 'Smpkg') !== false) {
                    $numSmPkg++;
                }
                if ($carrierPlan > 1) {
                    $hazmat = $insurance = 'Enabled';
                    $hazEn++;
                }
                if ($numLTL) {
                    $restriction['data'][$carrierCode]['hazmat'] = $hazmat;
                }
                if ($numSmPkg) {
                    if ($carrierPlan > 1) {
                        $insEn++;
                    }
                    $restriction['data'][$carrierCode]['hazmat'] = $hazmat;
                    $restriction['data'][$carrierCode]['insurance'] = $insurance;
                }
            }
        }
        $restriction['hazCount'] = $numSmPkg + $numLTL;
        $restriction['insCount'] = $numSmPkg;
        $restriction['hazEnCount'] = $hazEn;
        $restriction['insEnCount'] = $insEn;
        return $restriction;
    }

    public function getConfiguration($carrierCode, $reqFor)
    {
        return $this->context->getScopeConfig()->getValue(
            'eniture/' . $carrierCode . '/' . $reqFor . ''
        );
    }
    public function enUrl()
    {
        return EnConstants::EN_URL;
    }
}
