<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class RateSource
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class RateSource implements ArrayInterface
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'negotiate',
                        'label' => __('Use my negotiated rates.')
                    ],
                    [
                        'value' => 'retail',
                        'label' => __('Use retail (list) rates.')
                    ],
                ];
    }
}
