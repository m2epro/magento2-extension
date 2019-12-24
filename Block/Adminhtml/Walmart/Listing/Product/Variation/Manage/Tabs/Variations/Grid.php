<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $lockedDataCache = [];

    protected $childListingProducts = null;
    protected $currentProductVariations = null;
    protected $usedProductVariations = null;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    protected $walmartFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartVariationProductManageGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

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

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->distinct();
        $collection->getSelect()->where(
            "`second_table`.`variation_parent_id` = ?",
            (int)$this->getListingProduct()->getId()
        );
        // ---------------------------------------

        $collection->getSelect()->columns([
            'online_price' => 'second_table.online_price'
        ]);

        $lpvTable = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource()->getMainTable();
        $lpvoTable = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    mlpv.listing_product_id,
                    GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`product_id` SEPARATOR \'||\') as products_ids
                FROM `'. $lpvTable .'` as mlpv
                INNER JOIN `'. $lpvoTable .
                    '` AS `mlpvo` ON (`mlpvo`.`listing_product_variation_id`=`mlpv`.`id`)
                WHERE `mlpv`.`component_mode` = \'walmart\'
                GROUP BY `mlpv`.`listing_product_id`
            )'),
            'main_table.id=t.listing_product_id',
            [
                'products_ids' => 'products_ids',
            ]
        );

        // Set collection to grid
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
            'header'    => $this->__('Magento Variation'),
            'align'     => 'left',
            'width' => '210px',
            'sortable' => false,
            'index'     => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => [$this, 'callbackColumnProductOptions'],
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $productAttributes,
            'filter_condition_callback' => [$this, 'callbackProductOptions']
        ]);

        $this->addColumn('channel_options', [
            'header'    => $this->__('Walmart Variation'),
            'align'     => 'left',
            'width' => '210px',
            'sortable' => false,
            'index'     => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => [$this, 'callbackColumnChannelOptions'],
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $channelAttributes,
            'filter_condition_callback' => [$this, 'callbackChannelOptions']
        ]);

        $this->addColumn('sku', [
            'header' => $this->__('SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku',
            'frame_callback' => [$this, 'callbackColumnWalmartSku']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $priceColumn = [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ];

        $this->addColumn('online_price', $priceColumn);

        $statusColumn = [
            'header' => $this->__('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        if ($this->getHelper('View\Walmart')->isResetFilterShouldBeShown(
            $this->getListingProduct()->getListingId(),
            true
        )
        ) {
            $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status';
        }

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
            'label'    => $this->__('List Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('revise', [
            'label'    => $this->__('Revise Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('relist', [
            'label'    => $this->__('Relist Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('stop', [
            'label'    => $this->__('Stop Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label'    => $this->__('Retire on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductOptions($additionalData, $row, $column, $isExport)
    {
        $html = '';

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
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
                $url = $this->getUrl('catalog/product/edit', ['id' => reset($productsIds)]);
                $html .= '<a href="' . $url . '" target="_blank">';
            }
            foreach ($productOptions as $attribute => $option) {
                $style = '';
                if (in_array($attribute, $virtualProductAttributes)) {
                    $style = 'border-bottom: 2px dotted grey';
                }

                !$option && $option = '--';
                $optionHtml = '<span class="attribute-row" style="' . $style . '"><span class="attribute"><strong>' .
                    $this->getHelper('Data')->escapeHtml($attribute) .
                    '</strong></span>:&nbsp;<span class="value">' . $this->getHelper('Data')->escapeHtml($option) .
                    '</span></span>';

                if ($uniqueProductsIds && $option !== '--' && !in_array($attribute, $virtualProductAttributes)) {
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
        }

        if ($this->canChangeProductVariation($row)) {
            $listingProductId = $row->getId();
            $attributes = array_keys($typeModel->getParentTypeModel()->getMatchedAttributes());
            $variationsTree = $this->getProductVariationsTree($row, $attributes);

            $linkTitle = $this->__('Change Variation');
            $linkContent = $this->__('Change Variation');

            $attributes = $this->getHelper('Data')->escapeHtml($this->getHelper('Data')->jsonEncode($attributes));
            $variationsTree = $this->getHelper('Data')->escapeHtml(
                $this->getHelper('Data')->jsonEncode($variationsTree)
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
            $url = $this->getHelper('Component\Walmart')->getItemUrl(
                $itemId,
                $this->getListingProduct()->getListing()->getMarketplaceId()
            );

            $html .= '<a href="' . $url . '" target="_blank" title="' . $gtin . '" >';
        }

        foreach ($options as $attribute => $option) {
            $style = '';
            if (in_array($attribute, $virtualChannelAttributes)) {
                $style = 'border-bottom: 2px dotted grey';
            }

            !$option && $option = '--';

            $attrName = $this->getHelper('Data')->escapeHtml($attribute);
            $optionName = $this->getHelper('Data')->escapeHtml($option);

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

    public function callbackColumnWalmartSku($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('sku');
        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        }

        $productId = $row->getData('id');

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            $value = <<<HTML
<div class="walmart-sku">
    {$value}&nbsp;&nbsp;
    <a href="#" class="walmart-sku-edit"
       onclick="ListingGridHandlerObj.editChannelDataHandler.showEditSkuPopup({$productId})">(edit)</a>
</div>
HTML;
        }

        if ($row->getData('is_details_data_changed') || $row->getData('is_online_price_invalid')) {
            $msg = '';

            if ($row->getData('is_details_data_changed')) {
                $message = <<<HTML
Item Details, e.g. Product Tax Code, Lag Time, Shipping, Description, Image, Category, etc. settings, need to be
updated on Walmart.<br>
To submit new Item Details, apply the Revise action.
HTML;
                $msg .= '<p>'.$this->__($message).'</p>';
            }

            if ($row->getData('is_online_price_invalid')) {
                $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
                $msg .= '<p>'.$this->__($message).'</p>';
            }

            if (empty($msg)) {
                return $value;
            }

            $value .= <<<HTML
<span style="float:right;">
    {$this->getTooltipHtml($msg, 'map_link_defected_message_icon_'.$row->getId())}
</span>
HTML;
        }

        return $value;
    }

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        $gtin = $row->getChildObject()->getData('gtin');
        if (empty($gtin)) {
            return $this->__('N/A');
        }

        $productId = $row->getData('id');
        $gtinHtml = $this->getHelper('Data')->escapeHtml($gtin);
        $channelUrl = $row->getData('channel_url');

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = '<div class="walmart-identifiers-gtin">'.$gtinHtml;

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            $html .= <<<HTML
&nbsp;&nbsp;<a href="#" class="walmart-identifiers-gtin-edit"
   onclick="ListingGridHandlerObj.editChannelDataHandler.showIdentifiersPopup('$productId')">(edit)</a>
HTML;
        }

        $html .= '</div>';

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
            $html .= <<<HTML
<span style="float:right;">
    {$this->getTooltipHtml($htmlAdditional)}
</span>
HTML;
        }

        if (empty($html)) {
            return $this->__('N/A');
        }

        return $html;
    }

    public function callbackColumnAvailableQty($qty, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if ($qty === null || $qty === '' ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
                !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        if ($qty <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $qty;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlinePrice  = $row->getChildObject()->getData('online_price');

        if ($onlinePrice === null || $onlinePrice === '' ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
                !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        $currency = $this->getListingProduct()->getListing()
            ->getMarketplace()->getChildObject()
            ->getDefaultCurrency();

        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);
        }

        return $priceValue;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $listingProductId = (int)$row->getData('id');

        $html = $this->getViewLogIconHtml($row);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $synchNote = $listingProduct->getSetting('additional_data', 'synch_template_list_rules_note');
        if (!empty($synchNote)) {
            $synchNote = $this->getHelper('View')->getModifiedLogMessage($synchNote);

            if (empty($html)) {
                $html = <<<HTML
<span style="float:right;">
    {$this->getTooltipHtml($synchNote, 'map_link_error_icon_'.$row->getId())}
</span>
HTML;
            } else {
                $html .= <<<HTML
<div id="synch_template_list_rules_note_{$listingProductId}" style="display: none">{$synchNote}</div>
HTML;
            }
        }

        $isListAction = $listingProduct->getSetting('additional_data', 'is_list_action');
        if (!empty($isListAction)) {
            $html .= '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        } else {
            switch ($row->getData('status')) {
                case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                    $html .= '<span style="color: gray;">' . $value . '</span>';
                    break;

                case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                    $html .= '<span style="color: green;">' . $value . '</span>';
                    break;

                case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                    $html .= '<span style="color: red;">'.$value.'</span>';
                    break;

                case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                    $html .= '<span style="color: orange; font-weight: bold;">'.$value.'</span>';
                    break;

                default:
                    break;
            }
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();

        $html .= $this->getStatusChangeReasons($statusChangeReasons);

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
            !$row->getData('is_online_price_invalid')) {
            $html .= <<<HTML
<br/>
<span style="color: gray">[Can be fixed]</span>
HTML;
        }

        $tempLocks = $this->getLockedData($row);
        $tempLocks = $tempLocks['object_locks'];

        if (!empty($isListAction)) {
            $html .= '<br/><span style="color: #605fff">[Listing...]</span>';
        } else {
            foreach ($tempLocks as $lock) {
                switch ($lock->getTag()) {
                    case 'list_action':
                        $html .= '<br/><span style="color: #605fff">[Listing...]</span>';
                        break;

                    case 'relist_action':
                        $html .= '<br/><span style="color: #605fff">[Relisting...]</span>';
                        break;

                    case 'revise_action':
                        $html .= '<br/><span style="color: #605fff">[Revising...]</span>';
                        break;

                    case 'stop_action':
                        $html .= '<br/><span style="color: #605fff">[Stopping...]</span>';
                        break;

                    case 'stop_and_remove_action':
                        $html .= '<br/><span style="color: #605fff">[Stopping...]</span>';
                        break;

                    case 'delete_and_remove_action':
                        $html .= '<br/><span style="color: #605fff">[Removing...]</span>';
                        break;

                    default:
                        break;
                }
            }
        }

        return $html;
    }

    // ---------------------------------------

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
                    ['regexp'=> '"variation_product_options":[^}]*' .
                        $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                        // trying to screen slashes that in json
                        addslashes(addslashes($value['value']).'[[:space:]]*')]
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
                    ['regexp'=> '"variation_channel_options":[^}]*' .
                        $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                        // trying to screen slashes that in json
                        addslashes(addslashes($value['value']).'[[:space:]]*')]
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
                $condition = 'second_table.online_price >= \'' . (float)$value['from'] . '\'';
            }

            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'second_table.online_price <= \'' . (float)$value['to'] . '\'';
            }
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

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return string
     */
    public function getViewLogIconHtml($listingProduct)
    {
        $listingProductId = (int)$listingProduct->getId();
        $availableActionsId = array_keys($this->getAvailableActions());

        $connection = $this->resourceConnection->getConnection();

        // Get last messages
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
                ['action_id','action','type','description','create_date','initiator']
            )
            ->where('`listing_product_id` = ?', $listingProductId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing_Log_Grid_LastActions')->setData([
            'entity_id' => (int)$listingProduct->getId(),
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'ListingProductVariationManageVariationsGridObj.viewItemHelp',
            'hide_help_handler' => 'ListingProductVariationManageVariationsGridObj.hideItemHelp',
        ]);

        return $summary->toHtml();
    }

    private function getAvailableActions()
    {
        return [
            Log::ACTION_LIST_PRODUCT_ON_COMPONENT       => $this->__('List'),
            Log::ACTION_RELIST_PRODUCT_ON_COMPONENT     => $this->__('Relist'),
            Log::ACTION_REVISE_PRODUCT_ON_COMPONENT     => $this->__('Revise'),
            Log::ACTION_STOP_PRODUCT_ON_COMPONENT       => $this->__('Stop'),
            Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT   => $this->__('Remove from Channel'),
            Log::ACTION_STOP_AND_REMOVE_PRODUCT         => $this->__('Stop on Channel / Remove from Listing'),
            Log::ACTION_DELETE_AND_REMOVE_PRODUCT       => $this->__('Remove from Channel & Listing'),
            Log::ACTION_CHANNEL_CHANGE                  => $this->__('Channel Change'),
            Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT      => $this->__('Switch to AFN'),
            Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT      => $this->__('Switch to MFN'),
        ];
    }

    //########################################

    public function getMainButtonsHtml()
    {
        $html = '';
        if ($this->getFilterVisibility()) {
            $html.= $this->getSearchButtonHtml();
            $html.= $this->getResetFilterButtonHtml();
            $html.= $this->getAddNewChildButtonsHtml();
        }
        return $html;
    }

    private function getAddNewChildButtonsHtml()
    {
        if ($this->isNewChildAllowed()) {
            // ---------------------------------------
            $data = [
                'label'   => $this->__('Add New Child Product'),
                'onclick' => 'ListingProductVariationManageVariationsGridObj.showNewChildForm('
                             . $this->getListingProduct()->getId() . ')',
                'class'   => 'action primary',
                'style'   => 'position: absolute; margin-top: -32px;right: 27px;',
                'id'      => 'add_new_child_button'
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
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

            /** @var ChildRelation $childTypeModel */
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

    // ---------------------------------------

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_product_variation_manage/viewVariationsGridAjax', [
            'product_id' => $this->getListingProduct()->getId()
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="">
            {$content}
        </div>
    </div>
HTML;
    }

    //########################################

    protected function _toHtml()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Listing/Product/Variation/Manage/Tabs/Variations/Grid'
    ], function(){

        ListingProductVariationManageVariationsGridObj.afterInitPage();

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

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

    //########################################

    public function getProductVariationsTree($childProduct, $attributes)
    {
        $unusedVariations = $this->getUnusedProductVariations();

        /** @var ChildRelation $childTypeModel */
        $childTypeModel = $childProduct->getChildObject()->getVariationManager()->getTypeModel();

        if ($childTypeModel->isVariationProductMatched()) {
            $unusedVariations[] = $childTypeModel->getProductOptions();
        }

        $variationsSets = $this->getAttributesVariationsSets($unusedVariations);
        $variationsSetsSorted =  [];

        foreach ($attributes as $attribute) {
            $variationsSetsSorted[$attribute] = $variationsSets[$attribute];
        }

        $firstAttribute = key($variationsSetsSorted);

        return $this->prepareVariations($firstAttribute, $unusedVariations, $variationsSetsSorted);
    }

    private function prepareVariations($currentAttribute, $unusedVariations, $variationsSets, $filters = [])
    {
        $return = false;

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

            if ($return !== false) {
                ksort($return[$currentAttribute]);
            }

            return $return;
        }

        $return = false;
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

        if (count($unusedVariations) < 1) {
            return false;
        }

        if ($return !== false) {
            ksort($return[$currentAttribute]);
        }

        return $return;
    }

    //########################################

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

    //########################################

    public function getUnusedProductVariations()
    {
        return $this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUnusedProductOptions();
    }

    //########################################

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
                if (!in_array($option, $attributesOptions[$attr])) {
                    $attributesOptions[$attr][] = $option;
                }
            }
        }

        return $attributesOptions;
    }

    //########################################

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

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################

    private function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
            . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
            . '</li>';

        return <<<HTML
        <span class="fix-magento-tooltip">
            {$this->getTooltipHtml($html)}
        </span>
HTML;
    }

    //########################################
}
