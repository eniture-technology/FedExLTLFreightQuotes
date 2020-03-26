<?php
namespace Eniture\FedExLTLFreightQuotes\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class CopyBillingAddress
 *
 * @package Eniture\FedExLTLFreightQuotes\Block\System\Config
 */
class CopyBillingAddress extends Field
{
    /**
     * @const string
     */
    const CONFIG_PATH = 'carriers/fedexLtlConnSettings/fedexLtlCopyBillAddress';

    /**
     * @var string
     */
    protected $_template = 'Eniture_FedExLTLFreightQuotes::system/config/copyaddresscheckbox.phtml';

    /**
     * @var null
     */
    protected $_values = null;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;

    /**
     * CopyBillingAddress constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ObjectManagerInterface $objectmanager
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectmanager,
        array $data = []
    ) {
        $this->scopeConfig         = $scopeConfig;
        $this->objectManager       = $objectmanager;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    /**
     * @return array
     */
    public function getValues()
    {
        $values = [];

        foreach ($this->objectManager->create('Eniture\FedExLTLFreightQuotes\Model\Config\Source\CopyBillAddCheckbox')->toOptionArray() as $value) {
            $values[$value['value']] = $value['label'];
        }

        return $values;
    }

    /**
     * @return mixed
     */
    public function getIsChecked()
    {
        return $this->scopeConfig->getValue("fedexltlconnsettings/first/fedexLtlCopyBillAddress", ScopeInterface::SCOPE_STORE);
    }
}
