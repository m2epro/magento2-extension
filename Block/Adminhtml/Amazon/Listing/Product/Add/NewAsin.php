<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin
 */
class NewAsin extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingAddNewAsin');
        $this->_controller = 'adminhtml_amazon_listing_product_add';
        $this->_mode = 'newAsin';
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

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $url = $this->getUrl('*/*/index', [
            'step' => 3,
            '_current' => true
        ]);
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\''.$url.'\');'
        ]);

        $this->addButton('next', [
            'label'     => $this->__('Continue'),
            'class'     => 'action-primary forward',
            'onclick'   => "descriptionTemplateModeFormSubmit()"
        ]);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                '<p>Description Policy is required for New ASIN/ISBN creation.
                It should be properly configured to allow creation of New Amazon Products.</p><br>

                <p>More detailed information about creation of New Amazon Products and Description Policies
                 you can find in the following article article
                 <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/LQgtAQ')
            ),
        ]);

        parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
            '',
            ['data' => ['listing' => $this->listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
