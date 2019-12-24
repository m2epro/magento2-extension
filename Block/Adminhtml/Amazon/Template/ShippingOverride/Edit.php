<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ShippingOverride;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ShippingOverride\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Edit
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_template_shippingOverride';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getHelper('Data')->getBackUrl('list');
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'AmazonTemplateShippingOverrideObj.backClick(\'' . $url . '\')',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (!$isSaveAndClose
            && $this->getHelper('Data\GlobalData')->getValue('tmp_template')
            && $this->getHelper('Data\GlobalData')->getValue('tmp_template')->getId()
        ) {
            // ---------------------------------------
            $this->addButton('duplicate', [
                'label'   => $this->__('Duplicate'),
                'onclick' => 'AmazonTemplateShippingOverrideObj.duplicateClick'
                    .'(\'amazon-template-shippingOverride\')',
                'class'   => 'action-primary M2ePro_duplicate_button'
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $this->addButton('delete', [
                'label'     => $this->__('Delete'),
                'onclick'   => 'AmazonTemplateShippingOverrideObj.deleteClick()',
                'class'     => 'action-primary delete M2ePro_delete_button'
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
                'onclick' => 'AmazonTemplateShippingOverrideObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateShippingOverrideObj.saveAndEditClick('
                            . '\'\','
                            . 'undefined,'
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE . '\''
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
                'onclick'   => 'AmazonTemplateShippingOverrideObj.saveAndEditClick('
                    . '\'\','
                    . 'undefined,'
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE . '\''
                    . ')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   =>'AmazonTemplateShippingOverrideObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SHIPPING_OVERRIDE . '\''
                            . ')',
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
