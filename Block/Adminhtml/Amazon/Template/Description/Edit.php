<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Edit
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEdit');
        $this->_controller = 'adminhtml_amazon_template_description';
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

            $headId = 'amazon-template-description';
            // ---------------------------------------
            $this->buttonList->add('duplicate', [
                'label'   => $this->__('Duplicate'),
                'onclick' => "AmazonTemplateDescriptionObj.duplicateClick('{$headId}')",
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

        $saveButtonOptions = [];

        if ($isSaveAndClose) {
            $saveButtonOptions['save'] = [
                'label' => $this->__('Save And Close'),
                'onclick' => "AmazonTemplateDescriptionObj.saveAndCloseClick()"
            ];
            $this->removeButton('back');
        } else {
            $saveButtonOptions['save'] = [
                'label'     => $this->__('Save And Back'),
                'onclick'   => 'AmazonTemplateDescriptionObj.saveClick('
                    . '\'\','
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                    . ')',
                'class'     => 'save primary'
            ];
        }

        // ---------------------------------------
        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => $this->__('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick' => 'AmazonTemplateDescriptionObj.saveAndEditClick('
                . '\'\','
                . 'undefined,'
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                . ')',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
            'options' => $saveButtonOptions
        ];

        $this->addButton('save_buttons', $saveButtons);
        // ---------------------------------------

        $this->css->addFile('amazon/template.css');
    }

    //########################################

    private function isEditMode()
    {
        $templateModel = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        return $templateModel && $templateModel->getId();
    }

    //########################################
}