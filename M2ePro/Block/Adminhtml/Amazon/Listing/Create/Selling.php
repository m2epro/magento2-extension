<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling
 */
class Selling extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingCreateStepTwo');
        $this->_controller = 'adminhtml_amazon_listing_create';
        $this->_mode = 'selling';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__("Creating A New Amazon M2E Pro Listing");
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_create/index', [
            '_current' => true,
            'step' => '1'
        ]);
        $this->addButton('back', [
            'label'     => $this->__('Previous Step'),
            'onclick'   => 'AmazonListingSettingsObj.backClick(\'' . $url . '\')',
            'class'     => 'action-primary back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_create/index', [
            '_current' => true
        ]);
        $this->addButton('save_and_next', [
            'label'     => $this->__('Next Step'),
            'onclick'   => 'AmazonListingSettingsObj.saveClick(\'' . $url . '\')',
            'class'     => 'action-primary forward'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Amazon_Listing_Create_Breadcrumb')
            ->setSelectedStep((int)$this->getRequest()->getParam('step', 1));

        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'On this Page you can specify main <strong>Selling Settings</strong> for Amazon Items you are going
                to sell using this M2E Pro Listing.<br/><br/>

                You can provide settings for SKU formating, appropriate Condition,
                Condition Note, Gift Wrap, Gift Message and also specify
                Additional Settings - Production Time and Restock Date.<br/><br/>

                In addition to, in this Section you can select Selling Policy that contains
                Settings connected with forming
                of Price, Quantity etc. and Synchronization Policy that describes Rules of
                Automatic Synchronization of Magento Product and Amazon Item.<br/><br/>
                More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XwItAQ')
            )
        ]);

        return
            $breadcrumb->toHtml() .
            $helpBlock->toHtml() .
            parent::_toHtml();
    }

    //########################################
}
