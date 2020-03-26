<?php

namespace Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Warehouse;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Warehouse
 */
class Collection extends AbstractCollection
{

    /**
     *
     */
    public function _construct()
    {
        $this->_init('Eniture\FedExLTLFreightQuotes\Model\Warehouse', 'Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Warehouse');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
