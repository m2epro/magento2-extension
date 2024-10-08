<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\ProductType $productType;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Walmart\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->productType = $productType;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        $this->setDataObject($this->productType);

        $this->jsTranslator->addTranslations([
            'Do not show any more' => (string)__('Do not show this message anymore'),
            'Save Product Type Settings' => (string)__('Save Product Type Settings'),
            'Search Product Type' => (string)__('Search Product Type'),
        ]);

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\ProductType::class)
        );

        return parent::_beforeToHtml();
    }

    private function getSaveConfirmationText(): string
    {
        if ($this->productType->isObjectNew()) {
            return '';
        }

        return $this->dataHelper->escapeJs(
            (string)__(
                '<br/>
<b>Note:</b> All changes you have made will be automatically applied to all M2E Pro Listings where this
Product Type is used.
                '
            )
        );
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartProductTypeEdit');
        $this->_controller = 'adminhtml_walmart_productType';
        $this->_mode = 'edit';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (!$isSaveAndClose && $this->isEditMode()) {
            $this->buttonList->add(
                'delete',
                [
                    'label' => $this->__('Delete'),
                    'onclick' => 'WalmartProductTypeObj.deleteClick()',
                    'class' => 'delete M2ePro_delete_button primary',
                ]
            );
        }

        if ($isSaveAndClose) {
            $saveButtons = [
                'id' => 'save_and_close',
                'label' => (string)__('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'WalmartProductTypeObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => (string)__('Save And Continue Edit'),
                        'onclick' => 'WalmartProductTypeObj.saveAndEditClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\''
                            . ')',
                    ],
                ],
            ];
            $this->removeButton('back');
        } else {
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'onclick' => 'WalmartProductTypeObj.saveAndEditClick('
                    . '\'\','
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Back'),
                        'onclick' => 'WalmartProductTypeObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\''
                            . ')',
                    ],
                ],
            ];
        }

        // ---------------------------------------
        $this->addButton('save_buttons', $saveButtons);
        // ---------------------------------------

        $this->css->addFile('walmart/product_type.css');
    }

    private function isEditMode(): bool
    {
        return (bool)$this->productType->getId();
    }
}
