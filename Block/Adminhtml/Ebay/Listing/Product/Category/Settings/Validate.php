<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings;

class Validate extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->listing = $listing;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayListingSpecificValidation');
        $this->initToolbarButtons();
    }

    protected function _prepareLayout()
    {
        $gridBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category\Specific\Validation\Grid::class, '', [
                'listingProductIds' => $this->listing->getChildObject()->getAddedListingProductsIds(),
            ]);
        $this->setChild('grid', $gridBlock);

        $headerBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class, '', [
                'data' => ['listing' => $this->listing],
            ]);
        $this->setChild('listing_header', $headerBlock);

        return parent::_prepareLayout();
    }

    private function initToolbarButtons(): void
    {
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $backUrl = $this->getUrl('*/*/', ['step' => 2, '_current' => true]);
        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'class' => 'back',
                'onclick' => "setLocation('$backUrl');",
            ]
        );

        $cancelUrl = $this->getUrl(
            '*/ebay_listing_product_add/exitToListing',
            ['id' => $this->listing->getId()]
        );
        $confirmMessage = sprintf(
            '<strong>%s</strong><br><br>%s',
            __('Are you sure?'),
            __('All unsaved changes will be lost and you will be returned to the Listings grid.')
        );
        $this->addButton(
            'exit_to_listing',
            [
                'label' => __('Cancel'),
                'onclick' => "confirmSetLocation('$confirmMessage', '$cancelUrl');",
                'class' => 'action-primary',
            ]
        );

        $continueUrl = $this->getUrl(
            '*/ebay_listing/review',
            ['id' => $this->listing->getId()]
        );
        $this->addButton(
            'next',
            [
                'id' => 'ebay_listing_category_continue_btn',
                'label' => __('Continue'),
                'class' => 'action-primary forward',
                'onclick' => "setLocation('$continueUrl');",
            ]
        );
    }
}
