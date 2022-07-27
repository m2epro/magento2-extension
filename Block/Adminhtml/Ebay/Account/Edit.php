<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_ebay_account';

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        if ((bool)$this->getRequest()->getParam('close_on_save', false)) {
            if ($this->getRequest()->getParam('id')) {
                $this->addButton('save', [
                    'label'     => $this->__('Save And Close'),
                    'onclick'   => 'EbayAccountObj.saveAndClose()',
                    'class'     => 'primary'
                ]);
            } else {
                $this->addButton('save_and_continue', [
                    'label'     => $this->__('Save And Continue Edit'),
                    'onclick'   => 'EbayAccountObj.saveAndEditClick(\'\',\'ebayAccountEditTabs\')',
                    'class'     => 'primary'
                ]);
            }
            return;
        }

        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'EbayAccountObj.backClick(\'' . $this->getUrl('*/ebay_account/index').'\')',
            'class'     => 'back'
        ]);

        $saveButtonsProps = [];
        if ($this->globalDataHelper->getValue('edit_account') &&
            $this->globalDataHelper->getValue('edit_account')->getId()) {
            $this->addButton('delete', [
                'label'     => $this->__('Delete'),
                'onclick'   => 'EbayAccountObj.deleteClick()',
                'class'     => 'delete M2ePro_delete_button primary'
            ]);

            $saveButtonsProps['save'] = [
                'label'     => $this->__('Save And Back'),
                'onclick'   =>'EbayAccountObj.saveClick()',
                'class'     => 'save primary'
            ];
        }

        // ---------------------------------------
        if (!empty($saveButtonsProps)) {
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick'   => 'EbayAccountObj.saveAndEditClick(\'\', \'ebayAccountEditTabs\')',
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => $saveButtonsProps
            ];

            $this->addButton('save_buttons', $saveButtons);
        } else {
            $this->addButton('save_and_continue', [
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add primary',
                'onclick'   => 'EbayAccountObj.saveAndEditClick(\'\')',
            ]);
        }
        // ---------------------------------------
    }
}
