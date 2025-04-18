<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations;

use Ess\M2ePro\Model\Listing\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var array */
    protected $lockedDataCache = [];

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product */
    protected $childListingProducts;
    /** @var array */
    protected $currentProductVariations;
    /** @var array */
    protected $usedProductVariations;
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;
    /** @var \Ess\M2ePro\Helper\View\Walmart */
    protected $walmartViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing */
    private $walmartListingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat */
    private $walmartSellingFormatResource;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource
     * @param \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat $walmartSellingFormatResource
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Component\Walmart $walmartHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat $walmartSellingFormatResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->walmartViewHelper = $walmartViewHelper;
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $backendHelper, $data);
        $this->walmartListingResource = $walmartListingResource;
        $this->walmartSellingFormatResource = $walmartSellingFormatResource;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartVariationProductManageGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->distinct();
        $collection->getSelect()->where(
            "`second_table`.`variation_parent_id` = ?",
            (int)$this->getListingProduct()->getId()
        );

        $collection->getSelect()->columns([
            'online_price' => 'second_table.online_price',
        ]);

        $lpvTable = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource()->getMainTable();
        $lpvoTable = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
                                               ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr(
                '(
                SELECT
                    mlpv.listing_product_id,
                    GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`product_id` SEPARATOR \'||\') as products_ids
                FROM `' . $lpvTable . '` as mlpv
                INNER JOIN `' . $lpvoTable .
                '` AS `mlpvo` ON (`mlpvo`.`listing_product_variation_id`=`mlpv`.`id`)
                WHERE `mlpv`.`component_mode` = \'walmart\'
                GROUP BY `mlpv`.`listing_product_id`
            )'
            ),
            'main_table.id=t.listing_product_id',
            [
                'products_ids' => 'products_ids',
            ]
        );

        $collection->getSelect()->joinInner(
            ['wl' => $this->walmartListingResource->getMainTable()],
            'wl.listing_id = main_table.listing_id',
            null
        );

        $collection->getSelect()->joinInner(
            ['wtsf' => $this->walmartSellingFormatResource->getMainTable()],
            'wtsf.template_selling_format_id = wl.template_selling_format_id',
            [
                'is_set_online_promotions'
                => new \Zend_Db_Expr('wtsf.promotions_mode = 1 AND second_table.online_promotions IS NOT NULL'),
            ]
        );

        if ($this->getParam($this->getVarNameFilter()) == 'searched_by_child') {
            $collection->addFieldToFilter(
                'second_table.listing_product_id',
                ['in' => explode(',', $this->getRequest()->getParam('listing_product_id_filter'))]
            );
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $parentType */
        $parentType = $this->getListingProduct()->getChildObject()->getVariationManager()->getTypeModel();

        $channelAttributesSets = $parentType->getChannelAttributesSets();
        $productAttributes = $parentType->getProductAttributes();

        if ($parentType->hasMatchedAttributes()) {
            $productAttributes = array_keys($parentType->getMatchedAttributes());
            $channelAttributes = array_values($parentType->getMatchedAttributes());
        } elseif (!empty($channelAttributesSets)) {
            $channelAttributes = array_keys($channelAttributesSets);
        } else {
            $channelAttributes = [];
        }

        $this->addColumn('product_options', [
            'header' => __('Magento Variation'),
            'align' => 'left',
            'width' => '210px',
            'sortable' => false,
            'index' => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => [$this, 'callbackColumnProductOptions'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions::class,
            'options' => $productAttributes,
            'filter_condition_callback' => [$this, 'callbackProductOptions'],
        ]);

        $this->addColumn('channel_options', [
            'header' => __('Walmart Variation'),
            'align' => 'left',
            'width' => '210px',
            'sortable' => false,
            'index' => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => [$this, 'callbackColumnChannelOptions'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions::class,
            'options' => $channelAttributes,
            'filter_condition_callback' => [$this, 'callbackChannelOptions'],
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku',
            'is_variation_grid' => true,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku::class,
        ]);

        $this->addColumn('gtin', [
            'header' => __('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'is_variation_grid' => true,
            'marketplace_id' => $this->getListingProduct()->getListing()->getMarketplaceId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin::class,
        ]);

        $this->addColumn('online_qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Qty::class,
            'filter_condition_callback' => [$this, 'callbackFilterQty'],
        ]);

        $priceColumn = [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ];

        $this->addColumn('online_price', $priceColumn);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => __('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => __('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE => __('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => __('Incomplete'),
            ],
            'is_variation_grid' => true,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Status::class,
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        $isShouldBeShown = $this->walmartViewHelper->isResetFilterShouldBeShown(
            'variation_parent_id',
            $this->getListingProduct()->getId()
        );

        $isShouldBeShown && $statusColumn['filter'] = \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status::class;

        $this->addColumn('status', $statusColumn);

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
        $this->getMassactionBlock()->addItem('list', [
            'label' => __('List Item(s)'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('revise', [
            'label' => __('Revise Item(s)'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('relist', [
            'label' => __('Relist Item(s)'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('stop', [
            'label' => __('Stop Item(s)'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => __('Stop on Channel / Remove from Listing'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label' => __('Retire on Channel / Remove from Listing'),
            'url' => '',
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductOptions($additionalData, $row, $column, $isExport)
    {
        $html = '';

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
        $typeModel = $row->getChildObject()->getVariationManager()->getTypeModel();

        $html .= '<div class="product-options-main" style="font-size: 11px; color: grey; margin-left: 7px">';
        $productOptions = $typeModel->getProductOptions();
        if (!empty($productOptions)) {
            $productsIds = $this->parseGroupedData($row->getData('products_ids'));
            $uniqueProductsIds = count(array_unique($productsIds)) > 1;

            $matchedAttributes = $typeModel->getParentTypeModel()->getMatchedAttributes();
            if (!empty($matchedAttributes)) {
                $sortedOptions = [];

                foreach ($matchedAttributes as $magentoAttr => $walmartAttr) {
                    $sortedOptions[$magentoAttr] = $productOptions[$magentoAttr];
                }

                $productOptions = $sortedOptions;
            }

            $virtualProductAttributes = array_keys($typeModel->getParentTypeModel()->getVirtualProductAttributes());

            $html .= '<div class="m2ePro-variation-attributes product-options-list">';
            if (!$uniqueProductsIds) {
                $data['id'] = reset($productsIds);
                if ($this->getListingProduct()->getListing()->getStoreId() != null) {
                    $data['store'] = $this->getListingProduct()->getListing()->getStoreId();
                }
                $url = $this->getUrl('catalog/product/edit', $data);
                $html .= '<a href="' . $url . '" target="_blank">';
            }
            foreach ($productOptions as $attribute => $option) {
                $style = '';
                if (in_array($attribute, $virtualProductAttributes, true)) {
                    $style = 'border-bottom: 2px dotted grey';
                }

                if ($option === '' || $option === null) {
                    $option = '--';
                }
                $optionHtml = '<span class="attribute-row" style="' . $style . '"><span class="attribute"><strong>' .
                    $this->dataHelper->escapeHtml($attribute) .
                    '</strong></span>:&nbsp;<span class="value">' . $this->dataHelper->escapeHtml($option) .
                    '</span></span>';

                if ($uniqueProductsIds && $option !== '--' && !in_array($attribute, $virtualProductAttributes, true)) {
                    $data['id'] = $productsIds[$attribute];
                    if ($this->getListingProduct()->getListing()->getStoreId() != null) {
                        $data['store'] = $this->getListingProduct()->getListing()->getStoreId();
                    }
                    $url = $this->getUrl('catalog/product/edit', $data);
                    $html .= '<a href="' . $url . '" target="_blank">' . $optionHtml . '</a><br/>';
                } else {
                    $html .= $optionHtml . '<br/>';
                }
            }
            if (!$uniqueProductsIds) {
                $html .= '</a>';
            }
            $html .= '</div>';
        }

        if ($this->canChangeProductVariation($row)) {
            $listingProductId = $row->getId();
            $attributes = array_keys($typeModel->getParentTypeModel()->getMatchedAttributes());
            $variationsTree = $this->getProductVariationsTree($row, $attributes);

            $linkTitle = __('Change Variation');
            $linkContent = __('Change Variation');

            $attributes = $this->dataHelper->escapeHtml(
                \Ess\M2ePro\Helper\Json::encode($attributes)
            );
            $variationsTree = $this->dataHelper->escapeHtml(
                \Ess\M2ePro\Helper\Json::encode($variationsTree)
            );

            $html .= <<<HTML
<form action="javascript:void(0);" class="product-options-edit"></form>
<a href="javascript:" style="line-height: 23px;"
    onclick="ListingProductVariationManageVariationsGridObj.editProductOptions(
        this, {$attributes}, {$variationsTree}, {$listingProductId}
    )"
    title="{$linkTitle}">{$linkContent}</a>
HTML;
        }

        $html .= '</div>';

        return $html;
    }

    public function callbackColumnChannelOptions($additionalData, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $row->getChildObject();

        $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
        $parentWalmartListingProduct = $typeModel->getParentListingProduct()->getChildObject();

        $matchedAttributes = $parentWalmartListingProduct->getVariationManager()
                                                         ->getTypeModel()
                                                         ->getMatchedAttributes();

        $options = $typeModel->getChannelOptions();

        if (!empty($matchedAttributes)) {
            $sortedOptions = [];

            foreach ($matchedAttributes as $magentoAttr => $walmartAttr) {
                if (empty($options[$walmartAttr])) {
                    continue;
                }
                $sortedOptions[$walmartAttr] = $options[$walmartAttr];
            }

            $options = $sortedOptions;
        }

        if (empty($options)) {
            return '';
        }

        $gtin = $walmartListingProduct->getGtin();
        $itemId = $walmartListingProduct->getItemId();

        $virtualChannelAttributes = array_keys($typeModel->getParentTypeModel()->getVirtualChannelAttributes());

        $html = '<div class="m2ePro-variation-attributes" style="color: grey; margin-left: 7px">';

        if (!empty($gtin) && !empty($itemId)) {
            $marketplaceId = $this->getListingProduct()->getListing()->getMarketplaceId();
            $url = $this->walmartHelper->getItemUrl(
                $walmartListingProduct->getData($this->walmartHelper->getIdentifierForItemUrl($marketplaceId)),
                $marketplaceId
            );

            $html .= '<a href="' . $url . '" target="_blank" title="' . $gtin . '" >';
        }

        foreach ($options as $attribute => $option) {
            $style = '';
            if (in_array($attribute, $virtualChannelAttributes, true)) {
                $style = 'border-bottom: 2px dotted grey';
            }

            if ($option === '' || $option === null) {
                $option = '--';
            }

            $attrName = $this->dataHelper->escapeHtml($attribute);
            $optionName = $this->dataHelper->escapeHtml($option);

            $html .= <<<HTML
<span style="{$style}"><b>{$attrName}</b>:&nbsp;{$optionName}</span><br/>
HTML;
        }

        if (!empty($gtin) && !empty($itemId)) {
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $onlinePrice = $row->getChildObject()->getData('online_price');

        if ($onlinePrice === null || $onlinePrice === '') {
            if (
                $row->getData('status') == Product::STATUS_NOT_LISTED
                || $row->getData('status') == Product::STATUS_BLOCKED
            ) {
                return __('N/A');
            }

            return '<i style="color:gray;">receiving...</i>';
        }

        $currency = $this->getListingProduct()->getListing()
                         ->getMarketplace()->getChildObject()
                         ->getDefaultCurrency();
        $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);

        if ($row->getChildObject()->getData('is_online_price_invalid')) {
            $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
            $msg = '<p>' . __($message) . '</p>';
            if (empty($msg)) {
                return $priceValue;
            }

            $priceValue .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($message, 'map_link_defected_message_icon_' . $row->getId())}
</span>
HTML;

            return $priceValue;
        }

        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        }

        $isSetOnlinePromotions = (bool)$row->getData('is_set_online_promotions');
        if ($isSetOnlinePromotions) {
            $promotionTooltipText = __('Price without promotions<br>Actual price is available on Walmart.');
            $promotionTooltipHtml = $this->getTooltipHtml(
                $promotionTooltipText,
                '',
                ['m2epro-field-tooltip-price-info']
            );
            $priceValue = $promotionTooltipHtml . '&nbsp;' . $priceValue;
        }

        return $priceValue;
    }

    public function callbackProductOptions($collection, $column)
    {
        $values = $column->getFilter()->getValue();

        if ($values == null && !is_array($values)) {
            return;
        }

        foreach ($values as $value) {
            if (is_array($value) && isset($value['value'])) {
                $collection->addFieldToFilter(
                    'additional_data',
                    [
                        'regexp' => '"variation_product_options":[^}]*' .
                            $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                            // trying to screen slashes that in json
                            addslashes(addslashes($value['value']) . '[[:space:]]*'),
                    ]
                );
            }
        }
    }

    public function callbackChannelOptions($collection, $column)
    {
        $values = $column->getFilter()->getValue();

        if ($values == null && !is_array($values)) {
            return;
        }

        foreach ($values as $value) {
            if (is_array($value) && isset($value['value'])) {
                $collection->addFieldToFilter(
                    'additional_data',
                    [
                        'regexp' => '"variation_channel_options":[^}]*' .
                            $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                            // trying to screen slashes that in json
                            addslashes(addslashes($value['value']) . '[[:space:]]*'),
                    ]
                );
            }
        }
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = 'main_table.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $where .= ' AND online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            $where .= ' AND online_qty <= ' . (int)$value['to'];
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = 'main_table.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $condition .= ' AND second_table.online_price >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            $quoted = $collection->getConnection()->quote($value['to']);
            $condition .= ' AND second_table.online_price <= ' . $quoted;
        }

        $collection->getSelect()->where($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null || $index == null) {
            return;
        }

        if (is_array($value) && isset($value['value']) || is_string($value)) {
            if (is_string($value)) {
                $status = (int)$value;
            } else {
                $status = (int)$value['value'];
            }

            $collection->addFieldToFilter($index, $status);
        }

        if (is_array($value) && isset($value['is_reset'])) {
            $collection->addFieldToFilter($index, \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED)
                       ->addFieldToFilter('is_online_price_invalid', 0);
        }
    }

    public function getMainButtonsHtml()
    {
        $html = '';
        if ($this->getFilterVisibility()) {
            $html .= $this->getSearchButtonHtml();
            $html .= $this->getResetFilterButtonHtml();
            $html .= $this->getAddNewChildButtonsHtml();
        }

        return $html;
    }

    private function getAddNewChildButtonsHtml()
    {
        if ($this->isNewChildAllowed()) {
            // ---------------------------------------
            $data = [
                'label' => __('Add New Child Product'),
                'onclick' => 'ListingProductVariationManageVariationsGridObj.showNewChildForm('
                    . $this->getListingProduct()->getId() . ')',
                'class' => 'action primary',
                'style' => 'float: right;',
                'id' => 'add_new_child_button',
            ];
            $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                ->setData($data);
            $this->setChild('add_new_child_button', $buttonBlock);
            // ---------------------------------------
        }

        return $this->getChildHtml('add_new_child_button');
    }

    protected function isNewChildAllowed()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();

        if (!$walmartListingProduct->getVariationManager()->getTypeModel()->hasMatchedAttributes()) {
            return false;
        }

        if (!$this->hasUnusedProductVariation()) {
            return false;
        }

        if ($this->hasChildWithEmptyProductOptions()) {
            return false;
        }

        return true;
    }

    public function getCurrentChannelVariations()
    {
        return $this->getListingProduct()->getChildObject()
                    ->getVariationManager()->getTypeModel()->getChannelVariations();
    }

    public function hasUnusedProductVariation()
    {
        return (bool)$this->getListingProduct()
                          ->getChildObject()
                          ->getVariationManager()
                          ->getTypeModel()
                          ->getUnusedProductOptions();
    }

    public function hasChildWithEmptyProductOptions()
    {
        foreach ($this->getChildListingProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
                return true;
            }
        }

        return false;
    }

    public function getUsedChannelVariations()
    {
        return (bool)$this->getListingProduct()
                          ->getChildObject()
                          ->getVariationManager()
                          ->getTypeModel()
                          ->getUsedChannelOptions();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_product_variation_manage/viewVariationsGridAjax', [
            'product_id' => $this->getListingProduct()->getId(),
        ]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="width:300px">
            {$content}
        </div>
    </div>
HTML;
    }

    protected function _toHtml()
    {
        $this->css->add(
            <<<CSS
div.admin__filter-actions { width: 100%; }
CSS
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Listing/Product/Variation/Manage/Tabs/Variations/Grid'
    ], function(){

        ListingProductVariationManageVariationsGridObj.afterInitPage();
        ListingProductVariationManageVariationsGridObj.actionHandler.messageObj.clear();

    });
JS
        );

        if ($this->getParam($this->getVarNameFilter()) == 'searched_by_child') {
            $noticeMessage = __('This list includes a Product you are searching for.');
            $this->js->add(
                <<<JS
    require([
        'M2ePro/Walmart/Listing/Product/Variation/Manage/Tabs/Variations/Grid'
    ], function(){
        ListingProductVariationManageVariationsGridObj.actionHandler.messageObj.addNotice('{$noticeMessage}');
    });
JS
            );
        }

        return parent::_toHtml();
    }

    private function canChangeProductVariation(\Ess\M2ePro\Model\Listing\Product $childListingProduct)
    {
        if (!$this->hasUnusedProductVariation()) {
            return false;
        }

        $lockData = $this->getLockedData($childListingProduct);
        if ($lockData['in_action']) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartChildListingProduct */
        $walmartChildListingProduct = $childListingProduct->getChildObject();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
        $typeModel = $walmartChildListingProduct->getVariationManager()->getTypeModel();

        if ($typeModel->isVariationProductMatched() && $this->hasChildWithEmptyProductOptions()) {
            return false;
        }

        if (!$typeModel->getParentTypeModel()->hasMatchedAttributes()) {
            return false;
        }

        return true;
    }

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

    public function getProductVariationsTree($childProduct, $attributes)
    {
        $unusedVariations = $this->getUnusedProductVariations();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
        $childTypeModel = $childProduct->getChildObject()->getVariationManager()->getTypeModel();

        if ($childTypeModel->isVariationProductMatched()) {
            $unusedVariations[] = $childTypeModel->getProductOptions();
        }

        $variationsSets = $this->getAttributesVariationsSets($unusedVariations);
        $variationsSetsSorted = [];

        foreach ($attributes as $attribute) {
            $variationsSetsSorted[$attribute] = $variationsSets[$attribute];
        }

        $firstAttribute = key($variationsSetsSorted);

        return $this->prepareVariations($firstAttribute, $unusedVariations, $variationsSetsSorted);
    }

    private function prepareVariations($currentAttribute, $unusedVariations, $variationsSets, $filters = [])
    {
        $return = [];

        $temp = array_flip(array_keys($variationsSets));

        $lastAttributePosition = count($variationsSets) - 1;
        $currentAttributePosition = $temp[$currentAttribute];

        if ($currentAttributePosition != $lastAttributePosition) {
            $temp = array_keys($variationsSets);
            $nextAttribute = $temp[$currentAttributePosition + 1];

            foreach ($variationsSets[$currentAttribute] as $option) {
                $filters[$currentAttribute] = $option;

                $result = $this->prepareVariations(
                    $nextAttribute,
                    $unusedVariations,
                    $variationsSets,
                    $filters
                );

                if (!$result) {
                    continue;
                }

                $return[$currentAttribute][$option] = $result;
            }

            if (!empty($return)) {
                ksort($return[$currentAttribute]);
            }

            return $return;
        }

        $return = [];
        foreach ($unusedVariations as $key => $magentoVariation) {
            foreach ($magentoVariation as $attribute => $option) {
                if ($attribute == $currentAttribute) {
                    if (count($variationsSets) != 1) {
                        continue;
                    }

                    $values = array_flip($variationsSets[$currentAttribute]);
                    $return = [$currentAttribute => $values];

                    foreach ($return[$currentAttribute] as &$option) {
                        $option = true;
                    }

                    return $return;
                }

                if ($option != $filters[$attribute]) {
                    unset($unusedVariations[$key]);
                    continue;
                }

                foreach ($magentoVariation as $tempAttribute => $tempOption) {
                    if ($tempAttribute == $currentAttribute) {
                        $option = $tempOption;
                        $return[$currentAttribute][$option] = true;
                    }
                }
            }
        }

        if (empty($unusedVariations)) {
            return [];
        }

        if (!empty($return)) {
            ksort($return[$currentAttribute]);
        }

        return $return;
    }

    public function getCurrentProductVariations()
    {
        if ($this->currentProductVariations !== null) {
            return $this->currentProductVariations;
        }

        $magentoProductVariations = $this->getListingProduct()
                                         ->getMagentoProduct()
                                         ->getVariationInstance()
                                         ->getVariationsTypeStandard();

        $productVariations = [];

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = [];

            foreach ($option as $attribute) {
                $productOption[$attribute['attribute']] = $attribute['option'];
            }

            $productVariations[] = $productOption;
        }

        return $this->currentProductVariations = $productVariations;
    }

    public function getUsedProductVariations()
    {
        if ($this->usedProductVariations === null) {
            $this->usedProductVariations = $this->getListingProduct()
                                                ->getChildObject()
                                                ->getVariationManager()
                                                ->getTypeModel()
                                                ->getUsedProductOptions();
        }

        return $this->usedProductVariations;
    }

    public function getUnusedProductVariations()
    {
        return $this->getListingProduct()
                    ->getChildObject()
                    ->getVariationManager()
                    ->getTypeModel()
                    ->getUnusedProductOptions();
    }

    public function getChildListingProducts()
    {
        if ($this->childListingProducts !== null) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProduct()->getChildObject()
                                                  ->getVariationManager()->getTypeModel()->getChildListingsProducts();
    }

    public function getAttributesVariationsSets($variations)
    {
        $attributesOptions = [];

        foreach ($variations as $variation) {
            foreach ($variation as $attr => $option) {
                if (!isset($attributesOptions[$attr])) {
                    $attributesOptions[$attr] = [];
                }
                if (!in_array($option, $attributesOptions[$attr], true)) {
                    $attributesOptions[$attr][] = $option;
                }
            }
        }

        return $attributesOptions;
    }

    private function parseGroupedData($data)
    {
        $result = [];

        if (empty($data)) {
            return $result;
        }

        $variationData = explode('||', $data);
        foreach ($variationData as $variationAttribute) {
            $value = explode('==', $variationAttribute);
            $result[$value[0]] = $value[1];
        }

        return $result;
    }

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }
}
