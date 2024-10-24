<?php


namespace Eniture\FedExLTLFreightQuotes\Model\Source;


class ApiEndPoint
{
    public function toOptionArray()
    {
        return ['endPoint' =>
                    ['value' => '1', 'label' => 'Legacy API'],
                    ['value' => '2', 'label' => 'New API']
                ];
    }
}
