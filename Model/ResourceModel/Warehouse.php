<?php
namespace Eniture\FedExLTLFreightQuotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Warehouse
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\ResourceModel
 */
class Warehouse extends AbstractDb
{

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('warehouse', 'warehouse_id');
    }
}
