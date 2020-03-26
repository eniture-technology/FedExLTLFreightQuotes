<?php
namespace Eniture\FedExLTLFreightQuotes\App;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class State
 * @package Eniture\FedExLTLFreightQuotes\App
 */
class State extends \Magento\Framework\App\State
{
    /**
     * @return void
     * @throws LocalizedException
     */
    public function validateAreaCode()
    {
        if (!isset($this->_areaCode)) {
            $this->setAreaCode(Area::AREA_GLOBAL);
        }
    }
}
