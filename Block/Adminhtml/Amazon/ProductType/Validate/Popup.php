<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate;

class Popup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls([
            'product_type_validation_modal_open' =>
                $this->getUrl('*/amazon_productType_validation_modal/open', ['modal' => true]),
            'product_type_validation_modal_close' =>
                $this->getUrl('*/amazon_productType_validation_modal/close'),
            'product_type_validation_listing_product_ids_by_product_type_id' =>
                $this->getUrl('*/amazon_productType/getListingProductIdsByProductType')
        ]);

        $this->jsTranslator->addTranslations([
            'modal_title' => __('Product Data Validation'),
        ]);

        $this->css->addFile('amazon/product_type_validation_grid.css');

        $validateProductTypeFunction = $this->getData('validate_product_type_function') ?? '';
        $js = <<<JS
require([
    'M2ePro/Amazon/ProductType/Validator/Popup'
],function() {
    $validateProductTypeFunction
});
JS;

        $this->js->add($js);

        return parent::_prepareLayout();
    }
}
