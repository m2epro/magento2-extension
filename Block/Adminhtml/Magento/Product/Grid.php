<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product;

abstract class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    public $hideMassactionColumn = false;
    protected $hideMassactionDropDown = false;

    protected $showAdvancedFilterProductsOption = true;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->isAjax = json_encode($this->getRequest()->isXmlHttpRequest());
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('magento/product/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    /**
     * @inheritdoc
     */
    public function setCollection($collection)
    {
        if (is_null($collection->getStoreId())) {
            $collection->setStoreId(0);
        }

        // TODO NOT SUPPORTED FEATURES "Advanced filters"
//        /** @var $ruleModel Ess_M2ePro_Model_Magento_Product_Rule */
//        $ruleModel = $this->getHelper('Data\GlobalData')->getValue('rule_model');
//        $ruleModel->setAttributesFilterToCollection($collection);

        parent::setCollection($collection);
    }

    //########################################

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        // Set fake action
        // ---------------------------------------
        if ($this->getMassactionBlock()->getCount() == 0) {
            $this->getMassactionBlock()->addItem('fake', array(
                'label' => '&nbsp;&nbsp;&nbsp;&nbsp;',
                'url'   => '#',
            ));
                // Header of grid with massactions is rendering in other way, than with no massaction
                // so it causes broken layout when the actions are absent
            $this->css->add(<<<CSS
#{$this->getId()} .admin__data-grid-header-row:first-child {
    float: left;
}
#{$this->getId()} .admin__data-grid-header-row:last-child {
    margin-left: 170px;
}
CSS
);
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _prepareMassactionColumn()
    {
        if ($this->hideMassactionColumn) {
            return;
        }
        parent::_prepareMassactionColumn();
    }

    // TODO NOT SUPPORTED FEATURES "Advanced filters"
//    public function getMassactionBlockHtml()
//    {
//        $advancedFilterBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_listing_product_rule');
//        $advancedFilterBlock->setShowHideProductsOption($this->showAdvancedFilterProductsOption);
//        $advancedFilterBlock->setGridJsObjectName($this->getJsObjectName());
//
//        return $advancedFilterBlock->toHtml() . (($this->hideMassactionColumn)
//            ? '' :  parent::getMassactionBlockHtml());
//    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$value;

        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
        $htmlWithoutThumbnail = '<a href="' . $url . '" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()
            ->getGroupValue('/view/','show_products_thumbnails');

        if (!$showProductsThumbnails) {
            return $htmlWithoutThumbnail;
        }

        $storeId = $this->getStoreId();

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $thumbnail = $magentoProduct->getThumbnailImage();
        if (is_null($thumbnail)) {
            return $htmlWithoutThumbnail;
        }

        $thumbnailUrl = $thumbnail->getUrl();

        return <<<HTML
<a href="{$url}" target="_blank">
    {$productId}
    <div style="margin-top: 5px"><img src="{$thumbnailUrl}" /></div>
