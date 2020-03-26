<?php
namespace Eniture\FedExLTLFreightQuotes\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Enituremodules
 *
 * @package Eniture\FedExLTLFreightQuotes\Model
 */
class Enituremodules extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Eniture\FedExLTLFreightQuotes\Model\ResourceModel\Enituremodules');
    }
}
