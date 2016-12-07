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

        if ($isSaveAndClose) {
            $this->removeButton('back');

            $saveButtons = [
                'id' => 'save_and_close',
                'label' => $this->__('Save And Close'),
                'class' => 'add',
                'onclick'   => 'AmazonTemplateSellingFormatObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateSellingFormatObj.saveAndEditClick('
                            . '\'\','
                            . 'undefined,'
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT . '\''
                            . ')'
                    ]
                ],
            ];
        } else {

            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'onclick'   => 'AmazonTemplateSellingFormatObj.saveAndEditClick('
                    . '\'\','
                    . 'undefined,'
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   =>'AmazonTemplateSellingFormatObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SELLING_FORMAT . '\''
                            . ')',
                        'class'     => 'save primary'
                    ]
                ],
            ];
        }

        // ---------------------------------------

        $this->addButton('save_buttons', $saveButtons);

        $this->css->addFile('amazon/template.css');
        // ---------------------------------------
    }
}