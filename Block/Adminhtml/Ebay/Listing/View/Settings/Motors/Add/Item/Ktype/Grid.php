<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Ktype;

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
        $collection->setIdFieldName('ktype');

        $table = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_motor_ktype');

        $collection->getSelect()->reset()->from([
            'main_table' => $table
        ]);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'ktype', 'make', 'model', 'variant', 'body_style', 'type', 'from_year', 'to_year', 'engine', 'is_custom'
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('ktype', [
            'header' => $this->__('kType'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'ktype',
            'width'  => '100px',
            'frame_callback' => [$this, 'callbackColumnIdentifier']
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

        $this->addColumn('variant', [
            'header' => $this->__('Variant'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'variant',
            'width'  => '150px'
        ]);

        $this->addColumn('body_style', [
            'header' => $this->__('Body Style'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'body_style',
            'width'  => '150px'
        ]);

        $this->addColumn('type', [
            'header' => $this->__('Type'),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'type',
            'width'  => '150px'
        ]);

        $this->addColumn('year', [
            'header' => $this->__('Year'),
            'align'  => 'left',
            'type'   => 'text',
            'width'  => '150px',
            'index'  => 'to_year',
            'filter_index' => 'from_year',
            'frame_callback'            => [$this, 'callbackYearColumn'],
            'filter_condition_callback' => [$this, 'yearColumnFilter'],
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

    public function callbackYearColumn($value, $row, $column, $isExport)
    {
        return $row['from_year'] . ' - ' . $row['to_year'];
    }

    public function yearColumnFilter($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return $this;
        }

        $collection->addFieldToFilter('from_year', ['to' => $value]);
        $collection->addFieldToFilter('to_year', ['from' => $value]);

        return $this;
    }

    //########################################
}