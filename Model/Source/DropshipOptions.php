<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

use Eniture\FedExLTLFreightQuotes\Helper\Data;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class DropshipOptions
 *
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class DropshipOptions extends AbstractSource
{
    /**
     * @var Data
     */
    public $dataHelper;
    /**
     * @var array
     */
    public $options = [];

    /**
     * DropshipOptions constructor.
     *
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $getDropShip = $this->dataHelper->fetchWarehouseSecData('dropship');

        if (isset($getDropShip) && count($getDropShip) > 0) {
            foreach ($getDropShip as $manufacturer) {
                ( isset($manufacturer['nickname']) && $manufacturer['nickname'] == '' ) ? $nickname = '' : $nickname = html_entity_decode($manufacturer['nickname'], ENT_QUOTES).' - ';
                $city       = $manufacturer['city'];
                $state      = $manufacturer['state'];
                $zip        = $manufacturer['zip'];
                $dropShip   = $nickname.$city.', '.$state.', '.$zip;
                $this->options[] = [
                        'label' => __($dropShip),
                        'value' => $manufacturer['warehouse_id'],
                    ];
            }
        }
        return $this->options;
    }

    /**
     * @param int|string $value
     * @return bool|string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions(false);

        foreach ($options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return false;
    }
}
