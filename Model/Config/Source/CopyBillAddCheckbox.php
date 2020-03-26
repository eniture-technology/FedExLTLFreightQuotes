<?php

namespace Eniture\FedExLTLFreightQuotes\Model\Config\Source;

/**
 * Class CopyBillAddCheckbox
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Config\Source
 */
class CopyBillAddCheckbox
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'copyBillAdd', 'label'=>__('Copy billing address to physical address.')]];
    }
}
