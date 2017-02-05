<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingSearchM2eProGrid');
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
            ['elp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable()],
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
            NULL,
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
        $altTitle  = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc   = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/ebay_listing/view/', [
            'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY,
            'id'        => $row->getData('listing_id'),
            'filter'    => base64_encode(
                'product_id[from]='.(int)$row->getData('entity_id')
                .'&product_id[to]='.(int)$row->getData('entity_id')
            )
        ]);

        return <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$manageUrl}"><img src="{$iconSrc}" /></a>
</div>
HTML;
    }

    protected function getProcessingLocks($row)
    {
        $objectId = $row->getData('listing_product_id');
        $object = $this->ebayFactory->getObjectLoaded('Listing\Product', $objectId);
        return $object->getProcessingLocks();
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

        $collection->addFieldToFilter([
            ['attribute'=>'sku','like'=>'%'.$value.'%'],
            ['attribute'=>'online_sku','like'=>'%'.$value.'%'],
            ['attribute'=>'name', 'like'=>'%'.$value.'%'],
            ['attribute'=>'online_title','like'=>'%'.$value.'%'],
            ['attribute'=>'listing_title','like'=>'%'.$value.'%'],
        ]);
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
        } elseif (!is_array($value) && !is_null($value)) {
            $collection->addFieldToFilter($index, (int)$value);
        }

        if (isset($value['is_duplicate'])) {
            $collection->addFieldToFilter('is_duplicate' , 1);
        }
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {

            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'online_qty') {
                $collection->getSelect()->order(
                    '(elp.online_qty - elp.online_qty_sold) ' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }
        return $this;
    }

    //########################################
}