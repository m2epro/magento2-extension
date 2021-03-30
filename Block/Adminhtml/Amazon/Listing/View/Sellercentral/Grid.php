<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    private $lockedDataCache = [];

    private $parentAsins;

    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    private $parentAndChildReviseScheduledCache = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setStoreId($this->listing->getStoreId());
        $collection->setListing($this->listing->getId());

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem();

        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'amazon_status'   => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            [
                'listing_id' => (int)$this->listing['id'],
                'status' => [
                    \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
                ]
            ]
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['alp' => $alpTable],
            'listing_product_id=id',
            [
                'general_id'                    => 'general_id',
                'search_settings_status'        => 'search_settings_status',
                'amazon_sku'                    => 'sku',
                'online_qty'                    => 'online_qty',
                'online_regular_price'          => 'online_regular_price',
                'online_regular_sale_price'     => 'IF(
                  `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                  `alp`.`online_regular_sale_price`,
                  NULL
                )',
                'online_regular_sale_price_start_date'   => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date'     => 'online_regular_sale_price_end_date',
                'online_business_price'          => 'online_business_price',
                'online_business_discounts'      => 'online_business_discounts',
                'is_repricing'                   => 'is_repricing',
                'is_afn_channel'                 => 'is_afn_channel',
                'is_general_id_owner'            => 'is_general_id_owner',
                'is_variation_parent'            => 'is_variation_parent',
                'variation_child_statuses'      => 'variation_child_statuses',
                'variation_parent_id'           => 'variation_parent_id',
                'defected_messages'              => 'defected_messages',
                'variation_parent_afn_state'       => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',
            ],
            '{{table}}.is_variation_parent = 0'
        );

        $collection->getSelect()->columns([
            'min_online_price' => new \Zend_Db_Expr('
                IF (
                    `alp`.`online_regular_price` IS NULL,
                    `alp`.`online_business_price`,
                    IF (
                        `alp`.`online_regular_sale_price` IS NOT NULL AND
                        `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                        `alp`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                        `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                        `alp`.`online_regular_sale_price`,
                        `alp`.`online_regular_price`
                    )
                )
            ')
        ]);

        $alprTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            ['malpr' => $alprTable],
            '(`alp`.`listing_product_id` = `malpr`.`listing_product_id`)',
            [
                'is_repricing_disabled' => 'is_online_disabled',
            ]
        );

        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $collection->getSelect()->join(
            ['lps' => $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
                ->getResource()->getMainTable()],
            'lps.listing_product_id=main_table.id',
            []
        );

        $collection->addFieldToFilter('is_variation_parent', 0);
        $collection->addFieldToFilter('variation_parent_id', ['in' => $this->getCollection()->getColumnValues('id')]);
        $collection->addFieldToFilter('lps.action_type', \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'variation_parent_id' => 'second_table.variation_parent_id',
                'count'               => new \Zend_Db_Expr('COUNT(lps.id)')
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        foreach ($collection->getItems() as $item) {
            $this->parentAndChildReviseScheduledCache[$item->getData('variation_parent_id')] = true;
        }

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'   => $this->__('Product ID'),
            'align'    => 'right',
            'width'    => '100px',
            'type'     => 'number',
            'index'    => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId'
        ]);

        $this->addColumn('name', [
            'header'         => $this->__('Product Title / Product SKU'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'name',
            'filter_index'   => 'name',
            'escape'         => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('amazon_sku', [
            'header'       => $this->__('SKU'),
            'align'        => 'left',
            'width'        => '150px',
            'type'         => 'text',
            'index'        => 'amazon_sku',
            'filter_index' => 'amazon_sku',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Sku'
        ]);

        $this->addColumn('general_id', [
            'header'         => $this->__('ASIN / ISBN'),
            'align'          => 'left',
            'width'          => '140px',
            'type'           => 'text',
            'index'          => 'general_id',
            'filter_index'   => 'general_id',
            'filter'         => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\GeneralId',
            'frame_callback' => [$this, 'callbackColumnGeneralId'],
            'filter_condition_callback' => [$this, 'callbackFilterGeneralId']
        ]);

        $this->addColumn('online_qty', [
            'header'       => $this->__('QTY'),
            'align'        => 'right',
            'width'        => '70px',
            'type'         => 'number',
            'index'        => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Qty',
            'filter'       => 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty',
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $priceColumn = [
            'header'         => $this->__('Price'),
            'align'          => 'right',
            'width'          => '110px',
            'type'           => 'number',
            'index'          => 'min_online_price',
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'account_id'     => $this->listing->getAccountId(),
            'renderer'       => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Price',
            'filter_index'   => 'min_online_price',
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ];

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            $this->listing->getAccount()->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', [
            'header'   => $this->__('Status'),
            'width'    => '155px',
            'index'    => 'amazon_status',
            'filter_index' => 'amazon_status',
            'type'     => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus']
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        // ---------------------------------------

        // Set mass-action
        // ---------------------------------------
        $groups = [
            'actions'            => $this->__('Actions'),
            'edit_fulfillment'   => $this->__('Fulfillment')
        ];

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled()) {
            $groups['edit_repricing'] = $this->__('Repricing Tool');
        }

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('revise', [
            'label'    => $this->__('Revise Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label'    => $this->__('Relist Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label'    => $this->__('Stop Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label'    => $this->__('Remove from Channel & Listing'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('switchToAfn', [
            'label'    => $this->__('Switch to AFN'),
            'url'      => ''
        ], 'edit_fulfillment');

        $this->getMassactionBlock()->addItem('switchToMfn', [
            'label'    => $this->__('Switch to MFN'),
            'url'      => ''
        ], 'edit_fulfillment');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->listing->getAccount();

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            $account->getChildObject()->isRepricing()) {
            $this->getMassactionBlock()->addItem('showDetails', [
                'label' => $this->__('Show Details'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ], 'edit_repricing');

            $this->getMassactionBlock()->addItem('addToRepricing', [
                'label' => $this->__('Add Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ], 'edit_repricing');

            $this->getMassactionBlock()->addItem('editRepricing', [
                'label' => $this->__('Edit Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ], 'edit_repricing');

            $this->getMassactionBlock()->addItem('removeFromRepricing', [
                'label' => $this->__('Remove Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ], 'edit_repricing');
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $sku = $this->modelFactory->getObject('Magento\Product')
            ->setProductId($row->getData('entity_id'))
            ->getSku();

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if (!$listingProduct->getChildObject()->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            $typeModel = $variationManager->getTypeModel();

            $productOptions = $typeModel->getProductOptions();
            $channelOptions = $typeModel->getChannelOptions();

            $parentTypeModel = $variationManager->getTypeModel()->getParentTypeModel();

            $virtualProductAttributes = array_keys($parentTypeModel->getVirtualProductAttributes());
            $virtualChannelAttributes = array_keys($parentTypeModel->getVirtualChannelAttributes());

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
            $parentAmazonListingProduct = $typeModel->getParentListingProduct()->getChildObject();

            $matchedAttributes = $parentAmazonListingProduct->getVariationManager()
                ->getTypeModel()
                ->getMatchedAttributes();

            if (!empty($matchedAttributes) && !empty($channelOptions)) {
                $sortedOptions = [];

                foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {
                    $sortedOptions[$amazonAttr] = $channelOptions[$amazonAttr];
                }

                $channelOptions = $sortedOptions;
            }

            $value .= '<div style="font-weight:bold;font-size: 11px;color: grey;margin-left: 7px;margin-top: 5px;">'.
                $this->__('Magento Variation') . '</div>';
            $value .= '<div style="font-size: 11px; color: grey; margin-left: 24px">';
            foreach ($productOptions as $attribute => $option) {
                $style = '';
                if (in_array($attribute, $virtualProductAttributes)) {
                    $style = 'border-bottom: 2px dotted grey';
                }

                !$option && $option = '--';
                $value .= '<span style="' . $style . '"><b>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</b>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '</span><br/>';
            }
            $value .= '</div>';

            $value .= '<div style="font-weight:bold;font-size: 11px;color: grey;margin-left: 7px;margin-top: 5px;">'.
                $this->__('Amazon Variation') . '</div>';
            $value .= '<div style="font-size: 11px; color: grey; margin-left: 24px">';
            foreach ($channelOptions as $attribute => $option) {
                $style = '';
                if (in_array($attribute, $virtualChannelAttributes)) {
                    $style = 'border-bottom: 2px dotted grey';
                }

                !$option && $option = '--';
                $value .= '<span style="' . $style . '"><b>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</b>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '</span><br/>';
            }
            $value .= '</div>';

            return $value;
        }

        $productOptions = [];
        if ($listingProduct->getChildObject()->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $productOptions = $listingProduct->getChildObject()->getVariationManager()
                ->getTypeModel()->getProductOptions();
        }

        $value .= '<div style="font-size: 11px; color: grey; margin-left: 7px"><br/>';
        foreach ($productOptions as $attribute => $option) {
            !$option && $option = '--';
            $value .= '<b>' . $this->getHelper('Data')->escapeHtml($attribute) .
                '</b>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '<br/>';
        }
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        $url = $this->getHelper('Component\Amazon')->getItemUrl($value, $this->listing->getMarketplaceId());
        $parentAsinHtml = '';
        $variationParentId = $row->getData('variation_parent_id');
        if (!empty($variationParentId)) {
            $parentAsinHtml = '<br/><span style="display: block;
                                                margin-bottom: 5px;
                                                font-size: 10px;
                                                color: grey;">'.
                $this->__(
                    'child ASIN/ISBN<br/>of parent %parent_asin%',
                    $this->getParentAsin($row->getData('id'))
                ) . '</span>';
        }

        $generalIdOwnerHtml = '';
        if ($row->getData('is_general_id_owner') == 1) {
            $generalIdOwnerHtml = '<span style="font-size: 10px; color: grey; display: block;">'.
                                   $this->__('creator of ASIN/ISBN').
                                  '</span>';
        }
        return <<<HTML
<a href="{$url}" target="_blank">{$value}</a>{$parentAsinHtml}{$generalIdOwnerHtml}
HTML;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('amazon_status')) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN:
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $value = '<span style="color: gray;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $value = '<span style="color: orange; font-weight: bold;">'.$value.'</span>';
                break;

            default:
                break;
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\ViewLogIcon\Listing $viewLogIcon */
        $viewLogIcon = $this->createBlock('Amazon_Grid_Column_Renderer_ViewLogIcon_Listing');
        $value .= $viewLogIcon->render($row);

        $scheduledActionsCollection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
            ->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', $row->getData('id'));

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionsCollection->getFirstItem();

        switch ($scheduledAction->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $value .= '<br/><span style="color: #605fff">[List is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $value .= '<br/><span style="color: #605fff">[Relist is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $reviseParts = [];

                $additionalData = $scheduledAction->getAdditionalData();
                if (!empty($additionalData['configurator']) &&
                    !isset($this->parentAndChildReviseScheduledCache[$row->getData('id')])) {
                    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
                    $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
                    $configurator->setUnserializedData($additionalData['configurator']);

                    if ($configurator->isIncludingMode()) {
                        if ($configurator->isQtyAllowed()) {
                            $reviseParts[] = 'QTY';
                        }

                        if ($configurator->isRegularPriceAllowed() || $configurator->isBusinessPriceAllowed()) {
                            $reviseParts[] = 'Price';
                        }

                        if ($configurator->isDetailsAllowed()) {
                            $reviseParts[] = 'Details';
                        }

                        if ($configurator->isImagesAllowed()) {
                            $reviseParts[] = 'Images';
                        }
                    }
                }

                if (!empty($reviseParts)) {
                    $value .= '<br/><span style="color: #605fff">[Revise of '.implode(', ', $reviseParts)
                              .' is Scheduled...]</span>';
                } else {
                    $value .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $value .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $value .= '<br/><span style="color: #605fff">[Delete is Scheduled...]</span>';
                break;

            default:
                break;
        }

        $tempLocks = $this->getLockedData($row);
        $tempLocks = $tempLocks['object_locks'];

        foreach ($tempLocks as $lock) {
            switch ($lock->getTag()) {
                case 'list_action':
                    $value .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
                    break;

                case 'relist_action':
                    $value .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $value .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $value .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $value .= '<br/><span style="color: #605fff">[Stop And Remove in Progress...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $value .= '<br/><span style="color: #605fff">[Remove in Progress...]</span>';
                    break;

                case 'switch_to_afn_action':
                    $value .= '<br/><span style="color: #605fff">[Switch to AFN in Progress...]</span>';
                    break;

                case 'switch_to_mfn_action':
                    $value .= '<br/><span style="color: #605fff">[Switch to MFN in Progress...]</span>';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'amazon_sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%']
            ]
        );
    }

    protected function callbackFilterGeneralId($collection, $column)
    {
        $inputValue = $column->getFilter()->getValue('input');
        if ($inputValue !== null) {
            $collection->addFieldToFilter('general_id', ['like' => '%' . $inputValue . '%']);
        }

        $selectValue = $column->getFilter()->getValue('select');
        if ($selectValue !== null) {
            $collection->addFieldToFilter('is_general_id_owner', $selectValue);
        }
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_qty <= ' . (int)$value['to'];
        }

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }

            if ((int)$value['afn'] == 1) {
                $where .= 'is_afn_channel = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_AFN_STATE_PARTIAL;
                $where .= "(is_afn_channel = 0 OR variation_parent_afn_state = {$partialFilter})";
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) || isset($value['to'])) {
            if (isset($value['from']) && $value['from'] != '') {
                $condition = 'online_regular_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'online_regular_price <= \''.(float)$value['to'].'\'';
            }

            $condition = '(' . $condition . ' AND
            (
                online_regular_price IS NOT NULL AND
                ((online_regular_sale_price_start_date IS NULL AND
                online_regular_sale_price_end_date IS NULL) OR
                online_regular_sale_price IS NULL OR
                online_regular_sale_price_start_date > CURRENT_DATE() OR
                online_regular_sale_price_end_date < CURRENT_DATE())
            )) OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'online_regular_sale_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'online_regular_sale_price <= \''.(float)$value['to'].'\'';
            }

            $condition .= ' AND
            (
                online_regular_price IS NOT NULL AND
                (online_regular_sale_price_start_date IS NOT NULL AND
                online_regular_sale_price_end_date IS NOT NULL AND
                online_regular_sale_price IS NOT NULL AND
                online_regular_sale_price_start_date < CURRENT_DATE() AND
                online_regular_sale_price_end_date > CURRENT_DATE())
            )) OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'online_business_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'online_business_price <= \''.(float)$value['to'].'\'';
            }

            $condition .= ' AND (online_regular_price IS NULL))';
        }

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== '')) {
            if (!empty($condition)) {
                $condition = '(' . $condition . ') OR ';
            }

            if ((int)$value['is_repricing'] == 1) {
                $condition .= 'is_repricing = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL;
                $condition .= "(is_repricing = 0 OR variation_parent_repricing_state = {$partialFilter})";
            }
        }

        $collection->getSelect()->having($condition);
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    public function getEmptyText()
    {
        return $this->__(
            'Only Simple and Child Products listed on Amazon will be shown in Seller Сentral View Mode.'
        );
    }

    //########################################

    private function getLockedData($row)
    {
        $listingProductId = $row->getData('id');
        if (!isset($this->lockedDataCache[$listingProductId])) {
            $objectLocks = $this->activeRecordFactory->getObjectLoaded('Listing\Product', $listingProductId)
                ->getProcessingLocks();
            $tempArray = [
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            ];
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    //########################################

    private function getParentAsin($childId)
    {
        if ($this->parentAsins === null) {
            $connection = $this->resourceConnection->getConnection();
            $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon_Listing_Product')
                ->getResource()->getMainTable();

            $select = $connection->select();
            $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id','variation_parent_id'])
                ->where('listing_product_id IN (?)', $this->getCollection()->getAllIds())
                ->where('variation_parent_id IS NOT NULL');

            $parentIds = $connection->fetchPairs($select);

            $select = $connection->select();
            $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id', 'general_id'])
                ->where('listing_product_id IN (?)', $parentIds);

            $parentAsins = $connection->fetchPairs($select);

            $this->parentAsins = [];
            foreach ($parentIds as $childId => $parentId) {
                $this->parentAsins[$childId] = $parentAsins[$parentId];
            }
        }

        return $this->parentAsins[$childId];
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}
