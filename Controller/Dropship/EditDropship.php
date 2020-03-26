<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Dropship;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class EditDropship extends Action
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
     * Fetch Drop Ship from Database
     */
    public function execute()
    {
        $editDsData = [];
        foreach ($this->getRequest()->getParams() as $key => $post) {
            $editDsData[$key] = filter_var($post, FILTER_SANITIZE_STRING);
        }

        $getDropShipId = $editDsData['edit_id'];
        $dropShipList = $this->dataHelper->fetchWarehouseWithID('dropship', $getDropShipId);
        //Get plan
        $plan = $this->dataHelper->planInfo()['planNumber'];
        if ($plan != 3) {
            $dropShipList[0]['in_store'] = null;
            $dropShipList[0]['local_delivery'] = null;
        }

        //Change html entities code
        $nick = $dropShipList[0]['nickname'];
        $dropShipList[0]['nickname'] = html_entity_decode($nick, ENT_QUOTES);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($dropShipList));
    }
}
