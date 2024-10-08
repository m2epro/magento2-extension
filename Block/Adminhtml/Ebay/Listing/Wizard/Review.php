<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;

class Review extends AbstractContainer
{
    use ReviewTrait;

    private RuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        RuntimeStorage $uiWizardRuntimeStorage,
        Widget $context,
        array $data = []
    ) {
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;

        parent::__construct($context, $data);
    }

    public function _construct(): void
    {
        parent::_construct();

        $this->setId('listingProductReview');
        $this->setTemplate('ebay/listing/wizard/review.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $this->addGoToListingButton();

        if ($this->uiWizardRuntimeStorage->getManager()->isWizardTypeUnmanaged()) {
            $this->addBackToUnmanagedItemsButton();
        } elseif ($this->uiWizardRuntimeStorage->getManager()->isWizardTypeGeneral()) {
            $this->addListActionButton();
        }
    }

    private function addListActionButton(): void
    {
        $buttonBlock = $this->getLayout()
                            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData(
                                [
                                    'label' => __('List Added Products Now'),
                                    'onclick' => 'setLocation(\'' . $this->generateCompleteUrl(true, $this->generateListingViewUrl(true)) . '\');',
                                    'class' => 'primary',
                                ],
                            );

        $this->setChild('save_and_list', $buttonBlock);
    }

    private function addBackToUnmanagedItemsButton(): void
    {
        $url = $this->getUrl('*/ebay_listing_unmanaged/index');

        $buttonBlock = $this->getLayout()
                            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData(
                                [
                                    'label' => __('Back to Unmanaged Items'),
                                    'onclick' => 'setLocation(\'' . $this->generateCompleteUrl(false, $url) . '\');',
                                    'class' => 'primary',
                                ],
                            );

        $this->setChild('save_and_list', $buttonBlock);
    }
}
