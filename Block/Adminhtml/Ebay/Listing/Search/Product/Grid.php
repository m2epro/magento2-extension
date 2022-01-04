<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Product\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingSearchM2eProGrid');

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
            ['lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
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
            ['elp' => $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable()],
            'listing_product_id=id',
            [
                'listing_product_id'    => 'listing_product_id',
                'ebay_item_id'          => 'ebay_item_id',
                'online_title'          => 'online_title',
                'online_sku'            => 'online_sku',
                'online_qty'            => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'online_qty_sold'       => 'online_qty_sold',
                'online_bids'           => 'online_bids',
                'online_start_price'    => 'online_start_price',
                'online_current_price'  => 'online_current_price',
                'online_reserve_price'  => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',

                'is_duplicate'          => 'is_duplicate',
            ]
        );
        $collection->joinTable(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'id=listing_id',
            [
                'store_id'              => 'store_id',
                'account_id'            => 'account_id',
                'marketplace_id'        => 'marketplace_id',
                'listing_title'         => 'title',
            ]
        );
        $collection->joinTable(
            ['em' => $this->activeRecordFactory->getObject('Ebay\Marketplace')->getResource()->getMainTable()],
            'marketplace_id=marketplace_id',
            [
                'currency' => 'currency',
            ]
        );
        $collection->joinTable(
            ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            'id=ebay_item_id',
            [
                'item_id' => 'item_id',
            ],
            null,
            'left'
        );

        $accountId = (int)$this->getRequest()->getParam('ebayAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('ebayMarketplace', false);

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

    protected function getColumnProductTitleAdditionalHtml($row)
    {
        $listingWord  = $this->__('Listing');
        $listingUrl   = $this->getUrl('*/ebay_listing/view', ['id' => $row->getData('listing_id')]);

        $listingTitle = $this->getHelper('Data')->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $html = <<<HTML
<strong> {$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $html .= '<br/><strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $row->getData('sku');
        $onlineSku = $row->getData('online_sku');

        !empty($onlineSku) && $sku = $onlineSku;
        $sku = $this->getHelper('Data')->escapeHtml($sku);

        $skuWord = $this->__('SKU');
        $html .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;{$sku}
HTML;

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = $row->getData('listing_product_id');
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if ($listingProduct->getChildObject()->isVariationsReady()) {
            $additionalData    = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
            $productAttributes = array_keys($additionalData['variations_sets']);
            $productAttributes = implode(', ', $productAttributes);

            $html .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">
    {$productAttributes}
</div>
HTML;
        }

        return $html;
    }

    //----------------------------------------

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('entity_id');

        $urlData = [
            'id'        => $row->getData('listing_id'),
            'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY,
            'filter'    => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}")
        ];

        $searchedChildHtml = '';
        if ($this->wasFoundByChild($row)) {
            $urlData['child_variation_ids'] = $this->getChildVariationIds($row);

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

        $manageUrl = $this->getUrl('*/ebay_listing/view/', $urlData);
        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$this->__('Go to Listing')}" target="_blank" href="{$manageUrl}">
    <img src="{$this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png')}" /></a>
</div>
HTML;
        return $searchedChildHtml . $html;
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
            'product_id_subQuery.listing_product_id=lp.id',
            [
                'product_id_child_variation_ids' => 'child_variation_ids',
                'product_id_searched_by_child'   => 'searched_by_child'
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
            'product_sku_subQuery.listing_product_id=lp.id',
            [
                'product_sku_child_variation_ids' => 'child_variation_ids',
                'product_sku_searched_by_child'   => 'searched_by_child'
            ]
        );

        $collection->addFieldToFilter([
            ['attribute'=>'sku','like'=>'%'.$value.'%'],
            ['attribute'=>'online_sku','like'=>'%'.$value.'%'],
            ['attribute'=>'name', 'like'=>'%'.$value.'%'],
            ['attribute'=>'online_title','like'=>'%'.$value.'%'],
            ['attribute'=>'listing_title','like'=>'%'.$value.'%'],
        ]);
        $collection->getSelect()->orWhere("(product_sku_subQuery.searched_by_child = '1')");
    }

    protected function callbackFilterOnlineQty($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $where = '';
        $onlineQty = 'elp.online_qty - elp.online_qty_sold';

        if (isset($cond['from']) || isset($cond['to'])) {
            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$onlineQty} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }
                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$onlineQty} <= {$value}";
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('online_current_price', $cond);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null) {
            return;
        }

        if (is_array($value) && isset($value['value'])) {
            $collection->addFieldToFilter($index, (int)$value['value']);
        } elseif (!is_array($value) && $value !== null) {
            $collection->addFieldToFilter($index, (int)$value);
        }

        if (isset($value['is_duplicate'])) {
            $collection->addFieldToFilter('is_duplicate', 1);
        }
    }

    protected function callbackFilterItemId($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('item_id', $cond);
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'online_qty') {
                // fix for wrong fields wrapping with "`" when statement in ()
                $onlineQty = 'IF(
                    1=1,
                    elp.online_qty - elp.online_qty_sold,
                    NULL
                )';
                $collection->getSelect()->order(
                    $onlineQty . ' ' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        return $this;
    }

    //########################################

    private function getMagentoChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection()
            ->addFieldToSelect('listing_product_variation_id')
            ->addFieldToFilter('main_table.component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        $collection->getSelect()->joinLeft(
            ['lpv' => $this->activeRecordFactory->getObject('Listing_Product_Variation')
                ->getResource()->getMainTable()],
            'lpv.id=main_table.listing_product_variation_id',
            ['listing_product_id']
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_variation_ids' => new \Zend_Db_Expr('GROUP_CONCAT(lpv.id)'),
                'listing_product_id'  => 'lpv.listing_product_id',
                'searched_by_child'   => new \Zend_Db_Expr(1)
            ]
        );

        $collection->getSelect()->group("lpv.listing_product_id");

        return $collection;
    }

    //########################################

    protected function wasFoundByChild($row)
    {
        foreach (['product_id', 'product_sku'] as $item) {
            $searchedByChild = $row->getData("{$item}_searched_by_child");
            if (!empty($searchedByChild)) {
                return true;
            }
        }

        return false;
    }

    protected function getChildVariationIds($row)
    {
        $ids = [];

        foreach (['product_id', 'product_sku'] as $item) {
            $itemIds = $row->getData("{$item}_child_variation_ids");
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
