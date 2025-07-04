<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Settings;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $walmartFactory;
    protected $resourceConnection;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
        $this->productTypeResource = $productTypeResource;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $dataHelper, $globalDataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('view_listing');

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingViewGrid' . $this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setStoreId($this->listing->getStoreId());
        $collection->setListing($this->listing->getId());

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem();

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
                'status' => 'status',
                'component_mode' => 'component_mode',
                'additional_data' => 'additional_data',
            ],
            [
                'listing_id' => (int)$this->listing['id'],
            ]
        );

        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['wlp' => $wlpTable],
            'listing_product_id=id',
            [
                'product_type_id' => 'product_type_id',
                'variation_child_statuses' => 'variation_child_statuses',
                'walmart_sku' => 'sku',
                'gtin' => 'gtin',
                'upc' => 'upc',
                'ean' => 'ean',
                'isbn' => 'isbn',
                'wpid' => 'wpid',
                'item_id' => 'item_id',
                'online_qty' => 'online_qty',
                'online_price' => 'online_price',
                'is_variation_parent' => 'is_variation_parent',
                'is_variation_product' => 'is_variation_product',
                'is_online_price_invalid' => 'is_online_price_invalid',
                'online_start_date' => 'online_start_date',
                'online_end_date' => 'online_end_date',
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        $collection->joinTable(
            ['pt' => $this->productTypeResource->getMainTable()],
            'id = product_type_id',
            [
                'product_type_title' => 'title',
            ],
            null,
            'left'
        );

        if ($this->isFilterOrSortByPriceIsUsed(null, 'walmart_online_price')) {
            $collection->joinIndexerParent();
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('sku', [
            'header' => __('Channel SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'walmart_sku',
            'filter_index' => 'walmart_sku',
            'show_edit_sku' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku::class,
        ]);

        $this->addColumn('gtin', [
            'header' => __('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'show_edit_identifier' => false,
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin::class,
            'filter_index' => 'gtin',
            'filter_condition_callback' => [$this, 'callbackFilterGtin'],
        ]);

        if (
            !$this->listing->getMarketplace()
                           ->getChildObject()
                           ->isSupportedProductType()
        ) {
            return parent::_prepareColumns();
        }

        $this->addColumn('product_type', [
            'header' => __('Product Type'),
            'align' => 'left',
            'width' => '250px',
            'type' => 'text',
            'index' => 'template_category_title',
            'filter_index' => 'template_category_title',
            'frame_callback' => [$this, 'callbackColumnProductType'],
            'filter_condition_callback' => [$this, 'callbackFilterProductType'],
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'action',
            'index' => 'actions',
            'filter' => false,
            'sortable' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'field' => 'id',
            'no_link' => true,
            'group_order' => $this->getGroupOrder(),
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    protected function getGroupOrder()
    {
        $groups = [
            'edit_product_type' => __('Product Type'),
            'other' => __('Other'),
        ];

        return $groups;
    }

    private function getColumnActionsItems(): array
    {
        return [
            'assignProductType' => [
                'caption' => __('Assign'),
                'group' => 'edit_product_type',
                'field' => 'id',
                'onclick_action' => 'ListingGridObj.actions[\'changeProductTypeAction\']',
            ],
            'unassignProductType' => [
                'caption' => __('Unassign'),
                'group' => 'edit_product_type',
                'field' => 'id',
                'onclick_action' => 'ListingGridObj.actions[\'unassignProductTypeAction\']',
            ],
            'remapProduct' => [
                'caption' => __('Link to another Magento Product'),
                'group' => 'other',
                'field' => 'id',
                'only_remap_product' => true,
                'style' => 'width: 255px',
                'onclick_action' => 'ListingGridObj.actions[\'remapProductAction\']',
            ],
        ];
    }

    protected function _prepareMassaction()
    {
        $isSupportedPt = $this->listing->getMarketplace()
                                       ->getChildObject()
                                       ->isSupportedProductType();

        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        // ---------------------------------------

        // Set mass-action
        // ---------------------------------------
        $groups = [
            'other' => __('Other'),
        ];

        if ($isSupportedPt) {
            $groups['product_type'] = __('Product Type');
        }

        $this->getMassactionBlock()->setGroups($groups);

        if ($isSupportedPt) {
            $this->getMassactionBlock()->addItem('changeProductType', [
                'label' => __('Assign'),
                'url' => '',
            ], 'product_type');

            $this->getMassactionBlock()->addItem('unassignProductType', [
                'label' => __('Unassign'),
                'url' => '',
            ], 'product_type');
        }

        $this->getMassactionBlock()->addItem('moving', [
            'label' => __('Move Item(s) to Another Listing'),
            'url' => '',
        ], 'other');

        $this->getMassactionBlock()->addItem('duplicate', [
            'label' => __('Duplicate'),
            'url' => '',
        ], 'other');

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->dataHelper->escapeHtml($productTitle);

        $value = '<span>' . $productTitle . '</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                                      ->setProductId($row->getData('entity_id'))
                                      ->getSku();
        }

        $value .= '<br/><strong>' . __('SKU') .
            ':</strong><span class="white-space-pre-wrap"> ' . $this->dataHelper->escapeHtml($sku) . '</span><br/>';

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
                if ($option === '' || $option === null) {
                    $option = '--';
                }
                $value .= '<strong>' . $this->dataHelper->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->dataHelper->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnProductType($value, $row, $column, $isExport)
    {
        $html = __('N/A');

        if ($row->getData('product_type_id')) {
            $url = $this->getUrl('*/walmart_productType/edit', [
                'id' => $row->getData('product_type_id'),
                'close_on_save' => true,
            ]);

            $title = $row->getData('product_type_title');

            return <<<HTML
<a target="_blank" href="{$url}">{$title}</a>
HTML;
        }

        return $html;
    }

    protected function callbackFilterProductType($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $inputValue = null;

        if (
            is_array($value)
            && isset($value['input'])
        ) {
            $inputValue = $value['input'];
        } elseif (is_string($value)) {
            $inputValue = $value;
        }

        if ($inputValue !== null) {
            $collection->addAttributeToFilter(
                'product_type_title',
                ['like' => '%' . $inputValue . '%']
            );
        }
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
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
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

    public function getRowUrl($item)
    {
        return false;
    }

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
}
