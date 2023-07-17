<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class NewAsin extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param array $data
     */
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

    /**
     * @return void
     */
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

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $url = $this->getUrl('*/*/index', [
            'id' => $this->getRequest()->getParam('id'),
            'step' => 3,
        ]);
        $this->addButton('back', [
            'label' => __('Back'),
            'class' => 'back',
            'onclick' => 'setLocation(\'' . $url . '\');',
        ]);

        $url = $this->getUrl('*/amazon_listing_product_add/exitToListing', [
            'id' => $this->getRequest()->getParam('id')
        ]);
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
            'onclick' => "productTypeTemplateModeFormSubmit()",
        ]);
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                '<p>Product Type is required for New ASIN/ISBN creation.
                It should be properly configured to allow creation of New Amazon Products.</p><br>

                <p>More detailed information about creation of New Amazon Products and Product Types
                 you can find in the following article article
                 <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->supportHelper->getDocumentationArticleUrl(
                    'help/m2/amazon-integration/m2e-pro-listings/asin-isbn-management'
                )
            ),
        ]);

        parent::_prepareLayout();
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $this->listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml();
    }
}
