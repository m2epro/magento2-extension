<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SearchAsin;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    const SEARCH_SETTINGS_STATUS_NONE = 'none';
    const SEARCH_SETTINGS_STATUS_COMPLETED = 'completed';

    /** @var \Ess\M2ePro\Model\Listing */
    private $listing = NULL;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->amazonFactory->getCachedObjectLoaded('Listing',$this->getRequest()->getParam('id'));

        // Initialization block
        // ---------------------------------------
        $this->setId('searchAsinForListingProductsGrid'.$this->listing['id']);
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_listing_products_ids');

        // Get collection
        // ---------------------------------------
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setStoreId($this->listing['store_id'])
            ->setListingProductModeOn()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');
        $collection->joinTable(
            array('cisi' => 'cataloginventory_stock_item'),
            'product_id=entity_id',
            array(
                'qty' => 'qty'
            ),
            '{{table}}.stock_id=1',
            'left'
        );

        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array(
                'id'              => 'id',
                'component_mode'  => 'component_mode',
                'amazon_status'   => 'status',
                'additional_data' => 'additional_data'
            ),
            '{{table}}.listing_id='.(int)$this->listing['id']
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('alp' => $alpTable),
            'listing_product_id=id',
            array(
                'general_id'                     => 'general_id',
                'general_id_search_info'         => 'general_id_search_info',
                'search_settings_status'         => 'search_settings_status',
                'search_settings_data'           => 'search_settings_data',
                'variation_child_statuses'       => 'variation_child_statuses',
                'amazon_sku'                     => 'sku',
                'online_qty'                     => 'online_qty',
                'online_regular_price'           => 'online_regular_price',
                'online_regular_sale_price'      => 'online_regular_sale_price',
                'is_afn_channel'                 => 'is_afn_channel',
                'is_general_id_owner'            => 'is_general_id_owner',
                'is_variation_parent'            => 'is_variation_parent',
            ),
            '{{table}}.variation_parent_id is NULL'
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);

        // ---------------------------------------

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
            'filter_index' => 'entity_id',
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

        $this->addColumn('general_id', array(
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '140px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        if ($this->listing->getChildObject()->isGeneralIdAttributeMode() ||
            $this->listing->getChildObject()->isWorldwideIdAttributeMode()) {

            $this->addColumn('settings', array(
                'header' => $this->__('Search Settings Values'),
                'align' => 'left',
                'width' => '240px',
                'filter'    => false,
                'sortable'  => false,
                'type' => 'text',
                'index' => 'id',
                'frame_callback' => array($this, 'callbackColumnSettings')
            ));
        }

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'width' => '200px',
            'index' => 'search_settings_status',
            'filter_index' => 'search_settings_status',
            'sortable'  => false,
            'type' => 'options',
            'options' => array(
                self::SEARCH_SETTINGS_STATUS_NONE => $this->__('None'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS =>
                    $this->__('In Progress'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND =>
                    $this->__('Not Found'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED =>
                    $this->__('Action Required'),
                self::SEARCH_SETTINGS_STATUS_COMPLETED => $this->__('Completed')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus'),
            'filter_condition_callback' => array($this, 'callbackFilterStatus')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('assignGeneralId', array(
            'label'    => $this->__('Search ASIN/ISBN automatically'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('unassignGeneralId', array(
            'label'    => $this->__('Reset ASIN/ISBN information'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ));
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$value;
        $storeId = (int)$this->listing['store_id'];

        $withoutImageHtml = '<a href="'
            .$this->getUrl('catalog/product/edit',
                array('id' => $productId))
            .'" target="_blank">'
            .$productId
            .'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/view/','show_products_thumbnails');
        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $imageResized = $magentoProduct->getThumbnailImage();
        if (is_null($imageResized)) {
            return $withoutImageHtml;
        }

        $imageResizedUrl = $imageResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px">'.
            '<img style="max-width: 100px; max-height: 100px;" src="' .$imageResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }

        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $tempSku = $row->getData('sku');
        is_null($tempSku)
        && $tempSku = $this->modelFactory->getObject('Magento\Product')
            ->setProductId($row->getData('entity_id'))
            ->getSku();

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($tempSku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isRelationParentType()) {
            return $value;
        }

        $productAttributes = (array)$variationManager->getTypeModel()->getProductAttributes();

        $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
        $value .= implode(', ', $productAttributes);
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        if (empty($generalId)) {
            return $this->getGeneralIdColumnValueEmptyGeneralId($row);
        }

        return $this->getGeneralIdColumnValueNotEmptyGeneralId($row);
    }

    public function callbackColumnSettings($id, $row, $column, $isExport)
    {
        $value = '';
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $id)->getChildObject();

        if ($this->listing->getChildObject()->isGeneralIdAttributeMode()) {
            $attrValue = $listingProduct->getListingSource()->getSearchGeneralId();

            if (empty($attrValue)) {
                $attrValue = $this->__('Not set');
            } else if (!$this->getHelper('Component\Amazon')->isASIN($attrValue) &&
                        !$this->getHelper('Data')->isISBN($attrValue)) {
                $attrValue = $this->__('Inappropriate value');
            }

            $value .= '<b>' . $this->__('ASIN/ISBN') . '</b>: ' . $attrValue . '<br/>';
        }

        if ($this->listing->getChildObject()->isWorldwideIdAttributeMode()) {
            $attrValue = $listingProduct->getListingSource()->getSearchWorldwideId();

            if (empty($attrValue)) {
                $attrValue = $this->__('Not Set');
            } else if (!$this->getHelper('Data')->isUPC($attrValue) && !$this->getHelper('Data')->isEAN($attrValue)) {
                $attrValue = $this->__('Inappropriate value');
            }

            $value .= '<b>' . $this->__('UPC/EAN') . '</b>: ' . $attrValue;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $generalId = $row->getData('general_id');
        $searchSettingsStatus = $row->getData('search_settings_status');
        $style = 'display: inline-block; vertical-align: middle; line-height: 30px;';

        if (empty($generalId) && empty($searchSettingsStatus)) {

            $msg = $this->__('None');
            $tip = $this->__('The Search of Product was not performed yet');

            return <<<HTML
<span style="color: gray; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;
        }

        switch ($searchSettingsStatus) {
            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS:
                $searchData = $this->getHelper('Data')->jsonDecode($row->getData('search_settings_data'));

                $msg = $this->__('In Progress');
                $tip = $this->__(
                    'The Search is being performed now by %type% "%value%"',
                    $this->prepareSearchType($searchData['type']), $searchData['value']
                );

                return <<<HTML
<span style="color: orange; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;

            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND:

                $msg = $this->__('Product was not found');
                $tip = $this->__('There are no Products found on Amazon after the Automatic Search
                                                   was performed according to Listing Search Settings.');

                return <<<HTML
<span style="color: red; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;
            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED:

                $searchData = $this->getHelper('Data')->jsonDecode($row->getData('search_settings_data'));

                $lpId = $row->getData('id');

                $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
                if (strlen($productTitle) > 60) {
                    $productTitle = substr($productTitle, 0, 60) . '...';
                }
                $productTitle = $this->__(
                    'Search ASIN/ISBN For &quot;%product_title%&quot;',
                    $productTitle
                );
                $productTitle = $this->getHelper('Data')->escapeJs($productTitle);

                $linkTxt = $this->__('choose one of the Results');

                $linkHtml = <<<HTML
<a href="javascript:void(0)"
    onclick="ListingGridHandlerObj.productSearchHandler.openPopUp(1,'{$productTitle}',{$lpId})">{$linkTxt}</a>
HTML;

                $msg = $this->__('Action Required');
                $tip = $this->__(
                    'Please %link% that were found by %type% "%value%"',
                    $linkHtml, $this->prepareSearchType($searchData['type']), $searchData['value']
                );

                return <<<HTML
<span style="color: orange; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;
        }

        $searchInfo = $this->getHelper('Data')->jsonDecode($row->getData('general_id_search_info'));

        $msg = $this->__('Completed');
        $tip = $this->__(
            'Product was found by %type% "%value%"',
            $this->prepareSearchType($searchInfo['type']), $searchInfo['value']
        );

        return <<<HTML
<span style="color: green; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;
    }

    private function prepareSearchType($searchType)
    {
        if ($searchType == 'string') {
            return 'query';
        }

        return strtoupper($searchType);
    }

    //########################################

    private function getGeneralIdColumnValueEmptyGeneralId($row)
    {
        // ---------------------------------------
        $lpId = $row->getData('id');

        $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }
        $productTitle = $this->__('Search ASIN/ISBN For &quot;%product_title%&quot;', $productTitle);
        $productTitle = $this->getHelper('Data')->escapeJs($productTitle);
        // ---------------------------------------

        // ---------------------------------------

        $searchSettingsStatus = $row->getData('search_settings_status');

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS) {

            $tip = $this->__('Automatic ASIN/ISBN Search in Progress.');
            $iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/search_statuses/processing.gif');

            return <<<HTML
&nbsp;
<a href="javascript: void(0);" title="{$tip}">
    <img src="{$iconSrc}" alt="">
</a>
HTML;
        }
        // ---------------------------------------

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED) {

            $linkTxt = $this->__('Choose ASIN/ISBN');

            return <<<HTML
<a href="javascript:;" title="{$linkTxt}"
   onclick="ListingGridHandlerObj.productSearchHandler.openPopUp(1,'{$productTitle}',{$lpId})">{$linkTxt}</a>
HTML;
        }
        // ---------------------------------------

        $na = $this->__('N/A');
        $tip = $this->__('Search for ASIN/ISBN');

        return <<<HTML
{$na} &nbsp;
<a href="javascript:;" title="{$tip}" class="amazon-listing-view-icon amazon-listing-view-generalId-search"
   onclick="ListingGridHandlerObj.productSearchHandler.showSearchManualPrompt('{$productTitle}',{$lpId});">
</a>
HTML;
    }

    private function getGeneralIdColumnValueNotEmptyGeneralId($row)
    {
        $generalId = $row->getData('general_id');
        $marketplaceId = $this->listing->getMarketplaceId();

        $url = $this->getHelper('Component\Amazon')->getItemUrl(
            $generalId,
            $marketplaceId
        );

        $generalIdSearchInfo = $row->getData('general_id_search_info');

        if (!empty($generalIdSearchInfo)) {
            $generalIdSearchInfo = $this->getHelper('Data')->jsonDecode($generalIdSearchInfo);
        }

        if (!empty($generalIdSearchInfo['is_set_automatic'])) {

            $tip = $this->__('ASIN/ISBN was found automatically');

            $text = <<<HTML
<a href="{$url}" target="_blank" title="{$tip}" style="color:#40AADB;">{$generalId}</a>
HTML;

        } else {

            $text = <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>
HTML;

        }

        // ---------------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------------

        if ($hasInActionLock) {
            return $text;
        }

        $listingProductId = (int)$row->getData('id');

        $tip = $this->__('Unassign ASIN/ISBN');

        $text .= <<<HTML
&nbsp;
<a href="javascript:;"
    class="amazon-listing-view-icon amazon-listing-view-generalId-remove"
    onclick="ListingGridHandlerObj.productSearchHandler.showUnmapFromGeneralIdPrompt({$listingProductId});"
    title="{$tip}">
</a>
HTML;

        return $text;
    }

    //########################################

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

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value == self::SEARCH_SETTINGS_STATUS_NONE) {
            $collection->addFieldToFilter('general_id', array('null' => NULL));
            $collection->addFieldToFilter('search_settings_status', array('null' => NULL));
            return;
        }

        if ($value == self::SEARCH_SETTINGS_STATUS_COMPLETED) {
            $collection->addFieldToFilter(
                array(
                    array('attribute'=>'general_id', 'notnull' => NULL)
                )
            );

            return;
        }

        $collection->addFieldToFilter(
            array(
                array('attribute' => 'search_settings_status', 'eq' => $value)
            )
        );
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

            return parent::_toHtml();
        }

        $showNotCompletedPopup = '';
        if ($this->getRequest()->getParam('not_completed', false)) {
            $showNotCompletedPopup = 'ListingGridHandlerObj.showNotCompletedPopup();';
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Add/SearchAsin/Grid'
    ],function() {

        ListingGridHandlerObj = new AmazonListingProductAddSearchAsinGrid(
            '{$this->getId()}',
            {$this->listing->getId()}
        );

        ListingGridHandlerObj.actionHandler.setOptions(M2ePro);
        ListingGridHandlerObj.actionHandler.setProgressBar('search_asin_progress_bar');
        ListingGridHandlerObj.actionHandler.setGridWrapper('search_asin_content_container');

        ListingGridHandlerObj.productSearchHandler.setOptions(M2ePro);
        ListingGridHandlerObj.afterInitPage();

        {$showNotCompletedPopup}
    });
JS
        );

        if (!$this->listing->getChildObject()->isGeneralIdAttributeMode() &&
            !$this->listing->getChildObject()->isWorldwideIdAttributeMode()) {

            if (!$this->listing->getChildObject()->isSearchByMagentoTitleModeEnabled()) {
                $gridId = $this->getId();

                $this->js->add(
<<<JS
    var mmassActionEl = $("{$gridId}_massaction-select");

    if (mmassActionEl &&  mmassActionEl.select('option[value="assignGeneralId"]').length > 0) {
        var assignGeneralIdOption = mmassActionEl.select('option[value="assignGeneralId"]')[0];
        assignGeneralIdOption.disabled = true;

        mmassActionEl.insert({bottom: assignGeneralIdOption.remove()});
    }
JS
                );
            }

        } else {
            $autoSearchSetting = $this->listing->getSetting('additional_data', 'auto_search_was_performed');

            if (!$autoSearchSetting) {
                $this->listing->setSetting('additional_data', 'auto_search_was_performed', 1);
                $this->listing->save();

                $this->js->add(
<<<JS
require([
    'M2ePro/Amazon/Listing/Product/Add/SearchAsin/Grid'
],function() {
    ListingGridHandlerObj.getGridMassActionObj().selectAll();
    ListingGridHandlerObj.productSearchHandler.searchGeneralIdAuto(ListingGridHandlerObj.getSelectedProductsString());
});
JS
                );
            }
        }

        return '<div id="search_asin_progress_bar"></div>' .
                '<div id="search_asin_content_container">' .
                parent::_toHtml() .
                '</div>';
    }

    //########################################
}