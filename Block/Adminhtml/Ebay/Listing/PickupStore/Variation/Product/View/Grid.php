<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Variation\Product\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    protected $listingProductId;
    protected $pickupStoreId;
    protected $variationAttributes;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;
    protected $resourceConnection;
    protected $customCollectionFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->customCollectionFactory = $customCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayPickupStoreVariationProductGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->listingProductId = (int)$this->getRequest()->getParam('product_id');
        $this->pickupStoreId = (int)$this->getRequest()->getParam('pickup_store_id');
        $this->listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $this->listingProductId);
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product\Variation')->getCollection();
        $collection->getSelect()->where('main_table.listing_product_id = ?',$this->listingProductId);
        $collection->getSelect()->group('main_table.id');
        // ---------------------------------------

        // ---------------------------------------
        $collection->getSelect()->join(
            ['mlpvo' => $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
                                                  ->getResource()->getMainTable()],
            '`mlpvo`.`listing_product_variation_id`=`main_table`.`id`'
        );

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'id'                 => 'main_table.id',
                'listing_product_id' => 'main_table.listing_product_id',
                'additional_data'    => 'main_table.additional_data',
                'add'                => 'second_table.add',
                'delete'             => 'second_table.delete',
                'online_price'       => 'second_table.online_price',
                'online_sku'         => 'second_table.online_sku',
                'available_qty'      => new \Zend_Db_Expr('(second_table.online_qty - second_table.online_qty_sold)'),
                'online_qty_sold'    => 'second_table.online_qty_sold',
                'status'             => 'second_table.status',
                'attributes'       => 'GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`option` SEPARATOR \'||\')',
                'products_ids'     => 'GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`product_id` SEPARATOR \'||\')'
            ]
        );

        /** @var \Ess\M2ePro\Model\ResourceModel\Collection\Custom $resultCollection */
        $resultCollection = $this->customCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            ['main_table' => $collection->getSelect()],
            [
                'id',
                'listing_product_id',
                'additional_data',
                'add',
                'delete',
                'online_price',
                'available_qty',
                'online_sku',
                'online_qty_sold',
                'status',
                'attributes',
                'products_ids',
                'account_pickup_store_id',
                'store_name',
                'store_online_qty',
                'state_id',
                'is_added',
                'is_deleted',
                'is_in_processing'
            ]
        );
        $collection->getSelect()->join(
            ['elpp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')
                                                 ->getResource()->getMainTable()],
            'elpp.listing_product_id=main_table.listing_product_id',
            ['account_pickup_store_id']
        );
        $collection->getSelect()->joinLeft(
            ['eap' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore')
                                                ->getResource()->getMainTable()],
            'eap.id=elpp.account_pickup_store_id',
            ['store_name' => 'name']
        );
        $collection->getSelect()->joinLeft(
            ['eaps' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')
                                                 ->getResource()->getMainTable()],
            'eaps.sku=online_sku AND eaps.account_pickup_store_id=eap.id',
            [
                'store_online_qty'   => 'online_qty',
                'state_id'           => 'id',
                'is_in_processing'   => 'is_in_processing',
                'is_added'           => 'is_added',
                'is_deleted'         => 'is_deleted',
            ]
        );
        $collection->getSelect()->where('eap.id = ?', $this->pickupStoreId);
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('variation', [
            'header' => $this->__('Magento Variation'),
            'align' => 'left',
            'sortable' => false,
            'index' => 'attributes',
            'filter_index' => 'attributes',
            'frame_callback' => [$this, 'callbackColumnVariations'],
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $this->getVariationsAttributes(),
            'filter_condition_callback' => [$this, 'callbackFilterVariations']
        ]);

        $this->addColumn('online_sku', [
            'header'    => $this->__('SKU'),
            'align'     => 'left',
            'width'     => '300px',
            'index'     => 'online_sku',
            'filter_index' => 'online_sku',
            'frame_callback' => [$this, 'callbackColumnOnlineSku']
        ]);

        $this->addColumn('store_online_qty', [
            'header'    => $this->__('Available QTY'),
            'align'     => 'right',
            'type'      => 'number',
            'width'     => '100px',
            'index'     => 'store_online_qty',
            'frame_callback' => [$this, 'callbackColumnStoreOnlineQty']
        ]);

        $this->addColumn('availability', [
            'header'    => $this->__('Availability'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => array(
                1 => $this->__('Yes'),
                0 => $this->__('No')
            ),
            'index'     => 'pickup_store_product_qty',
            'frame_callback' => [$this, 'callbackColumnOnlineAvailability'],
            'filter_condition_callback' => [$this, 'callbackFilterOnlineAvailability']
        ]);

        $this->addColumn('store_log', [
            'header'    => $this->__('Logs'),
            'align'     => 'left',
            'type'      => 'text',
            'width'     => '100px',
            'index'     => 'store_log',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnLog'],
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnVariations($value, $row, $column, $isExport)
    {
        $attributes = $this->parseGroupedData($row->getData('attributes'));
        $productsIds = $this->parseGroupedData($row->getData('products_ids'));
        $uniqueProductsIds = count(array_unique($productsIds)) > 1;

        $html = '<div class="m2ePro-variation-attributes" style="margin-left: 5px;">';
        if (!$uniqueProductsIds) {
            $url = $this->getUrl('catalog/product/edit', ['id' => reset($productsIds)]);
            $html .= '<a href="' . $url . '" target="_blank">';
        }
        foreach ($attributes as $attribute => $option) {
            $optionHtml = '<b>' . $this->getHelper('Data')->escapeHtml($attribute) .
                '</b>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option);

            if ($uniqueProductsIds) {
                $url = $this->getUrl('catalog/product/edit', ['id' => $productsIds[$attribute]]);
                $html .= '<a href="' . $url . '" target="_blank">' . $optionHtml . '</a><br/>';
            } else {
                $html .= $optionHtml . '<br/>';
            }
        }
        if (!$uniqueProductsIds) {
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    public function callbackColumnOnlineSku($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnStoreOnlineQty($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '' || $row->getData('is_added')) {
            $value =  $this->__('Adding to Store');
        }

        $inProgressHtml = '';
        if ((bool)$row->getData('is_in_processing')) {
            $inProgressLabel = $this->__('In Progress');
            $inProgressHtml = '&nbsp;<div style="color: #605fff">'.$inProgressLabel.'</div>';
        }

        return $value . $inProgressHtml;
    }

    public function callbackColumnOnlineAvailability($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $qty = $row->getData('store_online_qty');
        if (is_null($qty) || $row->getData('is_added')) {
            return $this->__('Adding to Store');
        }

        if ($qty <= 0) {
            return '<span style="color: red;">'.$this->__('Out Of Stock').'</span>';
        }

        return '<span>'.$this->__('In Stock').'</span>';
    }

    public function callbackColumnLog($value, $row, $column, $isExport)
    {
        $logIcon = $this->getViewLogIconHtml($row->getData('state_id'), $row->getData('id'));

        if (!empty($logIcon)) {
            $logIcon .= '<input type="hidden"
                                id="product_row_order_'.$row->getData('id').'"
                                value="'.$row->getData('id').'"
                                listing-product-pickup-store-state="'.$row->getData('state_id').'"/>';
        }

        return $logIcon;
    }

    public function getViewLogIconHtml($stateId, $columnId)
    {
        $stateId = (int)$stateId;
        $availableActionsId = array_keys($this->getAvailableActions());

        // Get last messages
        // ---------------------------------------

        $dbSelect = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\Log')
                                          ->getResource()->getMainTable(),
                ['action_id','action','type','description','create_date']
            )
            ->where('`account_pickup_store_state_id` = ?', $stateId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $this->resourceConnection->getConnection()->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        foreach ($logs as &$log) {
            $log['initiator'] = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing\Log\Grid\LastActions')->setData([
            'entity_id' => (int)$columnId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'EbayListingPickupStoreVariationGridObj.viewItemHelp',
            'hide_help_handler' => 'EbayListingPickupStoreVariationGridObj.hideItemHelp',
        ]);

        $pickupStoreState = $this->activeRecordFactory->getObjectLoaded('Ebay\Account\PickupStore\State', $stateId);

        $this->jsTranslator->addTranslations([
            'Log For SKU '.$stateId => $this->__('Log For SKU (%s%)', $pickupStoreState->getSku())
        ]);

        return $summary->toHtml();
    }

    private function getAvailableActions()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_UNKNOWN        => $this->__('Unknown'),
            \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_ADD_PRODUCT    => $this->__('Add'),
            \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_UPDATE_QTY     => $this->__('Update'),
            \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log::ACTION_DELETE_PRODUCT => $this->__('Delete'),
        ];
    }

    //########################################

    public function callbackFilterVariations($collection, $column)
    {
        $values = $column->getFilter()->getValue();

        if ($values == null && !is_array($values)) {
            return;
        }

        foreach ($values as $value) {
            if (is_array($value) && !empty($value['value'])) {
                $collection->addFieldToFilter(
                    'attributes',
                    ['regexp'=> $value['attr'].'=='.$value['value']]
                );
            }
        }
    }

    protected function callbackFilterOnlineAvailability($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $query = 'store_online_qty' . ((int)$value ? '>' : '<=' ) . ' 0';
        $collection->getSelect()->where($query);
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl(
            '*/ebay_listing_pickupStore/getProductsVariationsGrid',
            [
                'product_id' => $this->listingProductId,
                'pickup_store_id' => $this->pickupStoreId
            ]
        );
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->addUrls([
            '*/logGrid' => $this->getUrl('*/ebay_listing_pickupStore/logGrid/')
        ]);

        $this->js->addRequireJs([
            'elppg' => 'M2ePro/Ebay/Listing/PickupStore/Grid',
            'vpmvg' => 'M2ePro/Ebay/Listing/VariationProductManageVariationsGrid'
        ], <<<JS

        window.EbayListingPickupStoreVariationGridObj = new EbayListingPickupStoreGrid();
        EbayListingPickupStoreVariationGridObj.gridId = '{$this->getId()}';

        window.VariationsGridObj = new EbayListingVariationProductManageVariationsGrid(
            'ebayPickupStoreVariationProductGrid'
        );

        setTimeout(function() {
            if (typeof VariationsGridObj != 'undefined') {
                VariationsGridObj.afterInitPage();
            }
        }, 350);
JS
        );

        return parent::_toHtml();
    }

    //########################################

    private function getVariationsAttributes()
    {
        if (is_null($this->variationAttributes)) {
            $tableVariation = $this->activeRecordFactory->getObject('Listing\Product\Variation')
                ->getResource()->getMainTable();
            $tableOption = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
                ->getResource()->getMainTable();

            $select = $this->resourceConnection->getConnection()->select();
            $select->from(['mlpv' => $tableVariation], [])
                ->join(
                    ['mlpvo' => $tableOption],
                    'mlpvo.listing_product_variation_id = mlpv.id',
                    ['attribute']
                )
                ->where('listing_product_id = ?', (int)$this->listingProductId);

            $attributes = $this->resourceConnection->getConnection()->fetchCol($select);

            $this->variationAttributes = array_unique($attributes);
        }

        return $this->variationAttributes;
    }

    private function parseGroupedData($data)
    {
        $result = [];

        $variationData = explode('||', $data);
        foreach ($variationData as $variationAttribute) {
            $value = explode('==', $variationAttribute);
            $result[$value[0]] = $value[1];
        }

        return $result;
    }

    //########################################
}