<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private ?\Ess\M2ePro\Model\Listing $listing = null;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAddProductType');
        $this->_controller = 'adminhtml_walmart_listing_product_add';
        $this->_mode = 'productType';
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

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $url = $this->getUrl('*/*/removeAddedProducts', [
            'step' => 1,
            '_current' => true,
        ]);
        $this->addButton('back', [
            'label' => __('Back'),
            'class' => 'back',
            'onclick' => 'setLocation(\'' . $url . '\');',
        ]);

        $url = $this->getUrl(
            '*/walmart_listing_product_add/exitToListing',
            ['id' => $this->getRequest()->getParam('id')]
        );
        $confirm =
            '<strong>' . __('Are you sure?') . '</strong><br><br>'
            . __('All unsaved changes will be lost and you will be returned to the Listings grid.');
        $this->addButton(
            'exit_to_listing',
            [
                'label' => __('Cancel'),
                'onclick' => "confirmSetLocation('$confirm', '$url');",
                'class' => 'action-primary',
            ]
        );

        $this->addButton('next', [
            'label' => __('Continue'),
            'class' => 'action-primary forward',
            'onclick' => "categoryTemplateModeFormSubmit()",
        ]);
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => __(
                '<p>On this page, you can assign a relevant Product Type to the Products you are adding to M2E Pro Listing.</p>

                <p><strong>Note</strong>: Please note, assigning a valid Product Type is crucial for a successful product listing on Walmart.</p><br>

                <p>For further details about Product Types, visit this
                 <a href="%url" target="_blank" class="external-link">page</a>.</p>',
                ['url' =>  $this->supportHelper->getDocumentationArticleUrl('walmart-product-types')]
            ),
        ]);

        parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $this->listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml();
    }
}
