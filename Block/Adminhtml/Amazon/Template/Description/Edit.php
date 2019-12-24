<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit
 */
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

        if ($isSaveAndClose) {
            $saveButtons = [
                'id' => 'save_and_close',
                'label' => $this->__('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonTemplateDescriptionObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateDescriptionObj.saveAndEditClick('
                            . '\'\','
                            . '\'amazonTemplateDescriptionEditTabs\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
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
                'onclick'   => 'AmazonTemplateDescriptionObj.saveAndEditClick('
                    . '\'\','
                    . '\'amazonTemplateDescriptionEditTabs\','
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   =>'AmazonTemplateDescriptionObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                            . ')',
                    ]
                ],
            ];
        }

        // ---------------------------------------
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
