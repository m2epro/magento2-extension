<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;

class SelectMode extends AbstractContainer
{
    use WizardTrait;

    public const MODE_SAME = 'same';
    public const MODE_MANUALLY = 'manually';
    public const MODE_CATEGORY = 'category';
    public const MODE_EBAY_SUGGESTED = 'ebay_suggested';

    private RuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        RuntimeStorage $uiWizardRuntimeStorage,
        Widget $context,
        array $data = []
    ) {
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('listingCategoryMode');
        $this->_controller = 'adminhtml_listing_wizard_category';
        $this->_mode = 'modeSame';

        $this->_headerText = __('Set Category');

        $urlSubmit = $this->getUrl(
            '*/ebay_listing_wizard_category/modeCompleteStep',
            ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
        );

        $this->prepareButtons(
            [
                'label' => __('Continue'),
                'class' => 'action-primary forward',
                'onclick' => 'CommonObj.submitForm(\'' . $urlSubmit . '\');',
            ],
            $this->uiWizardRuntimeStorage->getManager(),
        );
    }

    protected function _prepareLayout()
    {
        $this->addChild('form', \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeSame\Form::class);

        return parent::_prepareLayout();
    }
}
