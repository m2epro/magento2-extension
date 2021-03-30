<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Product\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\AbstractGrid
{
    private $parentAndChildReviseScheduledCache = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingSearchProductGrid');

        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
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
                'amazon_status'   => 'status',
                'component_mode'  => 'component_mode',
                'listing_id'      => 'listing_id',
                'additional_data' => 'additional_data',
            ]
        );
        $collection->joinTable(
            [
                'alp' => $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable()
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
                'malpr' => $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                    ->getResource()->getMainTable()
            ],
            'listing_product_id=listing_product_id',
            [
                'is_repricing_disabled' => 'is_online_disabled',
            ],
            null,
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

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');
        $title = $this->getHelper('Data')->escapeHtml($title);

        $listingWord  = $this->__('Listing');
        $listingTitle = $this->getHelper('Data')->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $listingUrl = $this->getUrl(
            '*/amazon_listing/view',
            ['id' => $row->getData('listing_id')]
        );

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
        $value = $this->getProductStatus($row->getData('amazon_status'));

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            return $value . $this->getScheduledTag($row) . $this->getLockedTag($row);
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
            return $this->getProductStatus($sNotListed).$this->getScheduledTag($row).$this->getLockedTag($row);
        }

        $sortedStatuses     = [];
        $variationsStatuses = $this->getHelper('Data')->jsonDecode($variationsStatuses);

        isset($variationsStatuses[$sUnknown]) && $sortedStatuses[$sUnknown]     = $variationsStatuses[$sUnknown];
        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed]) && $sortedStatuses[$sListed]       = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sStopped]) && $sortedStatuses[$sStopped]     = $variationsStatuses[$sStopped];
        isset($variationsStatuses[$sBlocked]) && $sortedStatuses[$sBlocked]     = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {
            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '['.$productsCount.']';
            $html .= $this->getProductStatus($status) . '&nbsp;'. $productsCount . '<br/>';
        }

        return $html . $this->getScheduledTag($row) . $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('entity_id');

        $urlData = [
            'id'     => $row->getData('listing_id'),
            'filter' => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}")
        ];

        $searchedChildHtml = '';
        if ($this->wasFoundByChild($row)) {
            $urlData['child_listing_product_ids'] = $this->getChildListingProductIds($row);

            $searchedChildHtml = <<<HTML
<br/>
<div class="fix-magento-tooltip searched_child_product" style="margin-top: 4px; padding-left: 10px;">
    {$this->getTooltipHtml($this->__(
                'A Product you are searching for is found as part of a Multi-Variational Product.' .
                ' Click on the arrow icon to manage it individually.'
            ))}
</div>
HTML;
        }
        $manageUrl = $this->getUrl('*/amazon_listing/view/', $urlData);
        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$this->__('Go to Listing')}" target="_blank" href="{$manageUrl}">
    <img src="{$this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png')}" alt="{$this->__('Go to Listing')}" /></a>
