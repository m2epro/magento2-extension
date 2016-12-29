<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Other;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingSearchOtherGrid');
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
        $listingOtherCollection = $this->ebayFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->getSelect()->distinct();

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns([
            'store_id'              => new \Zend_Db_Expr(0),
            'account_id'            => 'main_table.account_id',
            'marketplace_id'        => 'main_table.marketplace_id',
            'entity_id'             => 'main_table.product_id',
            'name'                  => 'second_table.title',
            'sku'                   => 'second_table.sku',
            'currency'              => 'second_table.currency',
            'item_id'               => 'second_table.item_id',
            'listing_product_id'    => new \Zend_Db_Expr('NULL'),
            'listing_other_id'      => 'main_table.id',
            'additional_data'       => new \Zend_Db_Expr('NULL'),
            'status'                => 'main_table.status',
            'online_sku'            => new \Zend_Db_Expr('NULL'),
            'online_title'          => new \Zend_Db_Expr('NULL'),
            'online_qty'            => new \Zend_Db_Expr('(second_table.online_qty - second_table.online_qty_sold)'),
            'online_qty_sold'       => 'second_table.online_qty_sold',
            'online_bids'           => new \Zend_Db_Expr('NULL'),
            'online_start_price'    => new \Zend_Db_Expr('NULL'),
            'online_current_price'  => 'second_table.online_price',
            'online_reserve_price'  => new \Zend_Db_Expr('NULL'),
            'online_buyitnow_price' => new \Zend_Db_Expr('NULL'),
            'listing_id'            => new \Zend_Db_Expr('NULL'),
            'listing_title'         => new \Zend_Db_Expr('NULL'),
        ]);

        $accountId     = (int)$this->getRequest()->getParam('ebayAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('ebayMarketplace', false);

        if ($accountId) {
            $listingOtherCollection->getSelect()->where('account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $listingOtherCollection->getSelect()->where('marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($listingOtherCollection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('name')->setData('header', $this->__('Product Title / Product SKU'));
    }

    //########################################

    protected function getColumnProductTitleAdditionalHtml($row)
    {
        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $html = '<strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $row->getData('sku');
        if (is_null($sku) && !is_null($row->getData('product_id'))) {

            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('product_id'))
                ->getSku();
        }

        if (is_null($sku)) {
            $sku = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else if ($sku === '') {
            $sku = '<i style="color:gray;">' . $this->__('none') . '</i>';
        } else {
            $sku = $this->getHelper('Data')->escapeHtml($sku);
        }

        return $html . '<br/><strong>' . $this->__('SKU') . ':</strong>&nbsp;' . $sku;
    }

    // ---------------------------------------

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle  = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc   = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/ebay_listing_other/view/', [
            'account'     => $row->getData('account_id'),
            'marketplace' => $row->getData('marketplace_id'),
            'filter'      => base64_encode(
                'item_id=' . $row->getData('item_id')
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
        $objectId = $row->getData('listing_other_id');
        $object   = $this->ebayFactory->getObjectLoaded('Listing\Other', $objectId);
        return $object->getProcessingLocks();
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('main_table.product_id', $cond);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterOnlineQty($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter(new \Zend_Db_Expr(
            'second_table.online_qty - second_table.online_qty_sold'), $cond
        );
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('second_table.online_price', $cond);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('main_table.status', $cond);
    }

    //########################################
}