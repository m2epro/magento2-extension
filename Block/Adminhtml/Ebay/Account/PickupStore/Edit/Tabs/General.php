<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\General
 */
class General extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabsGeneral');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $formData = $this->getFormData();
        $id = !empty($formData['id']) ? $formData['id'] : 0;

        $form->addField(
            'block_notice_ebay_accounts_pickup_store_general',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                    In this section you can add the <strong>basic Store setting</strong>
                    such as its Name, Location ID, Phone Number etc.<br/>
                    Please note, the Location ID <strong>should be unique</strong> for each Store.<br/>
                    The accuracy of the provided data will affect work of the In-Store Pickup Service in general.
                    So, please, <strong>be attentive</strong> providing information.
                ')
            ]
        );

        $form->addField(
            'pickup_store_id',
            'hidden',
            [
                'name' => 'id',
                'value' => $id
            ]
        );

        $form->addField(
            'account_id',
            'hidden',
            [
                'name' => 'account_id',
                'value' => $formData['account_id']
            ]
        );

        $fieldset = $form->addFieldset('magento_block_ebay_account_pickup_store_form_data_general', [
            'legend' => $this->__('General'), 'collapsable' => false
        ]);

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => $this->__('Name'),
                'value' => $formData['name'],
                'required' => true,
                'class' => 'input-text M2ePro-validate-max-length-128',
                'tooltip' => $this->__(
                    'Enter the Store Title which will be displayed to you and your Buyers.'
                )
            ]
        );

        $fieldset->addField(
            'location_id',
            'text',
            [
                'name' => 'location_id',
                'label' => $this->__('Location ID'),
                'value' => $formData['location_id'],
                'required' => true,
                'class' => 'input-text M2ePro-pickup-store-location-id M2ePro-pickup-store-location-id-length',
                'disabled' => (bool)$id,
                'tooltip' => $this->__(
                    'Enter the unique location identifier which will be used for the selected Store.'
                )
            ]
        );

        $fieldset->addField(
            'auto_generate',
            'checkbox',
            [
                'name' => 'auto_generate',
                'label' => '',
                'checked' => true,
                'field_extra_attributes' => 'id="auto_generate_field"',
                'after_element_html' => $this->__('Auto-generate Location ID')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_other',
            [
                'legend' => $this->__('Other'), 'collapsable' => true
            ]
        );

        $fieldset->addField(
            'phone',
            'text',
            [
                'name' => 'phone',
                'label' => $this->__('Phone'),
                'value' => $formData['phone'],
                'required' => true,
                'class' => 'input-text validate-phoneLax'
            ]
        );

        $fieldset->addField(
            'url',
            'text',
            [
                'name' => 'url',
                'label' => $this->__('URL'),
                'value' => $formData['url'],
                'class' => 'input-text validate-url'
            ]
        );

        $fieldset->addField(
            'pickup_instruction',
            'textarea',
            [
                'name' => 'pickup_instruction',
                'label' => $this->__('Pickup Instructions'),
                'value' => $formData['pickup_instruction'],
                'tooltip' => $this->__(
                    'Using this Option, you can add the Pickup Instruction which will
                    provide your Buyers with helpful details about the Store.'
                )
            ]
        );

        $this->setForm($form);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Account\PickupStore::class)
        );

        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl('*/ebay_account_pickupStore/save'),
            'deleteAction' => $this->getUrl('*/ebay_account_pickupStore/delete', ['id' => $id]),
            'getRegions' => $this->getUrl('*/ebay_account_pickupStore/getRegions'),
            'validateLocation' => $this->getUrl('*/ebay_account_pickupStore/validateLocation'),
        ]);

        $this->jsTranslator->addTranslations([
            'Max length 32 character.' => $this->__('Max length 32 character.'),
            'Max length 128 character.' => $this->__('Max length 128 character'),
            'Must be greater than "Open Time".' => $this->__('Must be greater than "Open Time".'),
            'Select value.' => $this->__('Select value.'),
            'Please enter a valid date.' => $this->__('Please enter a valid date.'),
            'You need to choose at set at least one time.' => $this->__('You need to choose at set at least one time.'),
            'You should specify time.' => $this->__('You should specify time.'),
            'The specified Title is already used for another In-Store Pickup. In-Store Pickup Title must be unique.' =>
            $this->__(
                'The specified Title is already used for another In-Store Pickup. In-Store Pickup Title must be unique.'
            ),
            'Same Location is already exists.' => $this->__('The same Location already exists.')
        ]);

        $this->js->addRequireJs([
            'p' => 'M2ePro/Ebay/Account/PickupStore'
        ], <<<JS
            M2ePro.formData.id = {$id};

            window.EbayPickupStoreObj = new EbayPickupStore();
            EbayPickupStoreObj.init();
JS
        );

        return parent::_prepareForm();
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'name' => '',
            'location_id' => '',
            'account_id' => (int)$this->getRequest()->getParam('account_id', 0),
            'phone' => '',
            'url' => '',
            'pickup_instruction' => ''
        ];

        $model = $this->getHelper('Data\GlobalData')->getValue('temp_data');
        if ($model === null) {
            return $default;
        }

        return array_merge($default, $model->toArray());
    }

    //########################################
}
