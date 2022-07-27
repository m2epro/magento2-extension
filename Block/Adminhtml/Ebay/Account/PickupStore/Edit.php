<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEdit');
        $this->_controller = 'adminhtml_ebay_account_pickupStore';
        $this->_mode = 'edit';

        $pickupStoreModel = $this->globalDataHelper->getValue('temp_data');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $accountId = $this->getRequest()->getParam('account_id', false);
        if ($pickupStoreModel->getAccountId()) {
            $accountId = $pickupStoreModel->getAccountId();
        }
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'EbayPickupStoreObj.backClick(\''
                .$this->getUrl('*/*/index', ['account_id' => $accountId]).'\')',
            'class'     => 'back'
        ]);

        if ($pickupStoreModel && $pickupStoreModel->getId()) {
            $duplicateHeaderText = $this->__('Add Store');

            $this->addButton('duplicate', [
                'label'     => $this->__('Duplicate'),
                'onclick'   => 'EbayPickupStoreObj.duplicateClick(
                    \'ebay-account-pickupStore\', \''.$duplicateHeaderText.'\'
                    )',
                'class'     => 'add M2ePro_duplicate_button'
            ]);

            $this->addButton('delete', [
                'label'     => $this->__('Delete'),
                'onclick'   => 'EbayPickupStoreObj.deleteClick()',
                'class'     => 'delete M2ePro_delete_button'
            ]);
        }

        $url = $this->getUrl(
            '*/ebay_account_pickupStore/save',
            ['back' => $this->dataHelper->makeBackUrlParam('edit', [])]
        );

        $this->addButton('save_buttons', [
            'id' => 'save_and_continue',
            'label' => $this->__('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick'   => 'EbayPickupStoreObj.saveAndEditClick(\''.$url.'\')',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
            'options' => [
                'save' => [
                    'label'     => $this->__('Save And Back'),
                    'onclick'   =>'EbayPickupStoreObj.saveClick()',
                    'class'     => 'save primary'
                ]
            ]
        ]);
        // ---------------------------------------
    }

    //########################################
}
