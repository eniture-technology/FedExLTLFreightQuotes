<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Test;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Eniture\FedExLTLFreightQuotes\Cron\PlanUpgrade;
use Eniture\FedExLTLFreightQuotes\Helper\Data;

class PlanRefresh extends Action
{
    /**
     * @var PlanUpgrade
     */
    private $planUpgrade;
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * TestConnection constructor.
     * @param Context $context
     * @param PlanUpgrade $planUpgrade
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        PlanUpgrade $planUpgrade,
        Data $dataHelper
    ) {
        $this->planUpgrade = $planUpgrade;
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->planUpgrade->execute();
        $result = json_encode(['success' => '1']);
        $this->dataHelper->clearCache();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($result);
    }
}
