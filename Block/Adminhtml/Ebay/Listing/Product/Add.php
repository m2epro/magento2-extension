<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add
 */
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

        if ((bool)$this->getRequest()->getParam('listing_creation', false)) {
            $url = $this->getUrl('*/*/sourceMode', ['_current' => true]);
        } else {
            $url = $this->getUrl('*/ebay_listing/view', [
                'id' => $this->getRequest()->getParam('id'),
            ]);

            if ($backParam = $this->getRequest()->getParam('back')) {
                $url = $this->getHelper('Data')->getBackUrl();
            }
        }

        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\''.$url.'\')'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('auto_action', [
            'label'     => $this->__('Auto Add/Remove Rules'),
            'class'     => 'action-primary',
            'onclick'   => 'ListingAutoActionObj.loadAutoActionHtml();'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('continue', [
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => 'ListingProductAddObj.continue();'
        ]);
        // ---------------------------------------

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Start Configure' => $this->__('Start Configure')
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add')]
        ]);

        $hideOthersListingsProductsFilterBlock = $this->createBlock(
            'Listing_Product_ShowOthersListingsProductsFilter'
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
           . $this->getAutoactionPopupHtml();
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

    //########################################
}
