<?php

namespace Eniture\FedExLTLFreightQuotes\Controller\Adminhtml\Product;

use Eniture\FedExLTLFreightQuotes\Model\EnituremodulesFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Directory\Model\Country;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Shipping\Model\Config;

/**
 * Class Edit
 * @package Eniture\FedExLTLFreightQuotes\Controller\Adminhtml\Product
 */
class Edit extends \Magento\Catalog\Controller\Adminhtml\Product\Edit
{
    /**
     * @var array
     */
    private $publicActions = ['edit'];
    /**
     * @var
     */
    private $conn;
    /**
     * @var Config
     */
    private $shipconfig;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var EnituremodulesFactory
     */
    private $enModuleFactory;
    /**
     * @var Attribute
     */
    private $attributeFactory;
    /**
     * @var null
     */
    private $dsSourceModel = null;
    /**
     * @var
     */
    private $enDsSource;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Country
     */
    private $enModuleFactoryCreate;


    /**
     * Edit constructor.
     * @param Context $context
     * @param Builder $productBuilder
     * @param PageFactory $resultPageFactory
     * @param ResourceConnection $resource
     * @param Config $shipconfig
     * @param EnituremodulesFactory $enModuleFactory
     * @param Attribute $attributeFactory
     */
    public function __construct(
        Context $context,
        Builder $productBuilder,
        PageFactory $resultPageFactory,
        ResourceConnection $resource,
        Config $shipconfig,
        EnituremodulesFactory $enModuleFactory,
        Attribute $attributeFactory
    ) {
        parent::__construct($context, $productBuilder, $resultPageFactory);
        $this->resultPageFactory    = $resultPageFactory;
        $this->resource             = $resource;
        $this->shipconfig           = $shipconfig;
        $this->enModuleFactory      = $enModuleFactory;
        $this->attributeFactory     = $attributeFactory;
    }

    /**
     * @return type
     */
    public function execute()
    {
        $this->conn = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->enModuleFactoryCreate = $this->enModuleFactory->create();
        $haveEntry = $enitureModules = [];
        $collection = $this->enModuleFactoryCreate->getCollection()
            ->addFilter('module_name', ['eq' => 'ENFedExLTL']);
        foreach ($collection as $value) {
            $haveEntry[] = $value->getData();
        }

        $activeCarriers = array_keys($this->shipconfig->getActiveCarriers());

        foreach ($activeCarriers as $carrierCode) {
            $enCarrier = substr($carrierCode, 0, 2);
            if ($enCarrier == 'EN') {
                array_push($enitureModules, $carrierCode);
            }
        }

        if (count($enitureModules) == 0) {
            return parent::execute();
        }
        $activeModuleList = $enitureModules;

        $enitureTableName = $this->resource->getTableName('enituremodules');

        $this->varifyModuleEntry($haveEntry);

        $eavTableName = $this->resource->getTableName('eav_attribute');
        $this->validateSourceModel($activeModuleList, $enitureTableName, $eavTableName, $enitureModules);
        return parent::execute();
    }


    /**
     * @param $haveEntry
     */
    public function varifyModuleEntry($haveEntry)
    {
        if (empty($haveEntry)) {
            $data = [
                'module_name'           => 'ENFedExLTL',
                'module_script'         => 'Eniture_FedExLTLFreightQuotes',
                'dropship_field_name'   => 'en_dropship_location',
                'dropship_source'       => 'Eniture\FedExLTLFreightQuotes\Model\Source\DropshipOptions',
            ];

            $this->enModuleFactoryCreate->setData($data)->save();
        }
    }

    /**
     * @param $activeModuleList
     * @param $enitureTableName
     * @param $eavTableName
     * @param $enitureModules
     */
    public function validateSourceModel($activeModuleList, $enitureTableName, $eavTableName, $enitureModules)
    {
        $modulesCountDb = $existedModules = [];
        $enModuleCollection = $this->enModuleFactoryCreate->getCollection();
        foreach ($enModuleCollection as $value) {
            $data = $value->getData();
            if (!in_array($data['module_name'], $activeModuleList)) {
                $modulesCountDb[] = $data;
            } else {
                $existedModules[] = $data;
            }
        }

        if (!empty($modulesCountDb)) {
            foreach ($modulesCountDb as $value) {
                $id = $value['module_id'];
                $this->conn->delete($enitureTableName, "module_id='".(int)$id."'");

                $this->enDsSource = $value['dropship_source'];

                $attributeInfo = $this->attributeFactory->getCollection()
                    ->addFieldToFilter('attribute_code', ['eq' => 'en_dropship_location'])
                    ->addFieldToFilter('source_model', ['eq' => $this->enDsSource]);
                foreach ($attributeInfo as $key => $value) {
                    $attrData = $value->getData();
                    $this->dsSourceModel = $attrData['source_model'];
                }
            }

            $ltlExist = $this->enModuleFactoryCreate->getCollection()->addFilter('is_ltl', ['eq' => '1'])->count();
            if (!$ltlExist) {
                $this->conn->delete($eavTableName, "attribute_code='en_freight_class'");
            }

            if ($this->dsSourceModel == null) {
                $dataArr = [
                    'source_model' => $existedModules[0]['dropship_source'],
                ];
                $this->conn->update($eavTableName, $dataArr, "attribute_code = 'en_dropship_location'");
            }
        }
    }
}
