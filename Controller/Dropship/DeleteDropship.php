<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Dropship;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class DeleteDropship extends Action
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
     * Delete Drop Ship from Database
     */
    public function execute()
    {
        $msg = 'Something went wrong!';
        $qry = 0;
        $deleteID = null;
        $deleteDsData = $this->getRequest()->getParams();
        if (count($deleteDsData)) {
            $deleteID = $deleteDsData['delete_id'];

            if ($deleteDsData['action'] == 'delete_dropship') {
                $qry = $this->dataHelper->deleteWarehouseSecData("warehouse_id='" . $deleteID . "'");
                $msg = 'Drop ship deleted successfully.';
            }
        }
        if ($qry == 1) {
            $response = ['deleteID' => $deleteID, 'qryResp' => $qry, 'msg' => $msg];
        } else {
            $response = $this->dataHelper->generateResponse();
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }
}
