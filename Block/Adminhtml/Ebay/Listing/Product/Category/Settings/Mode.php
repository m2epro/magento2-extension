<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode
 */
class Mode extends AbstractContainer
{
    const MODE_SAME     = 'same';
    const MODE_CATEGORY = 'category';
    const MODE_MANUALLY = 'manually';
    const MODE_PRODUCT  = 'product';

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_ebay_listing_product_category_settings';
        $this->_mode = 'mode';

        $this->setId('ebayListingCategoryMode');

        $this->removeButton('delete');
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('save');

        $this->_headerText = $this->__('Set Category');

        $url = $this->getUrl('*/ebay_listing_product_add/deleteAll', ['_current' => true]);

        if (!$this->getRequest()->getParam('without_back')) {
            $this->addButton('back', [
                'label'     => $this->__('Back'),
                'class'     => 'back',
                'onclick'   => 'setLocation(\''.$url.'\');'
            ]);
        }

        $this->addButton('next', [
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => "$('categories_mode_form').submit();"
        ]);
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'Apply Settings' => $this->__('Apply Settings')
        ]);

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );

        $this->js->addOnReadyJs(<<<JS
require([
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode'
], function(){

    window.EbayListingProductCategorySettingsModeObj = new EbayListingProductCategorySettingsMode(
        '{$this->getData('mode')}'
    );

});
JS
        );

        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
            '',
            ['data' => ['listing' => $listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml() . <<<HTML
<div id="mode_same_remember_pop_up_content" style="display: none">
        {$this->__(
            'If you continue the Settings you will choose next will be applied to the current M2E Pro Listing
            and automatically assigned to all Products added later.<br/><br/>'
        )}
</div>
HTML;
    }

    //########################################
}
