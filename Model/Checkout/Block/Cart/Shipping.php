<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Checkout\Block\Cart;

use Magento\Checkout\Block\Cart\LayoutProcessor;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Shipping
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Checkout\Block\Cart
 */
class Shipping extends LayoutProcessor
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Shipping constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeMerger $merger
     * @param Collection $countryCollection
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeMerger $merger,
        Collection $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($merger, $countryCollection, $regionCollection);
    }

    /**
     * @return bool
     */
    protected function isCityActive()
    {
        if ($this->scopeConfig->getValue('carriers/ENFedExLTL/active')) {
            return true;
        }
    }
}