</a>
HTML;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        return $this->getHelper('Data')->escapeHtml($value);
    }

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $rowVal = $row->getData();

        if (!isset($rowVal['price']) || (float)$rowVal['price'] <= 0) {
            $value = 0;
            $value = '<span style="color: red;">'.$value.'</span>';
        }
        return $value;
    }

    public function callbackColumnQty($value, $row, $column, $isExport)
    {
        if ($value <= 0) {
            $value = 0;
            $value = '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED) {
            $value = '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function getStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    //########################################

    public function getAdvancedFilterButtonHtml()
    {
        // TODO NOT SUPPORTED FEATURES "Advanced filters"

//        if (!$this->getChild('advanced_filter_button')) {
//            $data = array(
//                'label'   => Mage::helper('adminhtml')->__('Show Advanced Filter'),
//                'onclick' => 'ProductGridObj.advancedFilterToggle()',
//                'class'   => 'task',
//                'id'      => 'advanced_filter_button'
//            );
//            $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button');
//            $buttonBlock->setData($data);
//            $this->setChild('advanced_filter_button', $buttonBlock);
//        }
//
//        return $this->getChildHtml('advanced_filter_button');
    }

    // TODO NOT SUPPORTED FEATURES "Advanced filters"
//    public function getMainButtonsHtml()
//    {
//        $html = '';
//
//        if ($this->getFilterVisibility()) {
//            $html .= $this->getResetFilterButtonHtml();
//            if (!$this->isShowRuleBlock()) {
//                $html .= $this->getAdvancedFilterButtonHtml();
//            }
//            $html .= $this->getSearchButtonHtml();
//        }
//
//        return $html;
//    }

    //########################################

    protected function _toHtml()
    {
        // ---------------------------------------

        if ($this->hideMassactionDropDown) {
            $this->css->add(<<<CSS
    #{$this->getHtmlId()}_massaction .admin__grid-massaction-form {
        display: none;
    }
    #{$this->getHtmlId()}_massaction .mass-select-wrap {
        margin-left: -17.4rem;
    }
CSS
            );
        }
        // ---------------------------------------

        // TODO NOT SUPPORTED FEATURES "Advanced filters"
        // ---------------------------------------
//        $isShowRuleBlock = json_encode($this->isShowRuleBlock());

//        $commonJs = <<<HTML
//<script type="text/javascript">
//    var init = function() {
//        if ({$isShowRuleBlock}) {
//            $('listing_product_rules').show();
//            if ($('advanced_filter_button')) {
//                $('advanced_filter_button').simulate('click');
//            }
//        }
//    };
//
//    {$this->isAjax} ? init()
//                    : Event.observe(window, 'load', init);
//</script>
//HTML;
        // ---------------------------------------

        if ($this->getRequest()->isXmlHttpRequest()) {
            return
//                $commonJs .
                parent::_toHtml();
        }

        // ---------------------------------------
        $helper = $this->getHelper('Data');

        $this->jsTranslator->addTranslations([
            'Please select the Products you want to perform the Action on.' => $helper->escapeJs(
                $this->__('Please select the Products you want to perform the Action on.')
            ),
            // TODO NOT SUPPORTED FEATURES "Advanced filters"
//            'Show Advanced Filter' => $helper->escapeJs($this->__('Show Advanced Filter')),
//            'Hide Advanced Filter' => $helper->escapeJs($this->__('Hide Advanced Filter'))
        ]);

        // ---------------------------------------

        $this->js->add(
            <<<JS
                require([
        'M2ePro/Magento/Product/Grid'
    ], function(){

        window.ProductGridObj = new MagentoProductGrid();
        ProductGridObj.setGridId('{$this->getJsObjectName()}');

        // TODO NOT SUPPORTED FEATURES "Advanced filters"
//        var init = function () {
//            {$this->getJsObjectName()}.doFilter = ProductGridObj.setFilter;
//            {$this->getJsObjectName()}.resetFilter = ProductGridObj.resetFilter;
//        };
//
//        {$this->isAjax} ? init() : Event.observe(window, 'load', init);

    });
JS
        );

        return
            parent::_toHtml();
//            $commonJs;
    }

    //########################################

    // TODO NOT SUPPORTED FEATURES "Advanced filters"
//    protected function isShowRuleBlock()
//    {
//        $ruleData = Mage::helper('M2ePro/Data_Session')->getValue(
//            $this->getHelper('Data\GlobalData')->getValue('rule_prefix')
//        );
//
//        $showHideProductsOption = Mage::helper('M2ePro/Data_Session')->getValue(
//            $this->getHelper('Data\GlobalData')->getValue('hide_products_others_listings_prefix')
//        );
//
//        is_null($showHideProductsOption) && $showHideProductsOption = 1;
//        return !empty($ruleData) || ($this->showAdvancedFilterProductsOption && $showHideProductsOption);
//    }

    //########################################
}