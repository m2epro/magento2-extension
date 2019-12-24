<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate
 */
class CategoryTemplate extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAddCategoryTemplate');
        $this->_controller = 'adminhtml_walmart_listing_product_add';
        $this->_mode = 'categoryTemplate';
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

        $url = $this->getUrl('*/*/removeAddedProducts', [
            'step' => 1,
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
            'onclick'   => "categoryTemplateModeFormSubmit()"
        ]);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                '<p>On this page, you can assign the relevant Category Policy to the Products
                you are currently adding to M2E Pro Listing. </p>

                <p><strong>Note</strong>: Category Policy is required when you create a new offer on Walmart.</p><br>

                <p>The detailed information can be found
                 <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/RQBhAQ')
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
