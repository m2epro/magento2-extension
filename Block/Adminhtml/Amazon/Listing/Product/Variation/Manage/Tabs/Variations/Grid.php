<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Variations;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use Ess\M2ePro\Model\Listing\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $lockedDataCache = array();

    protected $childListingProducts = null;
    protected $currentProductVariations = null;
    protected $usedProductVariations = null;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    protected $amazonFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
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
        $this->setId('amazonVariationProductManageGrid');
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
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->distinct();
        $collection->getSelect()->where(
            "`second_table`.`variation_parent_id` = ?",(int)$this->getListingProduct()->getId()
        );
        // ---------------------------------------

        $collection->getSelect()->columns(array(
            'online_current_price' => new \Zend_Db_Expr('
                IF (
                    `second_table`.`online_regular_price` IS NULL,
                    `second_table`.`online_business_price`,
                    IF (
                        `second_table`.`online_regular_sale_price` IS NOT NULL AND
                        `second_table`.`online_regular_sale_price_end_date` IS NOT NULL AND
                        `second_table`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                        `second_table`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                        `second_table`.`online_regular_sale_price`,
                        `second_table`.`online_regular_price`
                    )
                )
            ')
        ));

        $lpvTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource()->getMainTable();
        $lpvoTable = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    mlpv.listing_product_id,
                    GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`product_id` SEPARATOR \'||\') as products_ids
                FROM `'. $lpvTable .'` as mlpv
                INNER JOIN `'. $lpvoTable .
                    '` AS `mlpvo` ON (`mlpvo`.`listing_product_variation_id`=`mlpv`.`id`)
                WHERE `mlpv`.`component_mode` = \'amazon\'
                GROUP BY `mlpv`.`listing_product_id`
            )'),
            'main_table.id=t.listing_product_id',
            array(
                'products_ids' => 'products_ids',
            )
        );

        $alprTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            array('malpr' => $alprTable),
            '(`second_table`.`listing_product_id` = `malpr`.`listing_product_id`)',
            array(
                'is_repricing_disabled' => 'is_online_disabled',
            )
        );

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\Parent $parentType */
        $parentType = $this->getListingProduct()->getChildObject()->getVariationManager()->getTypeModel();

        $channelAttributesSets = $parentType->getChannelAttributesSets();
        $productAttributes = $parentType->getProductAttributes();

        if ($parentType->hasMatchedAttributes()) {
            $productAttributes = array_keys($parentType->getMatchedAttributes());
            $channelAttributes = array_values($parentType->getMatchedAttributes());
        } else if (!empty($channelAttributesSets)) {
            $channelAttributes = array_keys($channelAttributesSets);
        } else {
            $channelAttributes = array();
        }

        $this->addColumn('product_options', array(
            'header'    => $this->__('Magento Variation'),
            'align'     => 'left',
            'width' => '210px',
            'sortable' => false,
            'index'     => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => array($this, 'callbackColumnProductOptions'),
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $productAttributes,
            'filter_condition_callback' => array($this, 'callbackProductOptions')
        ));

        $this->addColumn('channel_options', array(
            'header'    => $this->__('Amazon Variation'),
            'align'     => 'left',
            'width' => '210px',
            'sortable' => false,
            'index'     => 'additional_data',
            'filter_index' => 'additional_data',
            'frame_callback' => array($this, 'callbackColumnChannelOptions'),
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $channelAttributes,
            'filter_condition_callback' => array($this, 'callbackChannelOptions')
        ));

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku',
            'frame_callback' => array($this, 'callbackColumnAmazonSku')
        ));

        $this->addColumn('general_id', array(
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        $this->addColumn('online_qty', array(
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => array($this, 'callbackColumnAvailableQty'),
            'filter'   => 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty',
            'filter_condition_callback' => array($this, 'callbackFilterQty')
        ));

        $priceColumn = array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_current_price',
            'filter_index' => 'online_current_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        );

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $this->getListingProduct()->getListing()->getAccount()->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_current_price', $priceColumn);

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

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
        $this->getMassactionBlock()->addItem('list', array(
            'label'    => $this->__('List Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('revise', array(
            'label'    => $this->__('Revise Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('relist', array(
            'label'    => $this->__('Relist Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('stop', array(
            'label'    => $this->__('Stop Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('stopAndRemove', array(
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('deleteAndRemove', array(
            'label'    => $this->__('Remove from Channel & Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductOptions($additionalData, $row, $column, $isExport)
    {
        $html = '';

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $row->getChildObject()->getVariationManager()->getTypeModel();

        $html .= '<div class="product-options-main" style="font-size: 11px; color: grey; margin-left: 7px">';
        $productOptions = $typeModel->getProductOptions();
        if (!empty($productOptions)) {
            $productsIds = $this->parseGroupedData($row->getData('products_ids'));
            $uniqueProductsIds = count(array_unique($productsIds)) > 1;

            $matchedAttributes = $typeModel->getParentTypeModel()->getMatchedAttributes();
            if (!empty($matchedAttributes)) {

                $sortedOptions = array();

                foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {
                    $sortedOptions[$magentoAttr] = $productOptions[$magentoAttr];
                }

                $productOptions = $sortedOptions;
            }

            $virtualProductAttributes = array_keys($typeModel->getParentTypeModel()->getVirtualProductAttributes());

            $html .= '<div class="m2ePro-variation-attributes product-options-list">';
            if (!$uniqueProductsIds) {
                $url = $this->getUrl('catalog/product/edit', array('id' => reset($productsIds)));
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
                    $url = $this->getUrl('catalog/product/edit', array('id' => $productsIds[$attribute]));
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
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $row->getChildObject();

        $typeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $typeModel->getParentListingProduct()->getChildObject();

        $matchedAttributes = $parentAmazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getMatchedAttributes();

        if (!$typeModel->isVariationChannelMatched()) {
            if (!$typeModel->isVariationProductMatched() || !$amazonListingProduct->isGeneralIdOwner()) {
                return '';
            }

            if (empty($matchedAttributes)) {
                return '';
            }

            $options = array();

            foreach ($typeModel->getProductOptions() as $attribute => $value) {
                $options[$matchedAttributes[$attribute]] = $value;
            }
        } else {
            $options = $typeModel->getChannelOptions();

            if (!empty($matchedAttributes)) {

                $sortedOptions = array();

                foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {
                    $sortedOptions[$amazonAttr] = $options[$amazonAttr];
                }

                $options = $sortedOptions;
            }
        }

        if (empty($options)) {
            return '';
        }

        $generalId = $amazonListingProduct->getGeneralId();

        $virtualChannelAttributes = array_keys($typeModel->getParentTypeModel()->getVirtualChannelAttributes());

        $html = '<div class="m2ePro-variation-attributes" style="color: grey; margin-left: 7px">';

        if (!empty($generalId)) {
            $url = $this->getHelper('Component\Amazon')->getItemUrl(
                $generalId,
                $this->getListingProduct()->getListing()->getMarketplaceId()
            );

            $html .= '<a href="' . $url . '" target="_blank" title="' . $generalId . '" >';
        }

        foreach ($options as $attribute => $option) {
            $style = '';
            if (in_array($attribute, $virtualChannelAttributes)) {
                $style = 'border-bottom: 2px dotted grey';
            }

            !$option && $option = '--';

            $attrName = $this->getHelper('Data')->escapeHtml($attribute);
            $optionName = $this->getHelper('Data')->escapeHtml($option);

            if (empty($generalId) && $amazonListingProduct->isGeneralIdOwner()) {
                $html .= <<<HTML
<span style="{$style}">{$attrName}:&nbsp;{$optionName}</span><br/>
HTML;
            } else {
                $html .= <<<HTML
<span style="{$style}"><b>{$attrName}</b>:&nbsp;{$optionName}</span><br/>
HTML;
            }
        }

        if (!empty($generalId)) {
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    public function callbackColumnAmazonSku($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('sku');
        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        }

        if ($row->getChildObject()->getData('defected_messages')) {
            $defectedMessages = $this->getHelper('Data')->jsonDecode(
                $row->getChildObject()->getData('defected_messages')
            );

            $msg = '';
            foreach ($defectedMessages as $message) {
                if (empty($message['message'])) {
                    continue;
                }

                $msg .= '<p>'.$message['message'] . '&nbsp;';
                if (!empty($message['value'])) {
                    $msg .= $this->__('Current Value') . ': "' . $message['value'] . '"';
                }
                $msg .= '</p>';
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

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        $generalId = $row->getChildObject()->getData('general_id');

        if (is_null($generalId) || $generalId === '') {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $this->getListingProduct()->getChildObject();
            if ($amazonListingProduct->isGeneralIdOwner()) {
                return $this->__('New ASIN/ISBN');
            }

            return $this->__('N/A');
        }
        return $this->getGeneralIdLink($generalId);
    }

    public function callbackColumnAvailableQty($qty, $row, $column, $isExport)
    {
        $qty = $row->getChildObject()->getData('online_qty');
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if ((bool)$row->getChildObject()->getData('is_afn_channel')) {
            $sku = $row->getChildObject()->getData('sku');

            if (empty($sku)) {
                return $this->__('AFN');
            }

            $productId = $row->getData('id');
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$productId);

            $afn = $this->__('AFN');
            $total = $this->__('Total');
            $inStock = $this->__('In Stock');
            $accountId = $listingProduct->getListing()->getAccountId();

            return <<<HTML
<div id="m2ePro_afn_qty_value_{$productId}">
    <span class="m2ePro-online-sku-value" productId="{$productId}" style="display: none">{$sku}</span>
    <span class="m2epro-empty-afn-qty-data" style="display: none">{$afn}</span>
    <div class="m2epro-afn-qty-data" style="display: none">
        <div class="total">{$total}: <span></span></div>
        <div class="in-stock">{$inStock}: <span></span></div>
    </div>
    <a href="javascript:void(0)"
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}','{$productId}',{$accountId})">{$afn}</a>
</div>
HTML;
        }

        if (is_null($qty) || $qty === '') {
            return $this->__('N/A');
        }

        return $qty;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlineRegularPrice  = $row->getChildObject()->getData('online_regular_price');
        $onlineBusinessPrice = $row->getChildObject()->getData('online_business_price');

        $repricingHtml ='';

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (bool)(int)$row->getChildObject()->getData('is_repricing')) {

            $icon = 'repricing-enabled';
            $text = $this->__(
                'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro. <br>
                 <strong>Please note</strong> that the Price value(s) shown in the grid might
                 be different from the actual one from Amazon. It is caused by the delay
                 in the values updating made via the Repricing Service.'
            );

            if ((int)$row->getData('is_repricing_disabled') == 1) {
                $icon = 'repricing-disabled';
                $text = $this->__(
                    'This product is disabled on Amazon Repricing Tool.
                     The Price is updated through the M2E Pro.'
                );
            }

            $repricingHtml = <<<HTML
<div class="fix-magento-tooltip {$icon}" style="float:right; text-align: left; margin-left: 5px;">
    {$this->getTooltipHtml($text)}
</div>
HTML;
        }

        if ((is_null($onlineRegularPrice) || $onlineRegularPrice === '') &&
            (is_null($onlineBusinessPrice) || $onlineBusinessPrice === '')
        ) {
            return $this->__('N/A') . $repricingHtml;
        }

        $currency = $this->getListingProduct()->getListing()->getMarketplace()
            ->getChildObject()
            ->getDefaultCurrency();

        if ((float)$onlineRegularPrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlineRegularPrice, $currency);
        }

        if ($row->getChildObject()->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled')
        ) {
            $accountId = $this->getListingProduct()->getListing()->getAccountId();
            $sku = $row->getChildObject()->getData('sku');

            $priceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;
        }

        $resultHtml = '';

        $salePrice = $row->getChildObject()->getData('online_regular_sale_price');
        if ((float)$salePrice > 0) {
            $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

            $startDateTimestamp = strtotime($row->getChildObject()->getData('online_regular_sale_price_start_date'));
            $endDateTimestamp   = strtotime($row->getChildObject()->getData('online_regular_sale_price_end_date'));

            if ($currentTimestamp <= $endDateTimestamp) {

                $fromDate = $this->_localeDate->formatDate(
                    $row->getChildObject()->getData('online_regular_sale_price_start_date'), \IntlDateFormatter::MEDIUM
                );

                $toDate = $this->_localeDate->formatDate(
                    $row->getChildObject()->getData('online_regular_sale_price_end_date'), \IntlDateFormatter::MEDIUM
                );

                $intervalHtml = <<<HTML
<span style="color: gray;">
    <strong>From:</strong> {$fromDate}<br/>
    <strong>To:</strong> {$toDate}
</span>
HTML;
                $intervalHtml = $this->getTooltipHtml($intervalHtml, '', ['m2epro-field-tooltip-price-info']);
                $salePriceValue = $this->convertAndFormatPriceCurrency($salePrice, $currency);

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$value
                ) {
                    $resultHtml .= '<span style="color: grey; text-decoration: line-through;">'.$priceValue.'</span>' .
                                    $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.'&nbsp;'.$salePriceValue;
                } else {
                    $resultHtml .= $priceValue . $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.
                        '<span style="color:gray;">'.'&nbsp;'.$salePriceValue.'</span>';
                }
            }
        }

        if (empty($resultHtml)) {
            $resultHtml = $priceValue . $repricingHtml;
        }

        if ((float)$onlineBusinessPrice > 0) {
            $businessPriceValue = '<strong>B2B:</strong> '
                                  .$this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);

            $businessDiscounts = $row->getChildObject()->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {

                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->localeCurrency->getCurrency($currency)->toCurrency($price);
                    $discountsHtml .= 'QTY >= '.(int)$qty.', price '.$price.'<br />';
                }

                $discountsHtml = $this->getTooltipHtml($discountsHtml, '', ['m2epro-field-tooltip-price-info']);
                $businessPriceValue = $discountsHtml .'&nbsp;'. $businessPriceValue;
            }

            if (!empty($resultHtml)) {
                $businessPriceValue = '<br />'.$businessPriceValue;
            }

            $resultHtml .= $businessPriceValue;
        }

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $listingProductId = (int)$row->getData('id');

        $html = $this->getViewLogIconHtml($row);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$listingProductId);

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

        switch ($row->getData('status')) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN:
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

        $tempLocks = $this->getLockedData($row);
        $tempLocks = $tempLocks['object_locks'];

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

                case 'switch_to_afn_action':
                    $html .= '<br/><span style="color: #605fff">[Switch to AFN in Progress...]</span>';
                    break;

                case 'switch_to_mfn_action':
                    $html .= '<br/><span style="color: #605fff">[Switch to MFN in Progress...]</span>';
                    break;

                default:
                    break;

            }
        }

        return $html;
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
                    array('regexp'=> '"variation_product_options":[^}]*' .
                        $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                        // trying to screen slashes that in json
                        addslashes(addslashes($value['value']).'[[:space:]]*'))
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
                    array('regexp'=> '"variation_channel_options":[^}]*' .
                        $value['attr'] . '[[:space:]]*":"[[:space:]]*' .
                        // trying to screen slashes that in json
                        addslashes(addslashes($value['value']).'[[:space:]]*'))
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

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }
            $where .= 'is_afn_channel = ' . (int)$value['afn'];
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
                $condition = 'second_table.online_regular_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'second_table.online_regular_price <= \''.(float)$value['to'].'\'';
            }

            $condition = '(' . $condition . ' AND
            (
                second_table.online_regular_price IS NOT NULL AND
                ((second_table.online_regular_sale_price_start_date IS NULL AND
                second_table.online_regular_sale_price_end_date IS NULL) OR
                second_table.online_regular_sale_price IS NULL OR
                second_table.online_regular_sale_price_start_date > CURRENT_DATE() OR
                second_table.online_regular_sale_price_end_date < CURRENT_DATE())
            )) OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'second_table.online_regular_sale_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'second_table.online_regular_sale_price <= \''.(float)$value['to'].'\'';
            }

            $condition .= ' AND
            (
                second_table.online_regular_price IS NOT NULL AND
                (second_table.online_regular_sale_price_start_date IS NOT NULL AND
                second_table.online_regular_sale_price_end_date IS NOT NULL AND
                second_table.online_regular_sale_price IS NOT NULL AND
                second_table.online_regular_sale_price_start_date < CURRENT_DATE() AND
                second_table.online_regular_sale_price_end_date > CURRENT_DATE())
            )) OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'online_business_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'second_table.online_business_price <= \''.(float)$value['to'].'\'';
            }

            $condition .= ' AND (second_table.online_regular_price IS NULL))';

        }

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== ''))
        {
            if (!empty($condition)) {
                $condition = '(' . $condition . ') OR ';
            }

            $condition .= 'is_repricing = ' . (int)$value['is_repricing'];
        }

        $collection->getSelect()->where($condition);
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
                array('action_id','action','type','description','create_date','initiator')
            )
            ->where('`listing_product_id` = ?', $listingProductId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(array('id DESC'))
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing\Log\Grid\LastActions')->setData([
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
            $data = array(
                'label'   => $this->__('Add New Child Product'),
                'onclick' => 'ListingProductVariationManageVariationsGridObj.showNewChildForm(' .
                    var_export(!$this->hasUnusedChannelVariations(), true) .
                    ', ' . $this->getListingProduct()->getId() . ')',
                'class'   => 'action primary',
                'style'   => 'position: absolute; margin-top: -32px;right: 27px;',
                'id'      => 'add_new_child_button'
            );
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('add_new_child_button', $buttonBlock);
            // ---------------------------------------

        }

        return $this->getChildHtml('add_new_child_button');
    }

    protected function isNewChildAllowed()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        if (!$amazonListingProduct->getGeneralId()) {
            return false;
        }

        if (!$amazonListingProduct->getVariationManager()->getTypeModel()->hasMatchedAttributes()) {
            return false;
        }

        if (!$this->hasUnusedProductVariation()) {
            return false;
        }

        if ($this->hasChildWithEmptyProductOptions()) {
            return false;
        }

        if (!$this->isGeneralIdOwner() && !$this->hasUnusedChannelVariations()) {
            return false;
        }

        if (!$this->isGeneralIdOwner() && $this->hasChildWithEmptyChannelOptions()) {
            return false;
        }

        return true;
    }

    public function isGeneralIdOwner()
    {
        return $this->getListingProduct()->getChildObject()->isGeneralIdOwner();
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

    public function hasUnusedChannelVariations()
    {
        return (bool)$this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUnusedChannelOptions();
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

    public function hasChildWithEmptyChannelOptions()
    {
        foreach ($this->getChildListingProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationChannelMatched()) {
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
        return $this->getUrl('*/amazon_listing_product_variation_manage/viewVariationsGridAjax', [
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
        'M2ePro/Amazon/Listing/Product/Variation/Manage/Tabs/Variations/Grid'
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonChildListingProduct */
        $amazonChildListingProduct = $childListingProduct->getChildObject();

        if (!$amazonChildListingProduct->getGeneralId()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $amazonChildListingProduct->getVariationManager()->getTypeModel();

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
            $tempArray = array(
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            );
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    //########################################

    protected function getTemplateDescriptionLinkHtml($listingProduct)
    {
        $templateDescriptionEditUrl = $this->getUrl('*/amazon_template_description/edit', array(
            'id' => $listingProduct->getChildObject()->getTemplateDescriptionId()
        ));

        $helper = $this->getHelper('Data');
        $templateTitle = $listingProduct->getChildObject()->getDescriptionTemplate()->getTitle();

        return <<<HTML
<span style="font-size: 9px;">{$helper->__('Description Title')}:&nbsp;
    <a target="_blank" href="{$templateDescriptionEditUrl}">
        {$helper->escapeHtml($templateTitle)}</a>
</span>
<br/>
HTML;
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

        return $this->prepareVariations($firstAttribute,$unusedVariations,$variationsSetsSorted);
    }

    private function prepareVariations($currentAttribute,$unusedVariations,$variationsSets,$filters = array())
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
                    $nextAttribute,$unusedVariations,$variationsSets,$filters
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
                    $return = array($currentAttribute => $values);

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

        if (!is_null($this->currentProductVariations)) {
            return $this->currentProductVariations;
        }

        $magentoProductVariations = $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();

        $productVariations = array();

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = array();

            foreach ($option as $attribute) {
                $productOption[$attribute['attribute']] = $attribute['option'];
            }

            $productVariations[] = $productOption;
        }

        return $this->currentProductVariations = $productVariations;
    }

    public function getUsedProductVariations()
    {
        if (is_null($this->usedProductVariations)) {
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

    private function isVariationExistsInArray(array $needle, array $haystack)
    {
        foreach ($haystack as $option) {
            if ($option != $needle) {
                continue;
            }

            return true;
        }

        return false;
    }

    //########################################

    public function getChildListingProducts()
    {
        if (!is_null($this->childListingProducts)) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getChildListingsProducts();
    }

    public function getAttributesVariationsSets($variations)
    {
        $attributesOptions = array();

        foreach ($variations as $variation) {
            foreach ($variation as $attr => $option) {
                if (!isset($attributesOptions[$attr])) {
                    $attributesOptions[$attr] = array();
                }
                if (!in_array($option, $attributesOptions[$attr])) {
                    $attributesOptions[$attr][] = $option;
                }
            }
        }

        return $attributesOptions;
    }

    //########################################

    protected function getGeneralIdLink($generalId)
    {
        $url = $this->getHelper('Component\Amazon')->getItemUrl(
            $generalId,
            $this->getListingProduct()->getListing()->getMarketplaceId()
        );

        return <<<HTML
<a href="{$url}" target="_blank" title="{$generalId}" >{$generalId}</a>
HTML;
    }

    //########################################

    private function parseGroupedData($data)
    {
        $result = array();

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
}