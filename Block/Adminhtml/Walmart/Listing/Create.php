<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Create
 */
class Create extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingCreateStepOne');
        $this->_controller = 'adminhtml_walmart_listing';
        $this->_mode = 'create';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__("Creating A New Walmart M2E Pro Listing");
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
        $url = $this->getUrl('*/walmart_listing_create/index', [
            '_current' => true
        ]);
        $this->addButton('save_and_next', [
            'label'     => $this->__('Next Step'),
            'onclick'   => 'CommonObj.saveClick(\'' . $url . '\')',
            'class'     => 'action-primary forward'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'On this page, you can configure the basic Listing settings. Specify the meaningful Listing Title for
                your internal use.<br>
                Select Account under which you want to manage this Listing. Assign the Policy Templates and
                Magento Store View.<br/><br/>
                <p>The detailed information can be found <a href="%url%" target="_blank">here</a></p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/NQBhAQ')
            )
        ]);

        return
            $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
