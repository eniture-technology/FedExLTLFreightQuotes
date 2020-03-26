<?php
namespace Eniture\FedExLTLFreightQuotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Enituremodules
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\ResourceModel
 */
class Enituremodules extends AbstractDb
{

    /**
     *
     */
    public function _construct()
    {
        $this->_init('enituremodules', 'module_id');
    }
}