</div>
HTML;

        return $searchedChildHtml . $html;
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

    protected function getScheduledTag($row)
    {
        $html = '';

        /**
         * @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $scheduledActionsCollection
         */
        $scheduledActionsCollection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
            ->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', $row['id']);

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionsCollection->getFirstItem();

        if (!$scheduledAction->getId()) {
            return $html;
        }

        switch ($scheduledAction->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $html .= '<br/><span style="color: #605fff">[List is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $html .= '<br/><span style="color: #605fff">[Relist is Scheduled...]</span>';
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
                    $html .= '<br/><span style="color: #605fff">[Revise of '.implode(', ', $reviseParts)
                        .' is Scheduled...]</span>';
                } else {
                    $html .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $html .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $html .= '<br/><span style="color: #605fff">[Delete is Scheduled...]</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->addFieldToFilter('product_id', $cond);

        $collection->getSelect()->joinLeft(
            ['product_id_subQuery' => $childCollection->getSelect()],
            'product_id_subQuery.variation_parent_id=lp.id',
            [
                'product_id_child_listing_product_ids' => 'child_listing_product_ids',
                'product_id_searched_by_child'         => 'searched_by_child'
            ]
        );

        $collection->addFieldToFilter('entity_id', $cond);
        $collection->getSelect()->orWhere("(product_id_subQuery.searched_by_child = '1')");
    }

    protected function callbackFilterTitle($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->getSelect()->joinLeft(
            ['cpe' => $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('catalog_product_entity')],
            'cpe.entity_id=main_table.product_id',
            []
        );
        $childCollection->addFieldToFilter('cpe.sku', ['like' => '%'.$value.'%']);

        $collection->getSelect()->joinLeft(
            ['product_sku_subQuery' => $childCollection->getSelect()],
            'product_sku_subQuery.variation_parent_id=lp.id',
            [
                'product_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'product_sku_searched_by_child'         => 'searched_by_child'
            ]
        );

        $collection->addFieldToFilter(
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%'],
                ['attribute'=>'listing_title','like'=>'%'.$value.'%'],
            ]
        );
        $collection->getSelect()->orWhere("(product_sku_subQuery.searched_by_child = '1')");
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $childCollection = $this->getChildProductsCollection();
        $childCollection->addFieldToFilter('sku', ['like' => '%'.$value.'%']);

        $collection->getSelect()->joinLeft(
            ['online_sku_subQuery' => $childCollection->getSelect()],
            'online_sku_subQuery.variation_parent_id=lp.id',
            [
                'online_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'online_sku_searched_by_child'         => 'searched_by_child'
            ]
        );

        $collection->addFieldToFilter('online_sku', ['like' => '%'.$value.'%']);
        $collection->getSelect()->orWhere("(online_sku_subQuery.searched_by_child = '1')");
    }

    protected function callbackFilterAsinIsbn($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $childCollection = $this->getChildProductsCollection();
        $childCollection->addFieldToFilter('general_id', ['like' => '%'.$value.'%']);

        $collection->getSelect()->joinLeft(
            ['asin_subQuery' => $childCollection->getSelect()],
            'asin_subQuery.variation_parent_id=lp.id',
            [
                'asin_child_listing_product_ids' => 'child_listing_product_ids',
                'asin_searched_by_child'         => 'searched_by_child'
            ]
        );

        $collection->addFieldToFilter('general_id', ['like' => '%'.$value.'%']);
        $collection->getSelect()->orWhere("(asin_subQuery.searched_by_child = '1')");
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

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() && isset($cond['is_repricing'])) {
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
                    $onlineCurrentPrice . ' ' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        return $this;
    }

    //########################################

    protected function getMagentoChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection()
            ->addFieldToSelect('listing_product_variation_id')
            ->addFieldToFilter('main_table.component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);

        $collection->getSelect()->joinLeft(
            ['lpv' => $this->activeRecordFactory->getObject('Listing_Product_Variation')
                ->getResource()->getMainTable()],
            'lpv.id=main_table.listing_product_variation_id',
            ['listing_product_id']
        );
        $collection->getSelect()->joinLeft(
            ['alp' =>  $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable()],
            'alp.listing_product_id=lpv.listing_product_id',
            ['variation_parent_id']
        );
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT alp.listing_product_id)'),
                'variation_parent_id'       => 'alp.variation_parent_id',
                'searched_by_child'         => new \Zend_Db_Expr('1')
            ]
        );

        $collection->getSelect()->group('alp.variation_parent_id');

        return $collection;
    }

    protected function getChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getCollection()
            ->addFieldToFilter('variation_parent_id', ['notnull' => true])
            ->addFieldToFilter('is_variation_product', 1);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(listing_product_id)'),
                'variation_parent_id'       => 'variation_parent_id',
                'searched_by_child'         => new \Zend_Db_Expr('1')
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        return $collection;
    }

    //########################################

    protected function wasFoundByChild($row)
    {
        foreach (['product_id', 'product_sku', 'online_sku', 'asin'] as $item) {
            $searchedByChild = $row->getData("{$item}_searched_by_child");
            if (!empty($searchedByChild)) {
                return true;
            }
        }

        return false;
    }

    protected function getChildListingProductIds($row)
    {
        $ids = [];

        foreach (['product_id', 'product_sku', 'online_sku', 'asin'] as $item) {
            $itemIds = $row->getData("{$item}_child_listing_product_ids");
            if (empty($itemIds)) {
                continue;
            }

            foreach (explode(',', $itemIds) as $itemId) {
                !isset($ids[$itemId]) && $ids[$itemId] = 0;
                $ids[$itemId]++;
            }
        }

        $maxCount = max($ids);
        foreach ($ids as $id => $count) {
            if ($count < $maxCount) {
                unset($ids[$id]);
            }
        }

        return implode(',', array_keys($ids));
    }

    //########################################
}
