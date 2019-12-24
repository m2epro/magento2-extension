<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Edit
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryEdit');
        $this->_controller = 'adminhtml_walmart_template_category';
        $this->_mode = 'edit';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        // ---------------------------------------

        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (!$isSaveAndClose && $this->isEditMode()) {
            $headId = 'walmart-template-category';
            // ---------------------------------------
            $this->buttonList->add('duplicate', [
                'label'   => $this->__('Duplicate'),
                'onclick' => "WalmartTemplateCategoryObj.duplicateClick('{$headId}')",
                'class'   => 'add M2ePro_duplicate_button primary'
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $this->buttonList->add('delete', [
                'label'     => $this->__('Delete'),
                'onclick'   => 'CommonObj.deleteClick()',
                'class'     => 'delete M2ePro_delete_button primary'
            ]);
            // ---------------------------------------
        }

        // ---------------------------------------

        if ($isSaveAndClose) {
            $saveButtons = [
                'id' => 'save_and_close',
                'label' => $this->__('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'WalmartTemplateCategoryObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_CATEGORY . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' => 'WalmartTemplateCategoryObj.saveAndEditClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_CATEGORY . '\''
                            . ')'
                    ]
                ],
            ];
            $this->removeButton('back');
        } else {
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'onclick'   => 'WalmartTemplateCategoryObj.saveAndEditClick('
                    . '\'\','
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_CATEGORY . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   =>'WalmartTemplateCategoryObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_CATEGORY . '\''
                            . ')',
                    ]
                ],
            ];
        }

        // ---------------------------------------
        $this->addButton('save_buttons', $saveButtons);
        // ---------------------------------------

        $this->css->addFile('walmart/template.css');
    }

    //########################################

    private function isEditMode()
    {
        $templateModel = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        return $templateModel && $templateModel->getId();
    }

    //########################################
}
