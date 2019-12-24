<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Settings\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $walmartFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
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
        $this->setId('walmartListingViewSettingsGrid'.$this->listing['id']);
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

        if ($this->isFilterOrSortByPriceIsUsed(null, 'walmart_online_price')) {
            $collection->setIsNeedToUseIndexerParent(true);
        }

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
                'walmart_status'   => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            [
                'listing_id' => (int)$this->listing['id']
            ]
        );

        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['wlp' => $wlpTable],
            'listing_product_id=id',
            [
                'template_category_id'           => 'template_category_id',
                'variation_child_statuses'       => 'variation_child_statuses',
                'walmart_sku'                    => 'sku',
                'gtin'                           => 'gtin',
                'upc'                            => 'upc',
                'ean'                            => 'ean',
                'isbn'                           => 'isbn',
                'wpid'                           => 'wpid',
                'channel_url'                    => 'channel_url',
                'item_id'                        => 'item_id',
                'online_qty'                     => 'online_qty',
                'online_price'                   => 'online_price',
                'is_variation_parent'            => 'is_variation_parent',
                'is_details_data_changed'        => 'is_details_data_changed',
                'is_online_price_invalid'        => 'is_online_price_invalid',
                'online_start_date'              => 'online_start_date',
                'online_end_date'                => 'online_end_date',
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        $tdTable = $this->activeRecordFactory->getObject('Walmart_Template_Category')->getResource()->getMainTable();
        $collection->joinTable(
            ['wtc' => $tdTable],
            'id=template_category_id',
            [
                'template_category_title' => 'title'
            ],
            null,
            'left'
        );

        if ($collection->isNeedUseIndexerParent()) {
            $collection->joinIndexerParent();
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId']
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('sku', [
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'walmart_sku',
            'filter_index' => 'walmart_sku',
            'frame_callback' => [$this, 'callbackColumnWalmartSku']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin'],
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('category_template', [
            'header' => $this->__('Category Policy'),
            'align' => 'left',
            'width' => '250px',
            'type' => 'text',
            'index' => 'template_category_title',
            'filter_index' => 'template_category_title',
            'frame_callback' => [$this, 'callbackColumnTemplateCategory']
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'field' => 'id',
            'no_link'  => true,
            'group_order' => $this->getGroupOrder(),
            'actions'     => $this->getColumnActionsItems()
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function getGroupOrder()
    {
        $groups = [
            'edit_template_category' => $this->__('Category Policy')
        ];

        return $groups;
    }

    protected function getColumnActionsItems()
    {
        $actions = [
            'assignTemplateCategory' => [
                'caption' => $this->__('Use Another Category Policy'),
                'group'   => 'edit_template_category',
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.actions[\'changeTemplateCategoryIdAction\']'
            ],
        ];

        return $actions;
    }

    //########################################

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
            'category_policy' => $this->__('Category Policy'),
            'other'           => $this->__('Other'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('changeTemplateCategoryId', [
            'label'    => $this->__('Use Another'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'category_policy');

        $this->getMassactionBlock()->addItem('moving', [
            'label'    => $this->__('Move Item(s) to Another Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'other');

        $this->getMassactionBlock()->addItem('duplicate', [
            'label'    => $this->__('Duplicate'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'other');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if (!$listingProduct->getChildObject()->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $productAttributes = (array)$variationManager->getTypeModel()->getProductAttributes();
            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $attributesStr = '';
            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {
                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';
                    } elseif (in_array($attribute, array_keys($virtualChannelAttributes))) {
                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';
                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }
            $value .= $attributesStr;

            return $value;
        }

        $productOptions = $variationManager->getTypeModel()->getProductOptions();

        if (!empty($productOptions)) {
            $value .= '<div style="font-size: 11px; color: grey; margin-left: 7px"><br/>';
            foreach ($productOptions as $attribute => $option) {
                !$option && $option = '--';
                $value .= '<strong>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnWalmartSku($value, $row, $column, $isExport)
    {
        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        }

        return $value;
    }

    // ---------------------------------------

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        if (empty($gtin)) {
            return $this->__('N/A');
        }

        $gtinHtml = $this->getHelper('Data')->escapeHtml($gtin);
        $channelUrl = $row->getData('channel_url');

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = <<<HTML
<div class="walmart-identifiers-gtin">{$gtinHtml}</div>
HTML;

        $identifiers = [
            'UPC'        => $row->getData('upc'),
            'EAN'        => $row->getData('ean'),
            'ISBN'       => $row->getData('isbn'),
            'Walmart ID' => $row->getData('wpid'),
            'Item ID'    => $row->getData('item_id')
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (($row->getData('upc') || $row->getData('ean') || $row->getData('isbn')) &&
                ($row->getData('wpid') || $row->getData('item_id')) && $title == 'Walmart ID') {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }
            $identifierCode  = $this->__($title);
            $identifierValue = $this->getHelper('Data')->escapeHtml($value);

            $htmlAdditional .= <<<HTML
<div>
    <span style="display: inline-block; float: left;">
        <strong>{$identifierCode}:</strong>&nbsp;&nbsp;&nbsp;&nbsp;
    </span>
    <span style="display: inline-block; float: right;">
        {$identifierValue}
    </span>
    <div style="clear: both;"></div>
</div>
HTML;
        }

        if ($htmlAdditional != '') {
            $html .= $this->getTooltipHtml($htmlAdditional);
        }

        return $html;
    }

    // ---------------------------------------

    public function callbackColumnTemplateCategory($value, $row, $column, $isExport)
    {
        $html = $this->__('N/A');

        if ($row->getData('template_category_id')) {
            $url = $this->getUrl('*/walmart_template_category/edit', [
                'id' => $row->getData('template_category_id'),
                'close_on_save' => true
            ]);

            $templateTitle = $this->getHelper('Data')->escapeHtml($row->getData('template_category_title'));

            return <<<HTML
<a target="_blank" href="{$url}">{$templateTitle}</a>
HTML;
        }

        return $html;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%']
            ]
        );
    }

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
wlp.gtin LIKE '%{$value}%' OR
wlp.upc LIKE '%{$value}%' OR
wlp.ean LIKE '%{$value}%' OR
wlp.isbn LIKE '%{$value}%' OR
wlp.wpid LIKE '%{$value}%' OR
wlp.item_id LIKE '%{$value}%'
SQL;

        $collection->getSelect()->where($where);
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
    ListingGridHandlerObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################
}
