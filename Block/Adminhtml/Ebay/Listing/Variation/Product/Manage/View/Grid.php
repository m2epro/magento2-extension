<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\View;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    const VARIATION_STATUS_ADD     = 1;
    const VARIATION_STATUS_DELETE  = 2;

    protected $localeCurrency;

    protected $resourceConnection;

    protected $customCollection;

    protected $ebayFactory;

    protected $variationAttributes;

    protected $listingProductId;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->customCollection = $customCollection;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    /**
     * @return mixed
     */
    public function getListingProductId()
    {
        return $this->listingProductId;
    }

    // ---------------------------------------

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    protected function getListingProduct()
    {
        if (empty($this->listingProduct)) {
            $this->listingProduct = $this->ebayFactory
                ->getObjectLoaded('Listing\Product', $this->getListingProductId());
        }

        return $this->listingProduct;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayVariationProductManageGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->listingProductId = $this->getHelper('Data\GlobalData')->getValue('listing_product_id');
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product\Variation')->getCollection();
        $collection->getSelect()->where('main_table.listing_product_id = ?',(int)$this->getListingProductId());
        $collection->getSelect()->group('main_table.id');
        // ---------------------------------------

        // Join variation option tables
        // ---------------------------------------
        $collection->getSelect()->join(
            array(
                'mlpvo' => $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
                    ->getResource()->getMainTable()
            ),
            '`mlpvo`.`listing_product_variation_id`=`main_table`.`id`',
            array()
        );

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            array(
                'id'                 => 'main_table.id',
                'listing_product_id' => 'main_table.listing_product_id',
                'additional_data'    => 'main_table.additional_data',
                'add'                => 'second_table.add',
                'delete'             => 'second_table.delete',
                'online_price'       => 'second_table.online_price',
                'online_sku'         => 'second_table.online_sku',
                'available_qty'      => new \Zend_Db_Expr('(second_table.online_qty - second_table.online_qty_sold)'),
                'online_qty_sold'    => 'second_table.online_qty_sold',
                'status'             => 'second_table.status',
                'attributes'       => 'GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`option` SEPARATOR \'||\')',
                'products_ids'     => 'GROUP_CONCAT(`mlpvo`.`attribute`, \'==\', `mlpvo`.`product_id` SEPARATOR \'||\')'
            )
        );

        $resultCollection = $this->customCollection->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            array('main_table' => $collection->getSelect()),
            array(
                'id',
                'listing_product_id',
                'additional_data',
                'add',
                'delete',
                'online_price',
                'available_qty',
                'online_sku',
                'online_qty_sold',
                'status',
                'attributes',
                'products_ids'
            )
        );

        // Set collection to grid
        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('variation', array(
            'header' => $this->__('Magento Variation'),
            'align' => 'left',
            'width' => '210px',
            'sortable' => false,
            'index' => 'attributes',
            'filter_index' => 'attributes',
            'frame_callback' => array($this, 'callbackColumnVariations'),
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => $this->getVariationsAttributes(),
            'filter_condition_callback' => array($this, 'callbackFilterVariations')
        ));

        $this->addColumn('online_sku', array(
            'header'    => $this->__('SKU'),
            'align'     => 'left',
            'width'     => '150px',
            'index'     => 'online_sku',
            'filter_index' => 'online_sku',
            'frame_callback' => array($this, 'callbackColumnOnlineSku')
        ));

        $this->addColumn('available_qty', array(
            'header'    => $this->__('Available QTY'),
            'align'     => 'right',
            'width'     => '40px',
            'type'      => 'number',
            'index'     => 'available_qty',
            'filter'    => false,
            'frame_callback' => array($this, 'callbackColumnAvailableQty')
        ));

        $this->addColumn('online_qty_sold', array(
            'header' => $this->__('Sold QTY'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'number',
            'index' => 'online_qty_sold',
            'frame_callback' => array($this, 'callbackColumnOnlineQtySold')
        ));

        $this->addColumn('price', array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
        ));

        $this->addColumn('identifiers', array(
            'header' => $this->__('eBay Catalog Identifiers'),
            'align' => 'left',
            'width' => '150px',
            'sortable' => false,
            'index' => 'additional_data',
            'filter_index' => 'additional_data',
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AttributesOptions',
            'options' => array(
                'upc'  => 'UPC',
                'ean'  => 'EAN',
                'isbn' => 'ISBN',
                'mpn'  => 'MPN'
            ),
            'frame_callback' => array($this, 'callbackColumnIdentifiers'),
            'filter_condition_callback' => array($this, 'callbackFilterIdentifiers')
        ));

        $this->addColumn('status', array(
            'header'=> $this->__('Status'),
            'width' => '60px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN     => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD       => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED   => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => $this->__('Pending')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnVariations($value, $row, $column, $isExport)
    {
        $attributes = $this->parseGroupedData($row->getData('attributes'));
        $productsIds = $this->parseGroupedData($row->getData('products_ids'));
        $uniqueProductsIds = count(array_unique($productsIds)) > 1;

        $html = '<div class="m2ePro-variation-attributes" style="margin-left: 5px;">';
        if (!$uniqueProductsIds) {
            $url = $this->getUrl('catalog/product/edit', array('id' => reset($productsIds)));
            $html .= '<a href="' . $url . '" target="_blank" style="color: grey;">';
        }
        foreach ($attributes as $attribute => $option) {
            $optionHtml = '<b>' . $this->getHelper('Data')->escapeHtml($attribute) .
                '</b>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option);

            if ($uniqueProductsIds) {
                $url = $this->getUrl('catalog/product/edit', array('id' => $productsIds[$attribute]));
                $html .= '<a href="' . $url . '" target="_blank" style="color: grey;">' . $optionHtml . '</a><br/>';
            } else {
                $html .= $optionHtml . '<br/>';
            }
        }
        if (!$uniqueProductsIds) {
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    public function callbackColumnOnlineSku($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        if ($row->getData('status') != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            return '<span style="color: gray; text-decoration: line-through;">' . $value . '</span>';
        }

        return $value;
    }

    public function callbackColumnOnlineQtySold($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED &&
            (is_null($value) || $value === '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $this->getListingProduct()->getMarketplace()->getChildObject()->getCurrency();

        $priceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($value);

        return $priceStr;
    }

    public function callbackColumnIdentifiers($value, $row, $column, $isExport)
    {
        $html = '';
        $formHtml = '';
        $variationId = $row->getData('id');
        $additionalData = $this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        $linkTitle = $this->__('Change');
        $linkContent = $this->__('Change');

        $html .= '<div id="variation_identifiers_' . $variationId .
            '" style="font-size: 12px; color: grey; margin-left: 7px">';
        if (!empty($additionalData['product_details'])) {
            foreach ($additionalData['product_details'] as $identifier => $identifierValue) {
                !$identifierValue && $identifierValue = '--';
                $html .= '<span><span><strong>' .
                    $this->getHelper('Data')->escapeHtml(strtoupper($identifier)) .
                    '</strong></span>:&nbsp;<span class="value">' .
                    $this->getHelper('Data')->escapeHtml($identifierValue) . '</span></span><br/>';
            }
        } else {
            $linkTitle = $this->__('Set');
            $linkContent = $this->__('Set');
        }
        $html .= '</div>';

        $options = $column->getOptions();
        foreach ($options as $optionKey => $optionVal) {
            $identifierValue = empty($additionalData['product_details'][$optionKey]) ?
                '' : $additionalData['product_details'][$optionKey];

            $formHtml .= <<<HTML
<div style="padding: 2px 0; overflow: hidden;">
    <span style="line-height: 2em;">{$optionVal} </span>
    <input type="text"
           name="product_details[{$optionKey}]"
           class="input-text admin__control-text M2ePro-{$optionKey}" value="{$identifierValue}"
        style="float: right;">
</div>
HTML;
        }

        $html .= <<<HTML
<div style="margin: 0px 7px;">
<form action="javascript:void(0);" id="variation_identifiers_edit_{$variationId}" style="display: none">
    {$formHtml}
    <input type="hidden" name="variation_id" value="{$variationId}">
    <button class="scalable action-primary confirm-btn"
            onclick="VariationsGridObj.confirmVariationIdentifiers(this, {$variationId})"
            style="margin-top: 3px; float: right; font-size: 12px;">{$this->__('Confirm')}</button>
    <a href="javascript:void(0);" class="scalable"
        onclick="VariationsGridObj.cancelVariationIdentifiers({$variationId})"
        style="margin: 10px 10px 0 0; float: right;">{$this->__('Cancel')}</a>
</form>
<div style="text-align: left;">
<a href="javascript:"
    id="edit_variations_{$variationId}"
    onclick="VariationsGridObj.editVariationIdentifiers(this, {$variationId})"
    title="{$linkTitle}">{$linkContent}</a>
</div>
</div>
HTML;

        return $html;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $html = '';

        switch ($row->getData('status')) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $html = '<span style="color: gray;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $html = '<span style="color: green;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $html = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $html = '<span style="color: brown;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $html = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $html = '<span style="color: blue;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $html = '<span style="color: orange;">'.$value.'</span>';
                break;

            default:
                break;
        }

        if ($row->getData('add')) {
            $html .= '<br/><span style="color: gray; font-size: 10px;">will be added</span>';
        }

        if ($row->getData('delete')) {
            $html .= '<br/><span style="color: gray; font-size: 10px;">will be deleted</span>';
        }

        return $html;
    }

    //########################################

    public function callbackFilterVariations($collection, $column)
    {
        $values = $column->getFilter()->getValue();

        if ($values == null && !is_array($values)) {
            return;
        }

        foreach ($values as $value) {
            if (is_array($value) && !empty($value['value'])) {
                $collection->getSelect()->where(
                    'attributes REGEXP "[[:space:]]*' . $value['attr'] .
                    '[[:space:]]*==[[:space:]]*' . $value['value'] . '[[:space:]]*"'
                );
            }
        }
    }

    public function callbackFilterIdentifiers($collection, $column)
    {
        $values = $column->getFilter()->getValue();

        if ($values == null && !is_array($values)) {
            return;
        }

        foreach ($values as $value) {
            if (is_array($value) && !empty($value['value'])) {
                $collection->addFieldToFilter(
                    'additional_data',
                    array('regexp'=> '"product_details":[^}]*'.$value['attr'].'[[:space:]]*":"[[:space:]]*' .
                        // trying to screen slashes that in json
                        addslashes(addslashes($value['value']).'[[:space:]]*'))
                );
            }
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_variation_product_manage/getGridHtml', array(
            'product_id' => $this->getListingProductId(),
            '_current' => true
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        $urls = array(
            'ebay_listing_variation_product_manage/setIdentifiers' => $this->getUrl(
                '*/ebay_listing_variation_product_manage/setIdentifiers'
            )
        );

        $urls = $this->getHelper('Data')->jsonEncode($urls);

        $this->js->addRequireJs([
            'vpmvg' => 'M2ePro/Ebay/Listing/VariationProductManageVariationsGrid'
        ], <<<JS
        M2ePro.url.add({$urls});

        window.VariationsGridObj = new EbayListingVariationProductManageVariationsGrid(
            'ebayVariationProductManageGrid'
        );

        setTimeout(function() {
            if (typeof VariationsGridObj != 'undefined') {
                VariationsGridObj.afterInitPage();
            }
        }, 350);
JS
);
        return parent::_toHtml();
    }

    //########################################

    private function getVariationsAttributes()
    {
        if (is_null($this->variationAttributes)) {
            $tableVariation = $this->activeRecordFactory->getObject('Listing\Product\Variation')
                ->getResource()->getMainTable();
            $tableOption = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
                ->getResource()->getMainTable();

            $select = $this->resourceConnection->getConnection()->select();
            $select->from(array('mlpv' => $tableVariation), array())
                ->join(
                    array('mlpvo' => $tableOption),
                    'mlpvo.listing_product_variation_id = mlpv.id',
                    array('attribute')
                )
                ->where('listing_product_id = ?', (int)$this->getListingProductId());

            $attributes = $this->resourceConnection->getConnection()->fetchCol($select);

            $this->variationAttributes = array_unique($attributes);
        }

        return $this->variationAttributes;
    }

    private function parseGroupedData($data)
    {
        $result = array();

        $variationData = explode('||', $data);
        foreach ($variationData as $variationAttribute) {
            $value = explode('==', $variationAttribute);
            $result[$value[0]] = $value[1];
        }

        return $result;
    }

    //########################################
}