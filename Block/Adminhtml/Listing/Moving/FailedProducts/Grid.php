<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Moving\FailedProducts;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingFailedProductsGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $failedProducts = $this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('failed_products'));

        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id');

        $collection->joinStockItem(array(
            'qty'         => 'qty',
            'is_in_stock' => 'is_in_stock'
        ));
        $collection->addFieldToFilter('entity_id',array('in' => $failedProducts));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'       => $this->__('Product ID'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '100px',
            'index'        => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('title', array(
            'header'       => $this->__('Product Title / Product SKU'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '200px',
            'index'        => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));
    }

    //########################################

    public function callbackColumnProductId($productId, $product, $column, $isExport)
    {
        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
        $withoutImageHtml = '<a href="'.$url.'" target="_blank">'.$productId.'</a>&nbsp;';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/view/','show_products_thumbnails'
        );
        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProduct($product);

        $imageUrlResized = $magentoProduct->getThumbnailImage();
        if (is_null($imageUrlResized)) {
            return $withoutImageHtml;
        }

        $imageUrlResizedUrl = $imageUrlResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px">'.
            '<img style="max-width: 100px; max-height: 100px;" src="' .$imageUrlResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px">'.$this->getHelper('Data')->escapeHtml($value);

        $tempSku = $row->getData('sku');
        if (is_null($tempSku)) {
            $tempSku = $this->modelFactory->getObject('Magento\Product')->setProductId(
                $row->getData('entity_id')
            )->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU').':</strong> ';
        $value .= $this->getHelper('Data')->escapeHtml($tempSku).'</div>';

        return $value;
    }

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        return '<div style="margin-left: 3px">'.$this->getHelper('Data')->escapeHtml($value).'</div>';
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

    //########################################

    protected function _toHtml()
    {
        $this->js->add(<<<JS

        $$('#listingFailedProductsGrid div.grid th').each(function(el) {
            el.style.padding = '4px';
        });

        $$('#listingFailedProductsGrid div.grid td').each(function(el) {
            el.style.padding = '4px';
        });
JS
);

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getData('grid_url');
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}