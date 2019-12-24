<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\Product\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingSearchProductGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->distinct();
        $collection->setListingProductModeOn();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            [
                'lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()
            ],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'status'          => 'status',
                'component_mode'  => 'component_mode',
                'listing_id'      => 'listing_id',
                'additional_data' => 'additional_data',
            ]
        );
        $collection->joinTable(
            [
                'wlp' => $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable()
            ],
            'listing_product_id=id',
            [
                'listing_product_id'           => 'listing_product_id',
                'listing_other_id'             => new \Zend_Db_Expr('NULL'),
                'variation_parent_id'          => 'variation_parent_id',
                'is_variation_parent'          => 'is_variation_parent',
                'variation_child_statuses'     => 'variation_child_statuses',
                'online_sku'                   => 'sku',
                'gtin'                         => 'gtin',
                'upc'                          => 'upc',
                'ean'                          => 'ean',
                'isbn'                         => 'isbn',
                'wpid'                         => 'wpid',
                'channel_url'                  => 'channel_url',
                'item_id'                      => 'item_id',
                'online_qty'                   => 'online_qty',
                'online_price'                 => 'online_price',
                'is_online_price_invalid'      => 'is_online_price_invalid',
            ],
            'variation_parent_id IS NULL'
        );
        $collection->joinTable(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'id=listing_id',
            [
                'store_id'       => 'store_id',
                'account_id'     => 'account_id',
                'marketplace_id' => 'marketplace_id',
                'listing_title'  => 'title',
            ]
        );

        $accountId = (int)$this->getRequest()->getParam('walmartAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('walmartMarketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('l.account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');
        $title = $this->getHelper('Data')->escapeHtml($title);

        $listingWord  = $this->__('Listing');
        $listingTitle = $this->getHelper('Data')->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $listingUrl = $this->getUrl(
            '*/walmart_listing/view',
            ['id' => $row->getData('listing_id')]
        );

        $value = <<<HTML
<div style="margin-bottom: 5px">{$title}</div>
<strong>{$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->walmartFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->walmartFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $value .= '<br/><strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku     = $this->getHelper('Data')->escapeHtml($row->getData('sku'));
        $skuWord = $this->__('SKU');

        $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
{$sku}
HTML;

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isVariationParent()) {
            $productAttributes = $variationManager->getTypeModel()->getProductAttributes();

            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

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

            $value .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey; margin-top: 15px">
    {$attributesStr}
</div>
HTML;
        }

        if ($variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        ) {
            $optionsStr = '';
            $productOptions = $variationManager->getTypeModel()->getProductOptions();

            foreach ($productOptions as $attribute => $option) {
                $attribute = $this->getHelper('Data')->escapeHtml($attribute);
                !$option && $option = '--';
                $option = $this->getHelper('Data')->escapeHtml($option);

                $optionsStr .= <<<HTML
<strong>{$attribute}</strong>:&nbsp;{$option}<br/>
HTML;
            }

            $value .= <<<HTML
<br/>
<div style="font-size: 11px; color: grey;">
    {$optionsStr}
</div>
<br/>
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $value = $this->getProductStatus($row->getData('status'));

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();
            return $value . $this->getStatusChangeReasons($statusChangeReasons) .
                            $this->getLockedTag($row);
        }

        $html = '';

        $sNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        $sListed    = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        $sStopped   = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        $sBlocked   = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

        $variationsStatuses = $row->getData('variation_child_statuses');

        if (empty($variationsStatuses)) {
            return $this->getProductStatus($row->getData('status')) .
                   $this->getLockedTag($row);
        }

        $sortedStatuses     = [];
        $variationsStatuses = $this->getHelper('Data')->jsonDecode($variationsStatuses);

        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed]) && $sortedStatuses[$sListed]    = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sStopped]) && $sortedStatuses[$sStopped]   = $variationsStatuses[$sStopped];
        isset($variationsStatuses[$sBlocked]) && $sortedStatuses[$sBlocked]   = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {
            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '['.$productsCount.']';
            $html .= $this->getProductStatus($status) . '&nbsp;'. $productsCount . '<br/>';
        }

        return $html . $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc  = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/walmart_listing/view/', [
            'id' => $row->getData('listing_id'),
            'filter' => base64_encode(
                'product_id[from]='.(int)$row->getData('entity_id')
                .'&product_id[to]='.(int)$row->getData('entity_id')
            )
        ]);

        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$manageUrl}"><img src="{$iconSrc}" alt="{$altTitle}" /></a>
</div>
HTML;

        return $html;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') &&
            $row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $currentOnlinePrice = (float)$row->getData('online_price');

        if (empty($currentOnlinePrice) ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
             !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        $marketplaceId = $row->getData('marketplace_id');
        $currency = $this->walmartFactory->getObjectLoaded('Marketplace', $marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $iconHelpPath    = $this->getSkinUrl('M2ePro/images/i_logo.png');
            $toolTipIconPath = $this->getSkinUrl('M2ePro/images/i_icon.png');

            $noticeText = $this->__('The value is calculated as minimum price of all Child Products.');

            $priceHtml = <<<HTML
<img class="tool-tip-image" style="vertical-align: middle;" src="{$toolTipIconPath}"
    >&nbsp;<span class="tool-tip-message" style="display:none; text-align: left; width: 110px; background: #E3E3E3;">
    <img src="{$iconHelpPath}">
    <span style="color:gray;">
        {$noticeText}
    </span>
</span>
HTML;

            if (!empty($currentOnlinePrice)) {
                $currentOnlinePrice = $this->convertAndFormatPriceCurrency($currentOnlinePrice, $currency);
                $priceHtml .= "<span>{$currentOnlinePrice}</span><br />";
            }

            return $priceHtml;
        }

        $onlinePrice = $row->getData('online_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);
        }

        return $priceValue;
    }

    // ----------------------------------------

    private function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $tempLocks = $listingProduct->getProcessingLocks();

        $html = '';
        $childCount = 0;

        foreach ($tempLocks as $lock) {
            switch ($lock->getTag()) {
                case 'list_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('List in Progress')
                        . '...]</span>';
                    break;

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Relist in Progress')
                        . '...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Revise in Progress')
                        . '...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Stop in Progress')
                        . '...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Stop And Remove in Progress')
                        . '...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Remove in Progress')
                        . '...]</span>';
                    break;

                case 'child_products_in_action':
                    $childCount++;
                    break;

                default:
                    break;
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[' . $this->__('Child(s) in Action') . '...]</span>';
        }

        return $html;
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('entity_id', $cond);
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
                ['attribute'=>'name', 'like'=>'%'.$value.'%'],
                ['attribute'=>'listing_title','like'=>'%'.$value.'%'],
            ]
        );
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('wlp.sku LIKE ?', '%'.$value.'%');
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

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $where .= 'online_qty >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= 'online_qty <= ' . $quoted;
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
                $condition = 'wlp.online_price >= \'' . (float)$value['from'] . '\'';
            }

            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'wlp.online_price <= \'' . (float)$value['to'] . '\'';
            }
        }

        $collection->getSelect()->where($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "status = {$value} OR (variation_child_statuses REGEXP '\"{$value}\":[^0]') AND is_variation_parent = 1"
        );
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}
