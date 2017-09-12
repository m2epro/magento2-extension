<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingSearchProductGrid');
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
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
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
                'alp' => $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable()
            ],
            'listing_product_id=id',
            [
                'listing_product_id'           => 'listing_product_id',
                'is_general_id_owner'          => 'is_general_id_owner',
                'general_id'                   => 'general_id',
                'is_repricing'                 => 'is_repricing',
                'is_afn_channel'               => 'is_afn_channel',
                'variation_parent_id'          => 'variation_parent_id',
                'is_variation_parent'          => 'is_variation_parent',
                'variation_child_statuses'     => 'variation_child_statuses',
                'online_sku'                   => 'sku',
                'online_qty'                   => 'online_qty',
                'online_regular_price'         => 'online_regular_price',
                'online_regular_sale_price'    => 'online_regular_sale_price',
                'online_regular_sale_price_start_date' => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date'   => 'online_regular_sale_price_end_date',

                'online_business_price'        => 'online_business_price',

                'variation_parent_afn_state'       => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',

                'online_current_price' => new \Zend_Db_Expr('IF(
                    alp.online_regular_sale_price_start_date IS NOT NULL AND
                    alp.online_regular_sale_price_end_date IS NOT NULL AND
                    alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                    alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                    alp.online_regular_sale_price,
                    alp.online_regular_price
                )')
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
        $collection->joinTable(
            [
                'malpr' => $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
                    ->getResource()->getMainTable()
            ],
            'listing_product_id=listing_product_id',
            [
                'is_repricing_disabled' => 'is_online_disabled',
            ],
            NULL,
            'left'
        );

        $accountId = (int)$this->getRequest()->getParam('amazonAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('amazonMarketplace', false);

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

        $listingUrl = $this->getUrl('*/amazon_listing/view',
            ['id' => $row->getData('listing_id')]);

        $value = <<<HTML
<div style="margin-bottom: 5px">{$title}</div>
<strong>{$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->amazonFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

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
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
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

                    } else if (in_array($attribute, array_keys($virtualChannelAttributes))) {

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
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            return $value .
            $this->getLockedTag($row);
        }

        $html = '';

        $sUnknown   = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
        $sNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        $sListed    = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        $sStopped   = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        $sBlocked   = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

        $generalId = $listingProduct->getChildObject()->getGeneralId();
        $variationsStatuses = $row->getData('variation_child_statuses');

        if (empty($generalId) || empty($variationsStatuses)) {

            return $this->getProductStatus($sNotListed).
            $this->getLockedTag($row);
        }

        $sortedStatuses     = [];
        $variationsStatuses = $this->getHelper('Data')->jsonDecode($variationsStatuses);

        isset($variationsStatuses[$sUnknown])   && $sortedStatuses[$sUnknown]   = $variationsStatuses[$sUnknown];
        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed])    && $sortedStatuses[$sListed]    = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sStopped])   && $sortedStatuses[$sStopped]   = $variationsStatuses[$sStopped];
        isset($variationsStatuses[$sBlocked])   && $sortedStatuses[$sBlocked]   = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {

            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '['.$productsCount.']';
            $html .= $this->getProductStatus($status) . '&nbsp;'. $productsCount . '<br/>';
        }

        return $html .
        $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc  = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/amazon_listing/view/', [
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

    //----------------------------------------

    protected function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

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

                case 'switch_to_afn_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Switch to AFN in Progress')
                        . '...]</span>';
                    break;

                case 'switch_to_mfn_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . $this->__('Switch to MFN in Progress')
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

        $collection->getSelect()->where('alp.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $onlineCurrentPrice = 'IF (
            alp.online_regular_price IS NULL,
            alp.online_business_price,
            IF(
                alp.online_regular_sale_price_start_date IS NOT NULL AND
                alp.online_regular_sale_price_end_date IS NOT NULL AND
                alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                alp.online_regular_sale_price,
                alp.online_regular_price
            )
        )';

        $where = '';

        if (isset($cond['from']) || isset($cond['to'])) {

            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$onlineCurrentPrice} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }
                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$onlineCurrentPrice} <= {$value}";
            }
        }

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() && isset($cond['is_repricing'])) {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }

            if ((int)$cond['is_repricing'] == 1) {
                $where .= 'alp.is_repricing = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL;
                $where .= "(alp.is_repricing = 0 OR alp.variation_parent_repricing_state = {$partialFilter})";
            }
        }

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

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {

            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'online_current_price') {
                $onlineCurrentPrice = 'IF(
                    alp.online_regular_sale_price_start_date IS NOT NULL AND
                    alp.online_regular_sale_price_end_date IS NOT NULL AND
                    alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                    alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                    alp.online_regular_sale_price,
                    alp.online_regular_price
                )';
                $collection->getSelect()->order(
                    '('. $onlineCurrentPrice .')' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        return $this;
    }

    //########################################
}