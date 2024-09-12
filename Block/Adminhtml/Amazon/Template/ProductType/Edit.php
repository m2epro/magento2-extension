<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType $productType;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->productType = $productType;
        parent::__construct($context, $data);
    }

    public function _construct(): void
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateProductTypeEdit');
        $this->_controller = 'adminhtml_amazon_template_productType';
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
                    'label' => __('Delete'),
                    'onclick' => 'AmazonTemplateProductTypeObj.deleteClick()',
                    'class' => 'delete M2ePro_delete_button primary',
                ]
            );
        }

        if ($isSaveAndClose) {
            $saveButtons = [
                'id' => 'save_and_close',
                'label' => __('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonTemplateProductTypeObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateProductTypeObj.saveAndEditClick('
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
                'label' => __('Save And Continue Edit'),
                'class' => 'add',
                'onclick' => 'AmazonTemplateProductTypeObj.saveAndEditClick('
                    . '\'\','
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Back'),
                        'onclick' => 'AmazonTemplateProductTypeObj.saveClick('
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

        $this->css->addFile('amazon/product_type.css');
    }

    protected function _beforeToHtml()
    {
        $this->setDataObject($this->productType);

        $changeAttributeMappingConfirmMessage = __(
            '<p>New Magento attributes have been mapped to some of the Specifics.</p>
<p>Do you want to save these new Attribute mappings as the default ones?</p>
<p>Once confirmed, the records in <i>Amazon > Configurations > Settings > Attribute Mapping</i>
will be updated accordingly.</p>'
        );

        $this->jsTranslator->addTranslations([
            'Do not show any more' => __('Do not show this message anymore'),
            'Save Product Type Settings' => __('Save Product Type Settings'),
            'Search Product Type' => __('Search Product Type'),
            'Change Attribute Mapping Confirm Message' => $changeAttributeMappingConfirmMessage,
        ]);

        $this->jsUrl->add(
            $this->_urlBuilder->getUrl('*/*/updateAttributeMapping'),
            'update_attribute_mappings'
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\ProductType::class)
        );

        return parent::_beforeToHtml();
    }

    private function getSaveConfirmationText()
    {
        if (!$this->productType->isObjectNew()) {
            return $this->dataHelper->escapeJs(
                __(
                    '<br/>
<b>Note:</b> All changes you have made will be automatically applied to all M2E Pro Listings where this
Product Type is used.
                '
                )
            );
        }

        return '';
    }

    private function isEditMode(): bool
    {
        return !$this->productType->isObjectNew();
    }
}
