<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral;

use Ess\M2ePro\Model\Listing\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    private $lockedDataCache = array();

    private $parentAsins;

    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
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
        $this->setId('amazonListingViewSellercentralGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setListing($this->listing->getId());

        $collection->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem(array('qty' => 'qty', 'is_in_stock' => 'is_in_stock'));

        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array(
                'id'              => 'id',
                'amazon_status'   => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ),
            array(
                'listing_id' => (int)$this->listing['id'],
                'status' => array(
                    \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                    \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
                )
            )
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('alp' => $alpTable),
            'listing_product_id=id',
            array(
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
                'online_regular_sale_price_start_date'  => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date'    => 'online_regular_sale_price_end_date',
                'online_business_price'     => 'online_business_price',
                'online_business_discounts' => 'online_business_discounts',
                'is_afn_channel'                   => 'is_afn_channel',
                'is_repricing'                     => 'is_repricing',
                'is_general_id_owner'              => 'is_general_id_owner',
                'is_variation_parent'              => 'is_variation_parent',
                'variation_child_statuses'         => 'variation_child_statuses',
                'variation_parent_id'              => 'variation_parent_id',
                'variation_parent_afn_state'       => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',
                'defected_messages'                => 'defected_messages',
            ),
            '{{table}}.is_variation_parent = 0'
        );

        $collection->getSelect()->columns(array(
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
        ));

        $alprTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            array('malpr' => $alprTable),
            '(`alp`.`listing_product_id` = `malpr`.`listing_product_id`)',
            array(
                'is_repricing_disabled' => 'is_online_disabled',
            )
        );

        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'amazon_sku',
            'filter_index' => 'amazon_sku',
            'frame_callback' => array($this, 'callbackColumnAmazonSku')
        ));

        $this->addColumn('general_id', array(
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '140px',
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
            'width' => '110px',
            'type' => 'number',
            'index' => 'min_online_price',
            'filter_index' => 'min_online_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        );

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $this->listing->getAccount()->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'width' => '155px',
            'index' => 'amazon_status',
            'filter_index' => 'amazon_status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
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
        $groups = array(
            'actions'            => $this->__('Actions'),
            'edit_fulfillment'   => $this->__('Fulfillment')
        );

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled()) {
            $groups['edit_repricing'] = $this->__('Repricing Tool');
        }

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('revise', array(
            'label'    => $this->__('Revise Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'actions');

        $this->getMassactionBlock()->addItem('relist', array(
            'label'    => $this->__('Relist Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'actions');

        $this->getMassactionBlock()->addItem('stop', array(
            'label'    => $this->__('Stop Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', array(
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'actions');

        $this->getMassactionBlock()->addItem('deleteAndRemove', array(
            'label'    => $this->__('Remove from Channel & Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'actions');

        $this->getMassactionBlock()->addItem('switchToAfn', array(
            'label'    => $this->__('Switch to AFN'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'edit_fulfillment');

        $this->getMassactionBlock()->addItem('switchToMfn', array(
            'label'    => $this->__('Switch to MFN'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ), 'edit_fulfillment');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->listing->getAccount();

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $account->getChildObject()->isRepricing()) {

            $this->getMassactionBlock()->addItem('showDetails', array(
                'label' => $this->__('Show Details'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_repricing');

            $this->getMassactionBlock()->addItem('addToRepricing', array(
                'label' => $this->__('Add Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_repricing');

            $this->getMassactionBlock()->addItem('editRepricing', array(
                'label' => $this->__('Edit Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_repricing');

            $this->getMassactionBlock()->addItem('removeFromRepricing', array(
                'label' => $this->__('Remove Item(s)'),
                'url' => '',
                'confirm' => $this->__('Are you sure?')
            ), 'edit_repricing');
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        if (is_null($sku = $row->getData('sku'))) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

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

                $sortedOptions = array();

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

        $productOptions = array();
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

    public function callbackColumnAmazonSku($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        }

        if ($row->getData('defected_messages')) {
            $defectedMessages = $this->getHelper('Data')->jsonDecode($row->getData('defected_messages'));

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
    <img id="map_link_defected_message_icon_{$row->getId()}"
         class="tool-tip-image"
         style="vertical-align: middle;"
         src="{$this->getSkinUrl('M2ePro/images/warning.png')}">
    <span class="tool-tip-message tool-tip-warning tip-left" style="display:none;">
        <img src="{$this->getSkinUrl('M2ePro/images/i_notice.gif')}">
        <span>{$msg}</span>
    </span>
</span>
HTML;
        }

        return $value;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
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
                $this->__('child ASIN/ISBN<br/>of parent %parent_asin%',
                    $this->getParentAsin($row->getData('id'))) . '</span>';
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

    public function callbackColumnStockAvailability($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if ((bool)$row->getData('is_afn_channel')) {
            $sku = $row->getData('amazon_sku');

            if (empty($sku)) {
                return $this->__('AFN');
            }

            $productId = $row->getData('id');
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

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
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}',{$productId}, {$accountId})">{$afn}</a>
</div>
HTML;
        }

        if (is_null($value) || $value === '') {
            return '<i style="color:gray;">receiving...</i>';
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $onlinePrice = $row->getData('online_regular_price');

        $repricingHtml ='';

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (bool)(int)$row->getData('is_repricing')) {

            $icon = 'repricing-enabled';
            $text = $this->__(
                'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro. <br>
                 <strong>Please note</strong> that the Price value(s) shown in the grid might
                 be different from the actual one from Amazon. It is caused by the delay
                 in the values updating made via the Repricing Service'
            );

            if ((int)$row->getData('is_repricing_disabled') == 1) {
                $icon = 'repricing-disabled';
                $text = $this->__(
                    'This product is disabled on Amazon Repricing Tool.
                     The Price is updated through the M2E Pro.'
                );
            }

            $repricingHtml = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}" style="float:right;">
    {$this->getTooltipHtml($text)}
</div>
HTML;
        }

        $onlineBusinessPrice = $row->getData('online_business_price');

        if ((is_null($onlinePrice) || $onlinePrice === '') &&
            (is_null($onlineBusinessPrice) || $onlineBusinessPrice === '')
        ) {
            if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return $this->__('N/A') . $repricingHtml;
            } else {
                return '<i style="color:gray;">receiving...</i>' . $repricingHtml;
            }
        }

        $currency = $this->listing->getMarketplace()->getChildObject()->getDefaultCurrency();

        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);
        }

        if ($row->getData('is_repricing') && !$row->getData('is_repricing_disabled')) {
            $accountId = $this->listing['account_id'];
            $sku = $row->getData('amazon_sku');

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

        $salePrice = $row->getData('online_regular_sale_price');
        if ((float)$salePrice > 0) {
            $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

            $startDateTimestamp = strtotime($row->getData('online_regular_sale_price_start_date'));
            $endDateTimestamp   = strtotime($row->getData('online_regular_sale_price_end_date'));

            if ($currentTimestamp <= $endDateTimestamp) {
                $fromDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_start_date'), \IntlDateFormatter::MEDIUM
                );

                $toDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_end_date'), \IntlDateFormatter::MEDIUM
                );

                $intervalHtml = <<<HTML
<div class="m2epro-field-tooltip m2epro-field-tooltip-price-info admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        <span style="color:gray;">
            <strong>From:</strong> {$fromDate}<br/>
            <strong>To:</strong> {$toDate}
        </span>
    </div>
</div>
HTML;

                $salePriceValue = $this->convertAndFormatPriceCurrency($salePrice, $currency);

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$onlinePrice
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
                                  .$this->convertAndFormatPriceCurrency($onlineBusinessPrice, $currency);

            $businessDiscounts = $row->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {
                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->convertAndFormatPriceCurrency($price, $currency);
                    $discountsHtml .= 'QTY >= '.(int)$qty.', price '.$price.'<br />';
                }

                $businessPriceValue .= $this->getTooltipHtml($discountsHtml);
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

        $value .= $this->getViewLogIconHtml($row->getData('id'));

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
            array(
                array('attribute'=>'sku','like'=>'%'.$value.'%'),
                array('attribute'=>'name', 'like'=>'%'.$value.'%')
            )
        );
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

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== ''))
        {
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

    // ---------------------------------------

    public function getViewLogIconHtml($listingProductId)
    {
        $listingProductId = (int)$listingProductId;
        $availableActionsId = array_keys($this->getAvailableActions());

        $connection = $this->resourceConnection->getConnection();

        // Get last messages
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
                array('action_id','action','type','description','create_date','initiator')
            )
            ->where('`listing_product_id` = ?',$listingProductId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(array('id DESC'))
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing\Log\Grid\LastActions')->setData([
            'entity_id' => $listingProductId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'ListingGridHandlerObj.viewItemHelp',
            'hide_help_handler' => 'ListingGridHandlerObj.hideItemHelp',
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
            $tempArray = array(
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            );
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    //########################################

    private function getParentAsin($childId)
    {
        if (is_null($this->parentAsins)) {
            $connection = $this->resourceConnection->getConnection();
            $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
                ->getResource()->getMainTable();

            $select = $connection->select();
            $select->from(array('alp' => $tableAmazonListingProduct), array('listing_product_id','variation_parent_id'))
                ->where('listing_product_id IN (?)', $this->getCollection()->getAllIds())
                ->where('variation_parent_id IS NOT NULL');

            $parentIds = $connection->fetchPairs($select);

            $select = $connection->select();
            $select->from(array('alp' => $tableAmazonListingProduct), array('listing_product_id', 'general_id'))
                ->where('listing_product_id IN (?)', $parentIds);

            $parentAsins = $connection->fetchPairs($select);

            $this->parentAsins = array();
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