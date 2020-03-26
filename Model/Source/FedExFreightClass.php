<?php
namespace Eniture\FedExLTLFreightQuotes\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class FedExFreightClass
 * Source class for Warehouse and Dropship
 * @package Eniture\FedExLTLFreightQuotes\Model\Source
 */
class FedExFreightClass extends AbstractSource
{
    /**
     * @var array
     */
    private $options;

    /**
     * Abstract method of source class
     * @return array
     */
    public function getAllOptions()
    {
        /* your Attribute options list*/
        $this->options= [
            ['label'=>'No Freight Class', 'value'=>0],
            ['label'=>'50', 'value'=>50],
            ['label'=>'55', 'value'=>55],
            ['label'=>'60', 'value'=>60],
            ['label'=>'65', 'value'=>65],
            ['label'=>'70', 'value'=>70],
            ['label'=>'77.5', 'value'=>77],
            ['label'=>'85', 'value'=>85],
            ['label'=>'92.5', 'value'=>92],
            ['label'=>'100', 'value'=>100],
            ['label'=>'110', 'value'=>110],
            ['label'=>'125', 'value'=>125],
            ['label'=>'150', 'value'=>150],
            ['label'=>'175', 'value'=>175],
            ['label'=>'200', 'value'=>200],
            ['label'=>'250', 'value'=>250],
            ['label'=>'300', 'value'=>300],
            ['label'=>'400', 'value'=>400],
            ['label'=>'500', 'value'=>500],
            ['label'=>'Density Based', 'value'=>1],
        ];

        return $this->options;
    }
    /**
     * Abstract method of source class that returns data
     * @param $value
     * @return boolean
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

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }
}
