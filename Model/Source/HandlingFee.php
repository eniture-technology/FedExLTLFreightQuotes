<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

/**
 * Class HandlingFee
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class HandlingFee
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
            return [
                'handlingFeeVal' =>
                    [  'value' => 'flat',  'label'  => 'Flat Rate'],
                    [  'value' => '%',     'label'  => 'Percentage ( % )'],
            ];
    }
}
