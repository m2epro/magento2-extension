<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\Specific\Validation;

class Popup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls([
            'ebay_category_specific_validation_modal_open' =>
                $this->getUrl('*/ebay_category_specific_validation_modal/open', ['modal' => true]),
            'ebay_category_specific_validation_modal_close' =>
                $this->getUrl('*/ebay_category_specific_validation_modal/close'),
            'ebay_category_specific_validation_listing_product_ids_by_product_type_id' =>
                $this->getUrl('*/ebay_category_specific_validation/getListingProductIdsByCategoryId'),
        ]);

        $this->jsTranslator->addTranslations([
            'modal_title' => __('Category Specific Validation'),
        ]);

        $this->css->addFile('amazon/product_type_validation_grid.css');

        $validateProductTypeFunction = $this->getData('validate_product_type_function') ?? '';
        $js = <<<JS
require([
    'M2ePro/Ebay/Category/Specific/Validation/Popup'
],function() {
    $validateProductTypeFunction
});
JS;

        $this->js->add($js);

        return parent::_prepareLayout();
    }
}
