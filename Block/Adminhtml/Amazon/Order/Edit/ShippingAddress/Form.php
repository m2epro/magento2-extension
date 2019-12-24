<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\Edit\ShippingAddress;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Order\Edit\ShippingAddress\Form
 */
class Form extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->getHelper('Data\GlobalData')->getValue('order');

        try {
            $regionCode = $order->getShippingAddress()->getRegionCode();
        } catch (\Exception $e) {
            $regionCode = null;
        }

        $state = $order->getShippingAddress()->getState();

        if (empty($regionCode) && !empty($state)) {
            $regionCode = $state;
        }

        $address = $order->getShippingAddress()->getData();

        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ]
        ]);

        $fieldset = $form->addFieldset(
            'order_address_info',
            [
                'legend' => $this->__('Order Address Information')
            ]
        );

        $fieldset->addField(
            'buyer_name',
            'text',
            [
                'name' => 'buyer_name',
                'label' => $this->__('Buyer Name'),
                'value' => $order->getChildObject()->getData('buyer_name'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'buyer_email',
            'text',
            [
                'name' => 'buyer_email',
                'label' => $this->__('Buyer Email'),
                'value' => $order->getChildObject()->getData('buyer_email'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'recipient_name',
            'text',
            [
                'name' => 'recipient_name',
                'label' => $this->__('Recipient Name'),
                'value' => isset($address['recipient_name'])
                    ? $this->getHelper('Data')->escapeHtml($address['recipient_name']) : '',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'street_0',
            'text',
            [
                'name' => 'street[0]',
                'label' => $this->__('Street Address'),
                'value' =>  isset($address['street'][0])
                    ? $this->getHelper('Data')->escapeHtml($address['street'][0]) : '',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'street_1',
            'text',
            [
                'name' => 'street[1]',
                'label' => '',
                'value' =>  isset($address['street'][1])
                    ? $this->getHelper('Data')->escapeHtml($address['street'][1]) : '',
            ]
        );

        $fieldset->addField(
            'street_2',
            'text',
            [
                'name' => 'street[2]',
                'label' => '',
                'value' =>  isset($address['street'][2])
                    ? $this->getHelper('Data')->escapeHtml($address['street'][2]) : '',
            ]
        );

        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => $this->__('City'),
                'value' => $address['city'],
                'required' => true,
            ]
        );

        $fieldset->addField(
            'country_code',
            'select',
            [
                'name' => 'country_code',
                'label' => $this->__('Country'),
                'values' => $this->getHelper('Magento')->getCountries(),
                'value' => $address['country_code'],
                'required' => true,
            ]
        );

        $fieldset->addField(
            'state',
            'text',
            [
                'container_id' => 'state_td',
                'label' => $this->__('Region/State'),
            ]
        );

        $fieldset->addField(
            'county',
            'text',
            [
                'name' => 'county',
                'label' => $this->__('County'),
                'value' => isset($address['county'])
                    ? $this->getHelper('Data')->escapeHtml($address['county']) : '',
            ]
        );

        $fieldset->addField(
            'postal_code',
            'text',
            [
                'name' => 'postal_code',
                'label' => $this->__('Zip/Postal Code'),
                'value' => $address['postal_code'],
            ]
        );

        $fieldset->addField(
            'phone',
            'text',
            [
                'name' => 'phone',
                'label' => $this->__('Telephone'),
                'value' => $address['phone'],
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Order'));
        $this->jsUrl->add(
            $this->getUrl(
                '*/amazon_order/saveShippingAddress',
                ['order_id' => $this->getRequest()->getParam('id')]
            ),
            'formSubmit'
        );

        $this->js->add("M2ePro.formData.region = '" . $this->getHelper('Data')->escapeJs($regionCode) . "';");

        $this->js->add(<<<JS
    require([
        'M2ePro/Order/Edit/ShippingAddress',
    ], function(){
        window.OrderEditShippingAddressObj = new OrderEditShippingAddress('country_code', 'state_td', 'state');
        OrderEditShippingAddressObj.initObservers();
    });
JS
        );

        return parent::_prepareForm();
    }

    //########################################
}
