<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $ebayFactory;
    protected $magentoProductCollectionFactory;
    protected $resourceConnection;
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('temp_data');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingPickupStoreGrid'.$this->listing->getId());
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $listingData = $this->listing->getData();

        // ---------------------------------------
        // Get collection
        // ---------------------------------------
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        $collection->getSelect()->columns('entity_id AS category_product_id');
        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $collection->joinTable(
            ['lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
            'product_id=entity_id',
            [
                'lp_id' => 'id',
                'ebay_status' => 'status',
                'component_mode' => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            '{{table}}.listing_id='.(int)$listingData['id']
        );
        $collection->joinTable(
            ['elp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable()],
            'listing_product_id=lp_id',
            [
                'listing_product_id'    => 'listing_product_id',
                'end_date'              => 'end_date',
                'start_date'            => 'start_date',
                'online_title'          => 'online_title',
                'online_sku'            => 'online_sku',
                'available_qty'         => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'ebay_item_id'          => 'ebay_item_id',
                'online_category'       => 'online_category',
                'online_qty_sold'       => 'online_qty_sold',
                'online_bids'           => 'online_bids',
                'template_category_id'  => 'template_category_id',
            ]
        );
        $collection->joinTable(
            ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            'id=ebay_item_id',
            ['item_id' => 'item_id'],
            NULL,
            'left'
        );

        $collection->getSelect()->join(
            ['elpps' => $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')
                             ->getResource()->getMainTable()],
            'elp.listing_product_id=elpps.listing_product_id',
            [
                'id' => 'id',
                'account_pickup_store_id' => 'account_pickup_store_id'
            ]
        );

        $collection->getSelect()->joinLeft(
            ['meaps' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore')
                             ->getResource()->getMainTable()],
            'elpps.account_pickup_store_id = meaps.id',
            [
                'pickup_store_id' => 'id',
                'store_name'  => 'name',
                'location_id' => 'location_id',
                'phone'       => 'phone',
                'postal_code' => 'postal_code',
                'country'     => 'country',
                'region'      => 'region',
                'city'        => 'city',
                'address_1'   => 'address_1',
                'address_2'   => 'address_2'
            ]
        );

        $collection->getSelect()->joinLeft(
            ['meapss' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')
                              ->getResource()->getMainTable()],
            'meapss.account_pickup_store_id = meaps.id AND meapss.sku = elp.online_sku',
            [
                'pickup_store_product_qty' => 'IF(
                    (`meapss`.`online_qty` IS NULL),
                    `t`.`variations_qty`,
                    `meapss`.`online_qty`
                )',
                'state_id' => 'id',
                'is_in_processing' => 'is_in_processing',
                'is_added' => 'is_added',
                'is_deleted' => 'is_deleted'
            ]
        );

        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    `mlpv`.`listing_product_id`,
                    `meapss`.`account_pickup_store_id`,
                    SUM(`meapss`.`online_qty`) as `variations_qty`,
                    SUM(`meapss`.`is_in_processing`) as `variations_processing`,
                    SUM(`meapss`.`is_added`) as `variations_added`,
                    COUNT(`meapss`.`is_in_processing`) as `count_variations_in_state`
                FROM `'. $this->activeRecordFactory->getObject('Listing\Product\Variation')
                              ->getResource()->getMainTable() .'` AS `mlpv`
                INNER JOIN `' .
                $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')
                                          ->getResource()->getMainTable().'` AS `melpv`
                    ON (`mlpv`.`id` = `melpv`.`listing_product_variation_id`)
                INNER JOIN `' .
                $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')
                                          ->getResource()->getMainTable().'` AS meapss
                    ON (meapss.sku = melpv.online_sku)
                WHERE `melpv`.`status` != ' . \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED . '
                GROUP BY `meapss`.`account_pickup_store_id`, `mlpv`.`listing_product_id`
            )'),
            'elp.listing_product_id=t.listing_product_id AND t.account_pickup_store_id = meaps.id',
            [
                'variations_qty' => 'variations_qty',
                'variations_processing' => 'variations_processing',
                'variations_added' => 'variations_added',
                'count_variations_in_state' => 'count_variations_in_state',
            ]
        );

        $collection->getSelect()->where(
            'lp.listing_id = ?',(int)$listingData['id']);
        // ---------------------------------------

        if ($this->listing) {
            $collection->setStoreId($this->listing['store_id']);
        }

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ?
                $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'pickup_store_product_qty') {
                $collection->getSelect()->order('pickup_store_product_qty ' . strtoupper($column->getDir()));
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        return $this;
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnListingProductId'],
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'online_title',
            'width'     => '550px',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('account_pickup_store_id', [
            'header'    => $this->__('Store Details'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'id',
            'width'     => '500px',
            'frame_callback' => [$this, 'callbackColumnPickupStore'],
            'filter_condition_callback' => [$this, 'callbackFilterPickupStore']
        ]);

        $this->addColumn('ebay_item_id', [
            'header'    => $this->__('Item ID'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'text',
            'index'     => 'item_id',
            'frame_callback' => [$this, 'callbackColumnEbayItemId']
        ]);

        $this->addColumn('pickup_store_product_qty', [
            'header'    => $this->__('Available QTY'),
            'align'     => 'left',
            'width'     => '110px',
            'type'      => 'number',
            'index'     => 'pickup_store_product_qty',
            'frame_callback' => [$this, 'callbackColumnOnlineQty'],
            'filter_condition_callback' => [$this, 'callbackFilterOnlineQty']
        ]);

        $this->addColumn('availability', [
            'header'    => $this->__('Availability'),
            'align'     => 'right',
            'width'     => '110px',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => [
                1 => $this->__('Yes'),
                0 => $this->__('No')
            ],
            'index'     => 'pickup_store_product_qty',
            'frame_callback' => [$this, 'callbackColumnOnlineAvailability'],
            'filter_condition_callback' => [$this, 'callbackFilterOnlineAvailability']
        ]);

        $this->addColumn('delete_action', [
            'header'    => $this->__('Logs'),
            'align'     => 'left',
            'type'      => 'action',
            'index'     => 'delete_action',
            'width'     => '100px',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnLog'],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem('unassign', [
            'label'    => $this->__('Unassign'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        return parent::_prepareMassaction();
    }

    protected function _prepareMassactionColumn()
    {
        $columnId = 'massaction';
        $massactionColumn = $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Grid\Column')
            ->setData(
                [
                    'index' => $this->getMassactionIdField(),
                    'filter_index' => $this->getMassactionIdFilter(),
                    'type' => 'massaction',
                    'name' => $this->getMassactionBlock()->getFormFieldName(),
                    'is_system' => true,
                    'header_css_class' => 'col-select',
                    'column_css_class' => 'col-select',
                    'filter_condition_callback' => [$this, 'callbackFilterCheckboxes']
                ]
            );

        if ($this->getNoFilterMassactionColumn()) {
            $massactionColumn->setData('filter', false);
        }

        $massactionColumn->setSelected($this->getMassactionBlock()->getSelected())->setGrid($this)->setId($columnId);

        $this->getColumnSet()->insert(
            $massactionColumn,
            count($this->getColumnSet()->getColumns()) + 1,
            false,
            $columnId
        );
        return $this;
    }

    //########################################

    public function callbackColumnListingProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$value;

        $url = $this->getUrl('catalog/product/edit', ['id' => $productId]);
        $htmlWithoutThumbnail = '<a href="' . $url . '" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()
            ->getGroupValue('/view/','show_products_thumbnails');

        if (!$showProductsThumbnails) {
            return $htmlWithoutThumbnail;
        }

        $storeId = $this->getStoreId();

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $thumbnail = $magentoProduct->getThumbnailImage();
        if (is_null($thumbnail)) {
            return $htmlWithoutThumbnail;
        }

        $thumbnailUrl = $thumbnail->getUrl();

        return <<<HTML
        <a href="{$url}" target="_blank">
            {$productId}
            <div style="margin-top: 5px"><img src="{$thumbnailUrl}" /></div>
        </a>
HTML;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $row->getName();
        $onlineTitle = $row->getData('online_title');
        !empty($onlineTitle) && $title = $onlineTitle;
        $title = $this->getHelper('Data')->escapeHtml($title);

        $valueHtml = '<span class="product-title-value">' . $title . '</span>';

        if (is_null($sku = $row->getData('sku'))) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                        ->setProductId($row->getData('catalog_product_id'))
                        ->getSku();
        }

        $onlineSku = $row->getData('online_sku');
        !empty($onlineSku) && $sku = $onlineSku;

        $valueHtml .= '<br/><strong>' . $this->__('SKU') . ':</strong>&nbsp;'
                      . $this->getHelper('Data')->escapeHtml($sku);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product', $row->getData('listing_product_id')
        );

        if (!$listingProduct->getChildObject()->isVariationsReady()) {
            return '<div style="padding: 2px 4px;">'.$valueHtml.'</div>';
        }

        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        $productAttributes = array_keys($additionalData['variations_sets']);

        $valueHtml .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 10px 7px">';
        $valueHtml .= implode(', ', $productAttributes);
        $valueHtml .= '</div>';

        $linkContent = $this->__('Show Variations');
        $vpmt = $this->__('Variations of &quot;%s%&quot; ', $title);
        $vpmt = addslashes($vpmt);

        $itemId = $this->getData('item_id');

        if (!empty($itemId)) {
            $vpmt .= '('. $itemId .')';
        }

        $linkTitle = $this->__('Open Manage Variations Tool');
        $listingProductId = (int)$row->getData('listing_product_id');
        $pickupStoreId = $row->getData('pickup_store_id');

        $valueHtml .= <<<HTML
<div style="float: left; margin: 0 0 0 7px">
    <a href="javascript:"
       onclick="EbayListingPickupStoreGridObj.openVariationPopUp({$listingProductId}, '{$vpmt}', '{$pickupStoreId}')"
       title="{$linkTitle}">{$linkContent}</a>&nbsp;
</div>
HTML;

        return '<div style="padding: 0 4px;">'.$valueHtml.'</div>';
    }

    public function callbackColumnEbayItemId($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        $listingData = $this->listing->getData();

        $url = $this->getUrl('*/ebay_listing/gotoEbay/', [
            'item_id' => $value,
            'account_id' => $listingData['account_id'],
            'marketplace_id' => $listingData['marketplace_id']
        ]);
        return '<a href="' . $url . '" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnOnlineQty($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $sku = $row->getData('online_sku');
        if (empty($sku)) {
            return $this->__('Adding to Store');
        }

        $qty = $row->getData('pickup_store_product_qty');
        if (is_null($qty) || $row->getData('is_added')) {
            $qty = $this->__('Adding to Store');
        }

        $variationsAdded = $row->getData('variations_added');
        $countVariationsInState = $row->getData('count_variations_in_state');

        if (!is_null($countVariationsInState) && !is_null($variationsAdded) &&
            $countVariationsInState == $variationsAdded) {
            $qty = $this->__('Adding to Store');
        }

        $inProgressHtml = '';
        if ((bool)$row->getData('is_in_processing') || (bool)$row->getData('variations_processing')) {
            $inProgressLabel = $this->__('In Progress');
            $inProgressHtml = '&nbsp;<div style="color: #605fff">'.$inProgressLabel.'</div>';
        }

        return $qty . $inProgressHtml;
    }

    public function callbackColumnOnlineAvailability($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $qty = $row->getData('pickup_store_product_qty');
        $variationsAdded = $row->getData('variations_added');
        $countVariationsInState = $row->getData('count_variations_in_state');

        if (is_null($qty) || $row->getData('is_added') || (!is_null($countVariationsInState) &&
            !is_null($variationsAdded) && $countVariationsInState == $variationsAdded)) {
            return $this->__('Adding to Store');
        }

        if ($qty <= 0) {
            return '<span style="color: red;">'.$this->__('Out Of Stock').'</span>';
        }

        return '<span>'.$this->__('In Stock').'</span>';
    }

    public function callbackColumnPickupStore($value, $row, $column, $isExport)
    {
        $name = $row->getData('store_name');
        $locationId = $row->getData('location_id');
        $countryCode = $row->getData('country');

        $country = $countryCode;
        $countries = $this->getHelper('Magento')->getCountries();

        foreach ($countries as $country) {
            if ($country['value'] == $countryCode) {
                $country = $country['label'];
                break;
            }
        }

        $region = $row->getData('region');
        $city = $row->getData('city');
        $address1 = $row->getData('address_1');
        $address2 = $row->getData('address_2');

        $addressHtml = "{$country}, {$region}, {$city} <br/> {$address1}";
        if (!empty($address2)) {
            $addressHtml .= ',' . $address2;
        }
        $addressHtml .= ', ' .$row->getData('postal_code');

        return <<<HTML
        <style>
            .column-pickup-store {
                list-style: none;
                padding: 2px 4px;
            }

            .column-pickup-store li:nth-child(2) {
                margin-bottom: 16px;
            }
        </style>
        <div>
            <ul class="column-pickup-store">
                <li><span>{$name}</span></li>
                <li><strong>{$this->__('Location ID')}:</strong>&nbsp;<span>{$locationId}</span></li>
                <li>
                    <strong>{$this->__('Address')}:</strong><br/>
                    <div>
                        {$addressHtml}
                    </div>
                </li>
            </ul>
        </div>
HTML
;
    }

    public function callbackColumnLog($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product',$row->getData('listing_product_id')
        );

        if ($listingProduct->getChildObject()->isVariationsReady()) {
            return '';
        }

        $logIcon = $this->getViewLogIconHtml($row->getData('state_id'), $row->getData('id'));

        if (!empty($logIcon)) {
            $logIcon .= '<input type="hidden"
                                id="product_row_order_'.$row->getData('id').'"
                                value="'.$row->getData('id').'"
                                listing-product-pickup-store-state="'.$row->getData('state_id').'"/>';
        }

        return $logIcon;
    }

    // ---------------------------------------

    protected function callbackFilterCheckboxes($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $selected = $column->getData('selected');

        if ($value == null || $value == 0 && empty($selected)) {
            return;
        }

        if ($value == 1 && empty($selected)) {
            $selected = [0];
        }

        $query = 'elpps.id ' . ((int)$value ? 'IN' : 'NOT IN' ) . '('.implode(',', $selected).')';
        $collection->getSelect()->where($query);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'online_sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%'],
                ['attribute'=>'online_title','like'=>'%'.$value.'%']
            ]
        );
    }

    protected function callbackFilterOnlineQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $from = '';
        if (isset($value['from'])) {
            $from = '(meapss.online_qty >= ' . (int)$value['from']
                    . ' OR t.variations_qty >= ' . (int)$value['from'] .')';
        }

        $to = '';
        if (isset($value['to'])) {
            $to = '(meapss.online_qty <= ' . (int)$value['to']
                  . ' OR t.variations_qty <= ' . (int)$value['to'] .')';
        }

        $collection->getSelect()->where(
            $from . (!empty($from) && !empty($to) ? ' AND ' : '') . $to
        );
    }

    protected function callbackFilterOnlineAvailability($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'meapss.online_qty ' . ((int)$value ? '>' : '<=' ) . ' 0' .
            ' OR t.variations_qty ' . ((int)$value ? '>' : '<=' ) . ' 0'
        );
    }

    protected function callbackFilterPickupStore($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $countryCodes = [];
        $countries = $this->getHelper('Magento')->getCountries();

        foreach ($countries as $country) {
            $pos = strpos(strtolower($country['label']), strtolower($value));
            if ($pos !== false) {
                $countryCodes[] = $country['value'];
            }
        }

        $countryCodes = !empty($countryCodes) ? $countryCodes : [$value];
        $countryWhere = " OR meaps.country LIKE '%" . implode("%' OR meaps.country LIKE '%", $countryCodes) . "%' ";
        $collection->getSelect()->where(
            "meaps.name LIKE '%{$value}%'
            OR meaps.location_id LIKE '%{$value}%'
            {$countryWhere}
            OR meaps.region LIKE '%{$value}%'
            OR meaps.city LIKE '%{$value}%'
            OR meaps.address_1 LIKE '%{$value}%'
            OR meaps.address_2 LIKE '%{$value}%'
            OR meaps.postal_code LIKE '%{$value}%'"
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_pickupStore/index', [
            '_current' => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

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
                ['id', 'action_id','action','type','description','create_date']
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
            'view_help_handler' => 'EbayListingPickupStoreGridObj.viewItemHelp',
            'hide_help_handler' => 'EbayListingPickupStoreGridObj.hideItemHelp',
        ]);

        $pickupStoreState = $this->activeRecordFactory->getObjectLoaded('Ebay\Account\PickupStore\State', $stateId);

        $this->jsTranslator->addTranslations([
            'Log For SKU '. $stateId => $this->__('Log For SKU (%s%)', $pickupStoreState->getSku())
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

    protected function _toHtml()
    {
        $allIds = [];
        foreach ($this->getCollection()->getItems() as $item) {
            $allIds[] = $item['id'];
        }

        $allIdsStr  = implode(',', $allIds);

        if ($this->getRequest()->isXmlHttpRequest()) {

            $this->js->add(
                <<<JS
                EbayListingPickupStoreGridObj.afterInitPage();
                EbayListingPickupStoreGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');
JS
            );

            return parent::_toHtml();
        }

        $this->jsUrl->addUrls([
            '*/assign' => $this->getUrl('*/ebay_listing_pickupStore/assign/'),
            '*/unassign' => $this->getUrl('*/ebay_listing_pickupStore/unassign/'),
            '*/pickupStore' => $this->getUrl('*/ebay_listing_pickupStore/index/', [
                'id' => $this->listing->getId()
            ]),
            'variationProduct' => $this->getUrl(
                '*/ebay_listing_pickupStore/getProductsVariations/'
            ),
            '*/productsStep' => $this->getUrl('*/ebay_listing_pickupStore/productsStep/', [
                'id' => $this->listing->getId()
            ]),
            '*/storesStep' => $this->getUrl('*/ebay_listing_pickupStore/storesStep/', [
                'id' => $this->listing->getId()
            ]),
            '*/logGrid' => $this->getUrl('*/ebay_listing_pickupStore/logGrid/'),
        ]);

        $this->jsTranslator->addTranslations([
            'task_completed_message' => $this->__('Task completed. Please wait ...'),

            'task_completed_success_message' => $this->__('Stores have been successfully unassigned.'),

            'task_completed_warning_message' => $this->__('You should provide correct parameters.'),
            'task_completed_error_message' => $this->__('"%task_title%" task has completed with errors.'),

            'sending_data_message' => $this->__('Unassign %product_title% Product(s) data.'),
            'Assign Products to Stores' => $this->__('Assign Products to Stores'),
            'Unassign Product(s) from Stores' => $this->__('Unassign Product(s) from Stores'),
            'View Full Product Log' => $this->__('View Full Product Log'),
            'Back' => $this->__('Back'),
            'Complete' => $this->__('Complete'),
            'Log For SKU' => $this->__('Log For SKU')
        ]);

        $this->css->add(
            "<style>
                #{$this->getId()}_table .massaction-checkbox{
                    display: block;
                    margin: 2px auto 2px;
                }
            </style>"
        );
        $this->js->addRequireJs([
            'jQuery' => 'jquery',
            'elppg' => 'M2ePro/Ebay/Listing/PickupStore/Grid'
        ], <<<JS

            window.EbayListingPickupStoreGridObj = new EbayListingPickupStoreGrid(
                '{$this->getId()}',
                {$this->listing['id']}
            );
            EbayListingPickupStoreGridObj.getGridMassActionObj().setUseSelectAll(false);
            EbayListingPickupStoreGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');
            EbayListingPickupStoreGridObj.afterInitPage();

JS
        );

        return parent::_toHtml();
    }

    //########################################
}