<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add\Category\Grid as CategoryGrid;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add\Grid as ProductGrid;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSourceSelect;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;
use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage;

class Add extends AbstractContainer
{
    use WizardTrait;

    private string $sourceMode;
    private RuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        string $sourceMode,
        RuntimeStorage $uiWizardRuntimeStorage,
        Widget $context,
        array $data = []
    ) {
        $this->sourceMode = $sourceMode;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        parent::__construct($context, $data);
    }

    public function _construct(): void
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------

        $this->setId('listingProduct');
        $this->_controller = 'adminhtml_listing_wizard_add';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = __('Select Products');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------

        $this->css->addFile('listing/autoAction.css');

        // ---------------------------------------

        $this->addButton('auto_action', [
            'label' => $this->__('Auto Add/Remove Rules'),
            'class' => 'action-primary',
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
        ]);

        $this->prepareButtons(
            [
                'label' => __('Continue'),
                'class' => 'action-primary forward',
                'onclick' => 'ListingProductAddObj.continue();',
            ],
            $this->uiWizardRuntimeStorage->getManager(),
        );

        $this->jsTranslator->addTranslations([
            'Remove Category' => __('Remove Category'),
            'Add New Rule' => __('Add New Rule'),
            'Add/Edit Categories Rule' => __('Add/Edit Categories Rule'),
            'Start Configure' => __('Start Configure'),
        ]);

        $this->addGrid();

        return parent::_prepareLayout();
    }

    private function addGrid(): void
    {
        switch ($this->sourceMode) {
            case ProductSourceSelect::MODE_PRODUCT:
                $gridClass = ProductGrid::class;
                break;

            case ProductSourceSelect::MODE_CATEGORY:
                $gridClass = CategoryGrid::class;
                break;

            default:
                throw new \M2E\Kaufland\Model\Exception\Logic(sprintf('Unknown source mode - %s', $this->sourceMode));
        }

        $this->addChild(
            'grid',
            $gridClass,
        );
    }

    protected function _toHtml()
    {
        return '<div id="add_products_progress_bar"></div>' .
            '<div id="add_products_container">' .
            parent::_toHtml() .
            '</div>'
            . $this->getAutoactionPopupHtml();
    }

    private function getAutoactionPopupHtml()
    {
        return <<<HTML
<div id="autoaction_popup_content" style="display: none">
    <div style="margin-top: 10px;">
        {$this->__(
            '<h3>
 Do you want to set up a Rule by which Products will be automatically Added or
 Deleted from the current M2E Ebay Listing?
</h3>
Click <b>Start Configure</b> to create a Rule or <b>Cancel</b> if you do not want to do it now.
<br/><br/>
<b>Note:</b> You can always return to it by clicking Auto Add/Remove Rules Button on this Page.',
        )}
    </div>
</div>
HTML;
    }
}
