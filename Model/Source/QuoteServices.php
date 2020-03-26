<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

/**
 * Class QuoteServices
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class QuoteServices
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'serviceOptions' => ['value' => 'FEDEX_FREIGHT_ECONOMY',  'label'  => 'FedEx Freight Economy'],
                ['value' => 'FEDEX_FREIGHT_PRIORITY',  'label'  => 'FedEx Freight Priority'],
            ];
    }
}
