<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product;

class Add extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingProduct');
        $this->_controller = 'adminhtml_ebay_listing_product_add_';
        $this->_controller .= $this->getRequest()->getParam('source');
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__('Select Products');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------

        $this->css->addFile('listing/autoAction.css');

        // ---------------------------------------

        if ((bool)$this->getRequest()->getParam('listing_creation',false)) {
            $url = $this->getUrl('*/*/sourceMode', array('_current' => true));
        } else {
            $url = $this->getUrl('*/ebay_listing/view',array(
                'id' => $this->getRequest()->getParam('id'),
            ));

            if ($backParam = $this->getRequest()->getParam('back')) {
                $url = $this->getHelper('Data')->getBackUrl();
            }
        }

        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\''.$url.'\')'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('auto_action', array(
            'label'     => $this->__('Auto Add/Remove Rules'),
            'class'     => 'action-primary',
            'onclick'   => 'ListingAutoActionObj.loadAutoActionHtml();'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('continue', array(
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => 'ListingProductAddObj.continue();'
        ));
        // ---------------------------------------

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Start Configure' => $this->__('Start Configure')
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing')
        );

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add')]
        ]);

        $hideOthersListingsProductsFilterBlock = $this->createBlock(
            'Listing\Product\ShowOthersListingsProductsFilter'
        )->setData([
            'component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'controller' => 'ebay_listing_product_add'
        ]);

        return $viewHeaderBlock->toHtml()
               . '<div class="filter_block">'
               . $hideOthersListingsProductsFilterBlock->toHtml()
               . '</div>'
               . parent::getGridHtml();
    }

    protected function _toHtml()
    {
        return '<div id="add_products_progress_bar"></div>' .
            '<div id="add_products_container">' .
            parent::_toHtml() .
            '</div>'
           . $this->getAutoactionPopupHtml()
//            $this->getSettingsPopupHtml()
            ;
        }

    //########################################

    private function getAutoactionPopupHtml()
    {
        return <<<HTML
<div id="autoaction_popup_content" style="display: none">
    <div style="margin-top: 10px;">
        {$this->__(
            '<h3>
 Do you want to set up a Rule by which Products will be automatically Added or Deleted from the current M2E Pro Listing?
</h3>
Click <b>Start Configure</b> to create a Rule or <b>Cancel</b> if you do not want to do it now.
<br/><br/>
<b>Note:</b> You can always return to it by clicking Auto Add/Remove Rules Button on this Page.'
        )}
    </div>
</div>
HTML;
    }
//
//    //########################################
//
//    private function getSettingsPopupHtml()
//    {
//        $helper = Mage::helper('M2ePro');
//
//        // ---------------------------------------
//        $onclick = <<<JS
//ListingProductAddObj.settingsPopupYesClick();
//JS;
//        $data = array(
//            'label'   => $this->__('Yes'),
//            'onclick' => $onclick
//        );
//        $yesButton = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
//        // ---------------------------------------
//
//        // ---------------------------------------
//        $onclick = <<<JS
//ListingProductAddObj.settingsPopupNoClick();
//JS;
//        $data = array(
//            'label'   => $this->__('No'),
//            'onclick' => $onclick
//        );
//        $noButton = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
//        // ---------------------------------------
//
//        // M2ePro_TRANSLATIONS
//        // Choose <b>Yes</b> if you want to override the Default Settings for this M2E Pro Listing and to choose Different Settings for certain Products.
//        return <<<HTML
//<div id="settings_popup_content" style="display: none">
//    <div style="margin: 10px; height: 150px">
//        <h3>{$this->__('Do you want to customize the M2E Pro Listing Settings for some Products?')}</h3>
//        <br/>
//        <p>{$this->__('Choose <b>Yes</b> if you want to override the Default Settings for this M2E Pro Listing '.
//            'and to choose Different Settings for certain Products.')}</p>
//    </div>
//
//    <div class="clear"></div>
//    <div class="left">
//        <div style="margin-left: 20px">
//            <input id="remember_checkbox" type="checkbox">
//            &nbsp;&nbsp;
//            <label for="remember_checkbox">{$this->__('Remember my choice')}</label>
//        </div>
//    </div>
//    <div class="right">
//        {$yesButton->toHtml()}
//        <div style="display: inline-block;"></div>
//        {$noButton->toHtml()}
//    </div>
//    <div class="clear"></div>
//</div>
//HTML;
//    }

    //########################################
}