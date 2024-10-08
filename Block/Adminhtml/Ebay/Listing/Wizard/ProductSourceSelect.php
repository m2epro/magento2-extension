<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;
use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;

class ProductSourceSelect extends AbstractContainer
{
    use WizardTrait;

    public const MODE_PRODUCT = 'product';
    public const MODE_CATEGORY = 'category';

    private WizardRuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        Widget $context,
        array $data = []
    ) {
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        parent::__construct($context, $data);
    }

    public function _construct(): void
    {
        parent::_construct();

        $this->_headerText = __('Add Products');

        $this->prepareButtons(
            [
                'label' => __('Continue'),
                'onclick' => sprintf(
                    "CommonObj.submitForm('%s');",
                    $this->getUrl(
                        '*/ebay_listing_wizard_productSource/select',
                        ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
                    ),
                ),
                'class' => 'action-primary forward',
            ],
            $this->uiWizardRuntimeStorage->getManager(),
        );
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'form',
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSource\Form::class
        );

        return parent::_prepareLayout();
    }
}
