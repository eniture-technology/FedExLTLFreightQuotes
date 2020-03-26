<?php
namespace Eniture\FedExLTLFreightQuotes\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Warehouse
 *
 * @package Eniture\FedExLTLFreightQuotes\Model
 */
class Warehouse extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Warehouse');
    }
}
