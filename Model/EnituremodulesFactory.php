<?php

namespace Eniture\FedExLTLFreightQuotes\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class EnituremodulesFactory
 *
 * @package Eniture\FedExLTLFreightQuotes\Model
 */
class EnituremodulesFactory
{
    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     * @return \Magento\Directory\Model\Country
     */
    public function create(array $arguments = [])
    {
        return $this->objectManager->create('Eniture\FedExLTLFreightQuotes\Model\Enituremodules', $arguments, false);
    }
}
