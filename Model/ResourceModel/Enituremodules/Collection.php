<?php

namespace Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Enituremodules;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Enituremodules
 */
class Collection extends AbstractCollection
{

    /**
     *
     */
    public function _construct()
    {
        $this->_init('Eniture\FedExLTLFreightQuotes\Model\Enituremodules', 'Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Enituremodules');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
