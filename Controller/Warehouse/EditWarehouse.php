<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Warehouse;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class EditWarehouse extends Action
{
    /**
     * @var Data Object
     */
    private $dataHelper;

    /**
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }

    /**
     * Fetch Warehouse From Database
     */
    public function execute()
    {
        $editWhData = $this->getRequest()->getParams();
        $warehouseList = [];
        if (count($editWhData) && isset($editWhData['edit_id']) && is_numeric($editWhData['edit_id'])) {
            $warehouseId = $editWhData['edit_id'];
            $warehouseList = $this->dataHelper->fetchWarehouseWithID('warehouse', $warehouseId);
            //Get plan
            $plan = $this->dataHelper->planInfo()['planNumber'];
            if ($plan != 3) {
                $warehouseList[0]['in_store'] = null;
                $warehouseList[0]['local_delivery'] = null;
            }
        }
        $response = (count($warehouseList)) ? $warehouseList : $this->dataHelper->generateResponse();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }
}
