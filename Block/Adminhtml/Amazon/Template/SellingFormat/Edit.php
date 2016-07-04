<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\SellingFormat;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Edit
{
    protected function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_template_sellingFormat';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getHelper('Data')->getBackUrl('list');
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'onclick'   => 'AmazonTemplateSellingFormatObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (!$isSaveAndClose
            && $this->getHelper('Data\GlobalData')->getValue('tmp_template')
            && $this->getHelper('Data\GlobalData')->getValue('tmp_template')->getId()
        ) {
            // ---------------------------------------
            $this->addButton('duplicate', array(
                'label'   => $this->__('Duplicate'),
                'onclick' => 'AmazonTemplateSellingFormatObj.duplicateClick'
                    .'(\'amazon-template-sellingFormat\')',
                'class'   => 'add M2ePro_duplicate_button primary'
            ));
            // ---------------------------------------

            // ---------------------------------------
            $this->addButton('delete', array(
                'label'     => $this->__('Delete'),
                'onclick'   => 'AmazonTemplateSellingFormatObj.deleteClick()',
                'class'     => 'delete M2ePro_delete_button primary'
            ));
            // ---------------------------------------
        }

        // ---------------------------------------

        $saveButtonOptions = [];

        if ($isSaveAndClose) {
            $saveButtonOptions['save'] = [
                'label' => $this->__('Save And Close'),
                'onclick' => "AmazonTemplateSellingFormatObj.saveAndCloseClick()"
            ];
            $this->removeButton('back');
        } else {
            $saveButtonOptions['save'] = [
                'label'     => $this->__('Save And Back'),
                'onclick'   =>'AmazonTemplateSellingFormatObj.saveClick('
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
            'onclick'   => 'AmazonTemplateSellingFormatObj.saveAndEditClick('
                . '\'\','
                . 'undefined,'
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_DESCRIPTION . '\''
                . ')',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
            'options' => $saveButtonOptions,
        ];

        $this->addButton('save_buttons', $saveButtons);

        $this->css->addFile('amazon/template.css');
        // ---------------------------------------
    }
}