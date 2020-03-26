<?php
namespace Eniture\FedExLTLFreightQuotes\Block\System\Config;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class UserGuide
 *
 * @package Eniture\FedExLTLFreightQuotes\Block\System\Config
 */
class UserGuide extends Field
{
    /**
     *
     */
    const GUIDE_TEMPLATE = 'system/config/userguide.phtml';

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * UserGuide constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper      = $dataHelper;
        parent::__construct($context, $data);
    }
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::GUIDE_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Show FedEx Small Plan Notice
     * @return string
     */
    public function fedexLtlPlanNotice()
    {
        return $this->dataHelper->setPlanNotice();
    }
}
