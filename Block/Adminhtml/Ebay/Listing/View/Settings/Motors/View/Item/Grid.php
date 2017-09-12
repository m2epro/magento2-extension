<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Item;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    //########################################

    private $listingProductId;
    private $listingProduct;

    private $motorsType;

    protected $customCollectionFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        $this->setId('ebayMotorViewItemGrid');

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(false);
        //------------------------------
    }

    // ########################################

    protected function getExistingItems(array $ids)
    {
        $typeIdentifier = $this->getMotorsHelper()->getIdentifierKey($this->getMotorsType());

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $this->getMotorsHelper()->getDictionaryTable($this->getMotorsType()),
                [$typeIdentifier]
            )
            ->where('`'.$typeIdentifier.'` IN (?)', $ids);

        if ($this->getMotorsHelper()->isTypeBasedOnEpids($this->getMotorsType())) {
            $select->where('scope = ?', $this->getMotorsHelper()->getEpidsScopeByType($this->getMotorsType()));
        }

        return $select->query()->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function getCollectionItems()
    {
        $attributeValue = $this->getListingProduct()->getMagentoProduct()->getAttributeValue(
            $this->getMotorsHelper()->getAttribute($this->getMotorsType())
        );

        $parsedValue = $this->getMotorsHelper()->parseAttributeValue($attributeValue);
        if (empty($parsedValue)) {
            return [];
        }

        $existingItems = $this->getExistingItems(array_keys($parsedValue['items']));

        $items = [];
        foreach ($parsedValue['items'] as $id => $item) {

            if (!in_array($id, $existingItems)) {
                continue;
            }

            $itemData = [
                'id'       => $id,
                'note'     => $item['note']
            ];

            $items[$id] = new \Magento\Framework\DataObject($itemData);
        }

        return $items;
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Custom $collection */
        $collection = $this->customCollectionFactory->create();

        foreach ($this->getCollectionItems() as $item) {
            $collection->addItem($item);
        }
        $collection->setCustomSize($collection->count());

        $this->setCollection($collection);

        parent::_prepareCollection();

        $collection->setCustomIsLoaded(true);

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('item', [
            'header' => $this->__($this->getItemsColumnTitle()),
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'id',
            'width'  => '50px',
            'frame_callback' => [$this, 'callbackColumnIdentifier'],
            'filter_condition_callback' => [$this, 'customColumnFilter']
        ]);

        $this->addColumn('note', [
            'header'       => $this->__('Note'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'note',
            'width'        => '350px',
            'filter_index' => 'note',
            'filter_condition_callback' => [$this, 'customColumnFilter']
        ]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setUseSelectAll(false);
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('removeItem', [
            'label'   => $this->__('Remove'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);
        //--------------------------------

        return parent::_prepareMassaction();
    }

    protected function getNoFilterMassactionColumn()
    {
        return true;
    }

    //########################################

    public function callbackColumnIdentifier($value, $row, $column, $isExport)
    {
        return $value;
    }

    // ####################################

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection() && $column->getFilterConditionCallback()) {
            call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
        }
        return $this;
    }

    //########################################

    protected function customColumnFilter($collection, $column)
    {
        $field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();
        $condition = $column->getFilter()->getCondition();
        $value = array_pop($condition);

        if ($field && isset($condition)) {
            $this->filterByField($field, $value->__toString());
        }

        return $this;
    }

    //--------------------------------

    protected function filterByField($field, $value)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Custom $filteredCollection */
        $filteredCollection = $this->customCollectionFactory->create();

        $value = str_replace([' ','%','\\','\''],'',$value);

        foreach ($this->getCollection()->getItems() as $item) {
            if (strpos($item->getData($field),$value) !== false) {
                $filteredCollection->addItem($item);
            }
        }
        $filteredCollection->setCustomSize($filteredCollection->count());

        $this->setCollection($filteredCollection);

        $filteredCollection->setCustomIsLoaded(true);
    }

    // ####################################

    protected function _setCollectionOrder($column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $direction = $column->getDir();

        if ($field && isset($direction)) {
            $this->_orderByColumn($field, $direction);
        }

        return $this;
    }

    //--------------------------------

    protected function _orderByColumn($column, $direction)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Custom $sortedCollection */
        $sortedCollection = $this->customCollectionFactory->create();

        $collection = $this->getCollection()->toArray();
        $collection = $collection['items'];

        $sortByColumn = [];
        foreach ($collection as $item) {
            $sortByColumn[] = $item[$column];
        }

        strtolower($direction) == 'asc' && array_multisort($sortByColumn, SORT_ASC, $collection);
        strtolower($direction) == 'desc' && array_multisort($sortByColumn, SORT_DESC, $collection);

        foreach ($collection as $item) {
            $sortedCollection->addItem(new \Magento\Framework\DataObject($item));
        }
        $sortedCollection->setCustomSize($sortedCollection->count());

        $this->setCollection($sortedCollection);

        $sortedCollection->setCustomIsLoaded(true);
    }

    // ####################################

    protected function _toHtml()
    {

        if (!$this->canDisplayContainer()) {

            $this->js->add(<<<JS
    EbayListingViewSettingsMotorsViewItemGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/View/Item/Grid'
    ], function() {
        EbayListingViewSettingsMotorsViewItemGridObj = new EbayListingViewSettingsMotorsViewItemGrid(
            '{$this->getId()}',
            '{$this->getListingProductId()}'
        );
        EbayListingViewSettingsMotorsViewItemGridObj.afterInitPage();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_settings_motors/viewItemGrid', [
            '_current' => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsColumnTitle()
    {
        if ($this->getMotorsHelper()->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID');
        }

        return $this->__('kType');
    }

    //########################################

    public function getListingProduct()
    {
        if (is_null($this->listingProduct)) {
            $this->listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK , 'Listing\Product', $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################

    /**
     * @return null
     */
    public function getListingProductId()
    {
        return $this->listingProductId;
    }

    /**
     * @param null $listingProductId
     */
    public function setListingProductId($listingProductId)
    {
        $this->listingProductId = $listingProductId;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Helper\Component\Ebay\Motors
     */
    private function getMotorsHelper()
    {
        return $this->getHelper('Component\Ebay\Motors');
    }

    //########################################
}