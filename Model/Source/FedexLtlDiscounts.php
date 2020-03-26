<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class FedexLtlDiscounts
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class FedexLtlDiscounts implements ArrayInterface
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
                        'label' => __('My account has negotiated LTL rates.')
                    ],
                    [
                        'value' => 'promotion',
                        'label' => __('My account receives a promotional discount.')
                    ],
                ];
    }
}
