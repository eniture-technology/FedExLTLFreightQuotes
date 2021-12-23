<?php

namespace Eniture\FedExLTLFreightQuotes\Block\System\Config;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ResidentialAddressDetection
 *
 * @package Eniture\FedExLTLFreightQuotes\Block\System\Config
 */
class ResidentialAddressDetection extends Field
{
    /**
     *
     */
    const RAD_TEMPLATE = 'system/config/resaddressdetection.phtml';

    /**
     * @var Manager
     */
    public $moduleManager;
    /**
     * @var string
     */
    public $enable = 'no';
    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;
    /**
     * @var
     */
    public $licenseKey;
    /**
     * @var
     */
    public $radUseSuspended;
    /**
     * @var
     */
    public $trialMsg;
    /**
     * @var
     */
    public $resAddDetectData;
    /**
     * @var Context
     */
    public $context;
    /**
     * @var Data
     */
    public $dataHelper;
    public $addressType;


    /**
     * ResidentialAddressDetection constructor.
     * @param Context $context
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectmanager
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        ObjectManagerInterface $objectmanager,
        Data $dataHelper,
        $data = []
    ) {
        $this->objectManager = $objectmanager;
        $this->moduleManager = $moduleManager;
        $this->context = $context;
        $this->dataHelper = $dataHelper;
        $this->planRstrctnQuoteSettng();

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        $this->checkBinPackagingModule();
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::RAD_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return html
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * This function returns the HTML, used in the Block
     * @return mixed
     */

    public function getHtml()
    {
        return $this->_toHtml();
    }

    /**
     *
     */
    public function checkBinPackagingModule()
    {
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $scopeConfig = $this->context->getScopeConfig();
            $configPath = "fedexltlconnsettings/first/fedexLtlLicenseKey";
            $this->licenseKey = $scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);

            $this->enable = 'yes';
            $dataHelper = $this->objectManager->get("Eniture\ResidentialAddressDetection\Helper\Data");
            $this->resAddDetectData = $dataHelper->resAddDetectDataHandling($this->licenseKey);
            $this->radUseSuspended = $dataHelper->radUseSuspended();
            $this->addressType = $dataHelper->getAddressType();
            if ($dataHelper->checkModuleTrial()) {
                $this->trialMsg = 'The LTL Freight Quotes module must have active paid license to continue to use this feature.';
            }
        }
    }

    /**
     * @return string
     */
    public function suspendRADUrl()
    {
        return $this->getbaseUrl() . '/ResidentialAddressDetection/RAD/SuspendedRAD/';
    }

    /**
     * @return string
     */
    public function autoRenewRADPlanUrl()
    {
        return $this->getbaseUrl() . '/ResidentialAddressDetection/RAD/AutoRenewPlan/';
    }

    /**
     * @return string
     */
    public function addressTypeUrl()
    {
        return $this->getbaseUrl() . '/ResidentialAddressDetection/RAD/DefaultAddressType/';
    }

    /**
     * Show FedEx Small Plan Notice
     * @return string
     */
    public function fedexLtlPlanNotice()
    {
        $planRefreshUrl = $this->getPlanRefreshUrl();
        return $this->dataHelper->setPlanNotice($planRefreshUrl);
    }

    /**
     * @return false|string
     */
    public function planRstrctnQuoteSettng()
    {
        return json_encode($this->dataHelper->quoteSettingFieldsToRestrict());
    }

    /**
     * @return url
     */
    public function getPlanRefreshUrl()
    {
        return $this->getbaseUrl().'fedexltlfreightquotes/Test/PlanRefresh/';
    }
}
