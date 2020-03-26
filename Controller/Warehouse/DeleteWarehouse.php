<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Warehouse;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class DeleteWarehouse extends Action
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
     * Delete Warehouse from Database
     */
    public function execute()
    {
        $msg = '';
        $deleteID = null;
        $qry = $canAddWh = 0;
        $deleteWhData = $this->getRequest()->getParams();
        if (count($deleteWhData)) {
            $deleteID = $deleteWhData['delete_id'];
            if ($deleteWhData['action'] == 'delete_warehouse') {
                $qry = $this->dataHelper->deleteWarehouseSecData("warehouse_id='" . $deleteID . "'");
                $msg = 'Warehouse deleted successfully.';
                $canAddWh = $this->dataHelper->whPlanRestriction();
            }
        }
        if ($qry == 1) {
            $response = ['deleteID' => $deleteID, 'qryResp' => $qry, 'canAddWh' => $canAddWh, 'msg' => $msg];
        } else {
            $response = $this->dataHelper->generateResponse();
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }
}
