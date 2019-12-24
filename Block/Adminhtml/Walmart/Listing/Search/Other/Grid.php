<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\Other;

use \Ess\M2ePro\Model\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\Other\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingSearchOtherGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('name')->setData('header', $this->__('Product Title / Product SKU'));
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->distinct();

        $collection->getSelect()->joinLeft(
            [
                'cpe' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_entity')
            ],
            '(cpe.entity_id = `main_table`.product_id)',
            ['sku' => 'sku']
        );

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'sku'                          => 'cpe.sku',
                'name'                         => 'second_table.title',
                'listing_title'                => new \Zend_Db_Expr('NULL'),
                'store_id'                     => new \Zend_Db_Expr(0),
                'account_id'                   => 'main_table.account_id',
                'marketplace_id'               => 'main_table.marketplace_id',
                'listing_product_id'           => new \Zend_Db_Expr('NULL'),
                'entity_id'                    => 'main_table.product_id',
                'listing_id'                   => new \Zend_Db_Expr('NULL'),
                'status'                       => 'main_table.status',
                'is_variation_parent'          => new \Zend_Db_Expr('NULL'),
                'variation_child_statuses'     => new \Zend_Db_Expr('NULL'),
                'online_sku'                   => 'second_table.sku',
                'gtin'                         => 'second_table.gtin',
                'upc'                          => 'second_table.upc',
                'ean'                          => new \Zend_Db_Expr('NULL'),
                'isbn'                         => new \Zend_Db_Expr('NULL'),
                'wpid'                         => 'second_table.wpid',
                'channel_url'                  => 'second_table.channel_url',
                'item_id'                      => 'second_table.item_id',
                'online_title'                 => new \Zend_Db_Expr('NULL'),
                'online_qty'                   => 'second_table.online_qty',
                'online_price'                 => 'second_table.online_price',
                'online_sale_price'            => new \Zend_Db_Expr('NULL'),
                'online_sale_price_start_date' => new \Zend_Db_Expr('NULL'),
                'online_sale_price_end_date'   => new \Zend_Db_Expr('NULL'),
                'is_online_price_invalid'      => 'second_table.is_online_price_invalid',
            ]
        );

        $accountId = (int)$this->getRequest()->getParam('walmartAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('walmartMarketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');

        if ($title === null || $title === '') {
            $value = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else {
            $value = '<span>' .$this->getHelper('Data')->escapeHtml($title). '</span>';
        }

        $value = '<div style="margin-bottom: 5px">' . $value . '</div>';

        $account = $this->walmartFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->walmartFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $value .= '<strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $row->getData('sku');
        if (!empty($sku)) {
            $sku = $this->getHelper('Data')->escapeHtml($sku);
            $skuWord = $this->__('SKU');

            $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
{$sku}
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        return $this->getProductStatus($row->getData('status'));
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc  = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/walmart_listing_other/view/', [
            'account'     => $row->getData('account_id'),
            'marketplace' => $row->getData('marketplace_id'),
            'filter'      => base64_encode(
                'title=' . $row->getData('online_sku')
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
        if ($value === null || $value === '' ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
             !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $this->walmartFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'))
            ->getChildObject()
            ->getDefaultCurrency();

        return $this->convertAndFormatPriceCurrency($value, $currency);
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

        $collection->getSelect()->where('second_table.title LIKE ? OR cpe.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.sku LIKE ?', '%'.$value.'%');
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

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
second_table.gtin LIKE '%{$value}%' OR
second_table.upc LIKE '%{$value}%' OR
second_table.wpid LIKE '%{$value}%' OR
second_table.item_id LIKE '%{$value}%'
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
            $where .= 'second_table.online_qty >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= 'second_table.online_qty <= ' . $quoted;
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('status = ?', $value);
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}
