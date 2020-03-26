<?php

namespace Eniture\FedExLTLFreightQuotes\Block\System\Config;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Eniture\FedExLTLFreightQuotes\Helper\EnConstants;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends Field
{
    const BUTTON_TEMPLATE = 'system/config/testconnection.phtml';

    private $dataHelper;

    /**
     * @param Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(Context $context, Data $dataHelper, array $data = [])
    {
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
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return $this->getbaseUrl() . '/fedexltlfreightquotes/Test/TestConnection/';
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->addData(
            [
                'id' => 'fedexLtTestConnBtn',
                'button_label' => 'Test Connection',
            ]
        );
        return $this->_toHtml();
    }

    /**
     * Show FedEx LTL Plan Notice
     * @return string
     */
    public function planNotice()
    {
        return $this->dataHelper->setPlanNotice();
    }

    public function enUrl()
    {
        return EnConstants::EN_URL;
    }

    public function conMessage()
    {
        return '<div class="message message-notice notice fedexLt-conn-setting-note"><div data-ui-id="messages-message-notice">Note! You must have a FedEx account to use this application. If you do not have one, contact FedEx at 800-463-3339 or <a target="_blank" href="https://www.fedex.com/en-us/create-account.html">register online</a>.</div>';
    }
}
