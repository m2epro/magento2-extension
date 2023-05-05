<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Repricer;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonRepricerEdit');
        $this->_controller = 'adminhtml_amazon_repricer';
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

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if ($isSaveAndClose) {
            $this->removeButton('back');

            $saveButtons = [
                'id' => 'save_and_close',
                'label' => __('Save And Close'),
                'class' => 'add',
                'onclick' => 'AmazonRepricerObj.saveAndCloseClick()',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Continue Edit'),
                        'onclick' => 'AmazonRepricerObj.saveAndEditClick()',
                    ],
                ],
            ];
        } else {
            $url = $this->getUrl('*/amazon_repricer_settings/index');

            $this->addButton('back', [
                'label' => __('Back'),
                'onclick' => 'AmazonRepricerObj.backClick(\'' . $url . '\')',
                'class' => 'back',
            ]);

            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => __('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => 'AmazonRepricerObj.saveAndEditClick()',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Back'),
                        'onclick' => 'AmazonRepricerObj.saveClick()',
                        'class' => 'action-primary',
                    ],
                ],
            ];
        }

        $this->addButton('save_buttons', $saveButtons);
    }
}
