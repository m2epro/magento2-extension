<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    protected $globalDataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
    private $productType;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->productType = $productType;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        $this->setDataObject($this->productType);

        $changeAttributeMappingConfirmMessage = __(
            <<<TEXT
<p>New Magento attributes have been mapped to some of the Specifics.</p>
<p>Do you want to save these new Attribute mappings as the default ones?</p>
<p>Once confirmed, the records in <i>Amazon > Configurations > Settings > Attribute Mapping</i>
will be updated accordingly.</p>
TEXT
        );

        $this->jsTranslator->addTranslations([
            'Do not show any more' => $this->__('Do not show this message anymore'),
            'Save Product Type Settings' => $this->__('Save Product Type Settings'),
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

    protected function getSaveConfirmationText($id = null)
    {
        $saveConfirmation = '';

        if ($id === null && $this->productType !== null) {
            $id = $this->productType->getId();
        }

        if ($id) {
            $saveConfirmation = $this->dataHelper->escapeJs(
                $this->__(
                    '<br/>
<b>Note:</b> All changes you have made will be automatically applied to all M2E Pro Listings where this
Product Type is used.
                '
                )
            );
        }

        return $saveConfirmation;
    }

    public function _construct()
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

        $this->addButton(
            'view_mode_switch_button',
            [
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\ProductTypeSwitchButton::class,
                'label' => $this->__('Only Required Attributes'),
                'value' => $this->productType->getViewMode(),
            ]
        );

        if (!$isSaveAndClose && $this->isEditMode()) {
            $this->buttonList->add(
                'delete',
                [
                    'label' => $this->__('Delete'),
                    'onclick' => 'AmazonTemplateProductTypeObj.deleteClick()',
                    'class' => 'delete M2ePro_delete_button primary',
                ]
            );
        }

        if ($isSaveAndClose) {
            $saveButtons = [
                'id' => 'save_and_close',
                'label' => $this->__('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonTemplateProductTypeObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
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
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'onclick' => 'AmazonTemplateProductTypeObj.saveAndEditClick('
                    . '\'\','
                    . '\'' . $this->getSaveConfirmationText() . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Back'),
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

    private function isEditMode(): bool
    {
        return (bool)$this->productType->getId();
    }
}
