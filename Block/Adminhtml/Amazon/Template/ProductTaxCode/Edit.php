<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductTaxCode;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Edit
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateProductTaxCodeEdit');
        $this->_controller = 'adminhtml_amazon_template_productTaxCode';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
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
            'onclick'   => 'AmazonTemplateProductTaxCodeObj.backClick(\'' . $url . '\')',
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
                'onclick' => 'AmazonTemplateProductTaxCodeObj.duplicateClick'
                    .'(\'amazon-template-productTaxCode\')',
                'class'   => 'add M2ePro_duplicate_button primary'
            ));
            // ---------------------------------------

            // ---------------------------------------
            $this->addButton('delete', array(
                'label'     => $this->__('Delete'),
                'onclick'   => 'AmazonTemplateProductTaxCodeObj.deleteClick()',
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
                'onclick'   => 'AmazonTemplateProductTaxCodeObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateProductTaxCodeObj.saveAndEditClick('
                            . '\'\','
                            . 'undefined,'
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
                            . ')'
                    ]
                ],
            ];
        } else {

            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'onclick'   => 'AmazonTemplateProductTaxCodeObj.saveAndEditClick('
                    . '\'\','
                    . 'undefined,'
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   =>'AmazonTemplateProductTaxCodeObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
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

    //########################################
}