<?php

namespace Eniture\FedExLTLFreightQuotes\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

/**
 * Class OrderDetailWidget
 *
 * @package Eniture\FedExLTLFreightQuotes\Block\Adminhtml\Order\View\Tab
 */
class OrderDetailWidget extends Template implements TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'order/view/tab/orderdetailwidget.phtml';

    /**
     * Core registry
     *
     * @var Registry
     */
    private $coreRegistry = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Additional Order Details');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Additional Order Details');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        if ($this->coreRegistry->registry('orderWidgetFlag') === null) {
            $this->coreRegistry->register('orderWidgetFlag', 'yes');
            // For me, I wanted this tab to always show
            // You can play around with the ACL settings
            // to selectively show later if you want
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        // For me, I wanted this tab to always show
        // You can play around with conditions to
        // show the tab later
        return false;
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        // I wanted mine to load via AJAX when it's selected
        // That's what this does
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        // custom tab is a adminhtml router we're about to define
        // the full route can really be whatever you want
        return $this->getUrl('orderdetailwidget/*/OrderDetailWidget', ['_current' => true]);
    }
}
