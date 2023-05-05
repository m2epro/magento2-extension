<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Edit
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->_controller = 'adminhtml_amazon_template_synchronization';
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
        $url = $this->dataHelper->getBackUrl('list');
        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => 'AmazonTemplateSynchronizationObj.backClick(\'' . $url . '\')',
            'class' => 'back',
        ]);
        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (
            !$isSaveAndClose
            && $this->globalDataHelper->getValue('tmp_template')
            && $this->globalDataHelper->getValue('tmp_template')->getId()
        ) {
            // ---------------------------------------
            $this->addButton('duplicate', [
                'label' => __('Duplicate'),
                'onclick' => 'AmazonTemplateSynchronizationObj.duplicateClick'
                    . '(\'amazon-template-synchronization\')',
                'class' => 'add M2ePro_duplicate_button primary',
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $this->addButton('delete', [
                'label' => __('Delete'),
                'onclick' => 'AmazonTemplateSynchronizationObj.deleteClick()',
                'class' => 'delete M2ePro_delete_button primary',
            ]);
            // ---------------------------------------
        }

        // ---------------------------------------

        if ($isSaveAndClose) {
            $this->removeButton('back');

            $saveButtons = [
                'id' => 'save_and_close',
                'label' => __('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonTemplateSynchronizationObj.saveAndCloseClick('
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SYNCHRONIZATION . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Continue Edit'),
                        'onclick' => 'AmazonTemplateSynchronizationObj.saveAndEditClick('
                            . '\'\','
                            . 'undefined,'
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SYNCHRONIZATION . '\''
                            . ')',
                    ],
                ],
            ];
        } else {
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => __('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonTemplateSynchronizationObj.saveAndEditClick('
                    . '\'\','
                    . 'undefined,'
                    . '\'' . $this->getSaveConfirmationText() . '\','
                    . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SYNCHRONIZATION . '\''
                    . ')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Back'),
                        'onclick' => 'AmazonTemplateSynchronizationObj.saveClick('
                            . '\'\','
                            . '\'' . $this->getSaveConfirmationText() . '\','
                            . '\'' . \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Grid::TEMPLATE_SYNCHRONIZATION . '\''
                            . ')',
                        'class' => 'save primary',
                    ],
                ],
            ];
        }

        // ---------------------------------------

        $this->addButton('save_buttons', $saveButtons);
        // ---------------------------------------

        $this->css->addFile('amazon/template.css');
    }
}
