<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;

class ValidationStep extends AbstractContainer
{
    private ListingRuntimeStorage $uiListingRuntimeStorage;

    private WizardRuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        ListingRuntimeStorage $uiListingRuntimeStorage,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        Widget $context,
        array $data = []
    ) {
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;

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
            ->createBlock(ValidationStep\Grid::class, '', [
                'listingProductIds' => $this->uiWizardRuntimeStorage->getManager()->getProductsIds(),
            ]);
        $this->setChild('grid', $gridBlock);

        $headerBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class, '', [
                'data' => ['listing' => $this->uiListingRuntimeStorage->getListing()],
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

        $url = $this->getUrl(
            '*/ebay_listing_wizard/back',
            ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()]
        );
        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => sprintf("setLocation('%s')", $url),
            'class' => 'back',
        ]);

        $url = $this->getUrl(
            '*/ebay_listing_wizard/cancel',
            ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()]
        );
        $confirm =
            '<strong>' . __('Are you sure?') . '</strong><br><br>'
            . __('All unsaved changes will be lost and you will be returned to the Listings grid.');
        $this->addButton(
            'exit_to_listing',
            [
                'label' => __('Cancel'),
                'onclick' => sprintf("confirmSetLocation('%s', '%s');", $confirm, $url),
                'class' => 'action-primary',
            ],
        );

        $continueUrl = $this->getUrl(
            '*/ebay_listing_wizard_validation/completeStep',
            ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()]
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
