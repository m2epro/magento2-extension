<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher;

class Initialization extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('EbayListingTemplateSwitcherInitialization');
        // ---------------------------------------
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
//        $this->setChild('confirm', $this->getLayout()->createBlock('M2ePro/adminhtml_widget_dialog_confirm'));
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        // ---------------------------------------
        $urls = array();

        // initiate account param
        // ---------------------------------------
        $account = $this->getHelper('Data\GlobalData')->getValue('ebay_account');
        $params['account_id'] = $account->getId();
        // ---------------------------------------

        // initiate marketplace param
        // ---------------------------------------
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');
        $params['marketplace_id'] = $marketplace->getId();
        // ---------------------------------------

        // initiate attribute sets param
        // ---------------------------------------
        if ($this->getMode() == \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::MODE_LISTING_PRODUCT) {
            $attributeSets = $this->getHelper('Data\GlobalData')->getValue('ebay_attribute_sets');
            $params['attribute_sets'] = implode(',', $attributeSets);
        }
        // ---------------------------------------

        // initiate display use default option param
        // ---------------------------------------
        $displayUseDefaultOption = $this->getHelper('Data\GlobalData')->getValue('ebay_display_use_default_option');
        $params['display_use_default_option'] = (int)(bool)$displayUseDefaultOption;
        // ---------------------------------------

        $path = 'ebay_template/getTemplateHtml';
        $urls[$path] = $this->getUrl('*/' . $path, $params);
        //------------------------------

        //------------------------------
        $path = 'ebay_template/isTitleUnique';
        $urls[$path] = $this->getUrl('*/' . $path);

        $path = 'ebay_template/newTemplateHtml';
        $urls[$path] = $this->getUrl('*/' . $path);

        $path = 'ebay_template/edit';
        $urls[$path] = $this->getUrl(
            '*/ebay_template/edit', array('wizard' => (bool)$this->getRequest()->getParam('wizard', false))
        );
        //------------------------------

        $this->jsUrl->addUrls($urls);
        $this->jsUrl->add(
            $this->getUrl(
                '*/template/checkMessages', array('component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK)
            ),
            'templateCheckMessages'
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Manager')
        );

        $this->jsTranslator->addTranslations([
            'Customized' => $this->__('Customized'),
            'Policies' => $this->__('Policies'),
            'Policy with the same Title already exists.' => $this->__('Policy with the same Title already exists.'),
            'Please specify Policy Title' => $this->__('Please specify Policy Title'),
            'Save New Policy' => $this->__('Save New Policy'),
            'Save as New Policy' => $this->__('Save as New Policy'),
        ]);

        $store = $this->getHelper('Data\GlobalData')->getValue('ebay_store');
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        $this->js->add(<<<JS
    define('Switcher/Initialization',[
        'M2ePro/Ebay/Listing/Template/Switcher',
        'M2ePro/TemplateHandler'
    ], function(){
        window.TemplateHandlerObj = new TemplateHandler();

        window.EbayListingTemplateSwitcherObj = new EbayListingTemplateSwitcher();
        EbayListingTemplateSwitcherObj.storeId = {$store->getId()};
        EbayListingTemplateSwitcherObj.marketplaceId = {$marketplace->getId()};
        EbayListingTemplateSwitcherObj.listingProductIds = '{$this->getRequest()->getParam('ids')}';

    });
JS
    );

        return parent::_toHtml();
    }

    //########################################
}