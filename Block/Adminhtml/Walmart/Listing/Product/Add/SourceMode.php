<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add;

class SourceMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    public const MODE_PRODUCT = 'product';
    public const MODE_CATEGORY = 'category';
    public const MODE_OTHER = 'other';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingSourceMode');
        $this->_controller = 'adminhtml_walmart_listing_product_add';
        $this->_mode = 'sourceMode';
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

        $url = $this->getUrl('*/*/*', ['_current' => true]);
        $this->addButton('next', [
            'label' => __('Continue'),
            'onclick' => 'CommonObj.submitForm(\'' . $url . '\');',
            'class' => 'action-primary forward',
        ]);
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $listing]]
        );

        return $viewHeaderBlock->toHtml() . parent::_toHtml();
    }
}
