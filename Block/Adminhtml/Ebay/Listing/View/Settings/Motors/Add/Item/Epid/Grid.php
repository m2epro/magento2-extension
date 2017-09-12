<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Epid;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Grid
{
    protected $itemCollectionFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Item\Collection $collection */
        $collection = $this->itemCollectionFactory->create();
        $collection->setConnection($this->resourceConnection->getConnection());
        $collection->setIdFieldName('epid');

        $table = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_motor_epid');

        $collection->getSelect()->reset()->from([
            'main_table' => $table
        ]);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'epid', 'product_type', 'make', 'model', 'year', 'trim', 'engine', 'submodel', 'is_custom'
        ]);
        $collection->setScope($this->getHelper('Component\Ebay\Motors')->getEpidsScopeByType($this->getMotorsType()));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('epid', [
            'header' => $this->__('ePID'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'epid',
            'width'  => '100px',
            'frame_callback' => [$this, 'callbackColumnIdentifier']
        ]);

        $this->addColumn('product_type', [
            'header' => $this->__('Type'),
            'align'  => 'left',
            'type'   => 'options',
            'index'  => 'product_type',
            'options'  => [
                \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_VEHICLE
                    => $this->__('Car / Truck'),
                \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_MOTORCYCLE
                    => $this->__('Motorcycle'),
                \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_ATV
                    => $this->__('ATV / Snowmobiles'),
            ]
        ]);

        $this->addColumn('make', [
            'header' => $this->__('Make'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'make',
            'width'  => '150px'
        ]);

        $this->addColumn('model', [
            'header' => $this->__('Model'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'model',
            'width'  => '150px'
        ]);

        $this->addColumn('submodel', [
            'header' => $this->__('Submodel'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'submodel',
            'width'  => '100px',
            'frame_callback' => [$this, 'callbackNullableColumn']
        ]);

        $this->addColumn('year', [
            'header' => $this->__('Year'),
            'align'  => 'left',
            'type'   => 'number',
            'index'  => 'year',
            'width'  => '100px'
        ]);

        $this->addColumn('trim', [
            'header' => $this->__('Trim'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'trim',
            'width'  => '100px',
            'frame_callback' => [$this, 'callbackNullableColumn']
        ]);

        $this->addColumn('engine', [
            'header' => $this->__('Engine'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'engine',
            'width'  => '100px',
            'frame_callback' => [$this, 'callbackNullableColumn']
        ]);

        return parent::_prepareColumns();
    }

    //########################################
}