<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\General
 */
class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingCreateStepOne');
        $this->_controller = 'adminhtml_amazon_listing_create';
        $this->_mode = 'general';
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

        $this->addButton('save_and_next', [
            'label'     => $this->__('Next Step'),
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
                '<p>It is necessary to select an Amazon Account (existing or create a new one) as well as choose
                a Marketplace that you are going to sell Magento Products on.</p>
                <p>It is also important to specify a Store View in accordance with which Magento Attribute
                values will be used in the Listing settings.</p><br>
                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XQItAQ')
            )
        ]);

        return
            $breadcrumb->toHtml() .
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }

    //########################################
}
