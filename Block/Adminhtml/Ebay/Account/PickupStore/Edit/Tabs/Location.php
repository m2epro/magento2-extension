<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\Location
 */
class Location extends AbstractForm
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabsLocation');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $formData = $this->getFormData();

        $form->addField(
            'block_notice_ebay_accounts_pickup_store_location',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                On this Tab, you should provide the <strong>full address of your Store</strong> including all possible
                details. eBay will analyze this information when deciding if this Store fits the In-Store Pickup
                Service conditions for a particular buyer.<br/>
                Based on the general address data you provided (i.e. Country, Region, City, etc), Longitude and
                Latitude values will be <strong>automatically offered</strong> by pressing the <strong>Get Latitude
                and Longitude</strong> button.
                You can also press <strong>Show on Google Map</strong> link to check the generated Longitude and
                Latitude values.')
            ]
        );

        $form->addField(
            'check_location_validation',
            'text',
            [
                'name' => 'check_location_validation',
                'class' => 'M2ePro-check-location hidden-validation'
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_location_general',
            [
                'legend' => $this->__('General'), 'collapsable' => false
            ]
        );

        $form->addField('marketplace_id', 'hidden', ['name' => 'marketplace_id']);

        $tempMarketplaces = $this->ebayFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->addFieldToFilter('is_in_store_pickup', 1)
            ->setOrder('sorder', 'ASC')
            ->setOrder('title', 'ASC');

        $countries = [['label' => '', 'value' => '']];
        foreach ($tempMarketplaces->getItems() as $marketplace) {

            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */

            $tmpCountry = $this->getHelper('Component_Ebay_PickupStore')
                               ->convertMarketplaceToCountry($marketplace->getChildObject()->getData());

            if (!$tmpCountry) {
                continue;
            }

            $tmp = [
                'label' => $tmpCountry['label'],
                'value' => $tmpCountry['value'],
                'attrs' => ['attribute_code' => strtoupper($marketplace['id'])]
            ];

            if ($tmpCountry['value'] == $formData['country']) {
                $tmp['attrs']['selected'] = 'selected';
            }

            $countries[] = $tmp;
        }

        $fieldset->addField(
            'country',
            self::SELECT,
            [
                'name' => 'country',
                'label' => $this->__('Country'),
                'values' => $countries,
                'value' => '',
                'required' => true,
                'class' => 'M2ePro-pickup-store-dropdown',
                'tooltip' => $this->__(
                    'Select the Country where your Store is located. <strong>Please note</strong>,
                     currently 3 countries are available for selection — Australia, United States and United Kingdom.'
                )
            ]
        );

        $fieldset->addField(
            'region_hidden',
            'hidden',
            [
                'name' => 'region_hidden',
                'value' => $formData['region'],
            ]
        );

        $fieldset->addField(
            'region_container',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Region'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => $this->__('City'),
                'value' => $formData['city'],
                'required' => true,
                'class' => 'input-text'
            ]
        );

        $fieldset->addField(
            'address_1',
            'text',
            [
                'name' => 'address_1',
                'label' => $this->__('Address 1'),
                'value' => $formData['address_1'],
                'required' => true,
                'class' => 'input-text M2ePro-validate-max-length-128'
            ]
        );

        $fieldset->addField(
            'address_2',
            'text',
            [
                'name' => 'address_2',
                'label' => $this->__('Address 2'),
                'value' => $formData['address_2'],
                'class' => 'input-text M2ePro-validate-max-length-128'
            ]
        );

        $fieldset->addField(
            'postal_code',
            'text',
            [
                'name' => 'postal_code',
                'label' => $this->__('Postal Code'),
                'value' => $formData['postal_code'],
                'required' => true,
                'class' => 'input-text'
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_location_additional',
            [
                'legend' => $this->__('Additional'), 'collapsable' => true
            ]
        );

        $fieldset->addField(
            'latitude',
            'text',
            [
                'name' => 'latitude',
                'label' => $this->__('Latitude'),
                'value' => $formData['latitude'],
                'required' => true,
                'class' => 'input-text validate-number',
                'tooltip' => $this->__(
                    'Specify the Latitude and Longitude of your Store.
                     Please, be attentive and provide the accurate values as it will determine the Store location.'
                )
            ]
        );

        $fieldset->addField(
            'longitude',
            'text',
            [
                'name' => 'longitude',
                'label' => $this->__('Longitude'),
                'value' => $formData['longitude'],
                'required' => true,
                'class' => 'input-text validate-number',
                'tooltip' => $this->__(
                    'Specify the Latitude and Longitude of your Store.
                     Please, be attentive and provide the accurate values as it will determine the Store location.'
                )
            ]
        );

        $googleMapHref = '#empty';
        if (!empty($formData['latitude']) && !empty($formData['longitude'])) {
            $googleMapHref = 'https://www.google.com/maps/place/'.$formData['latitude'].','.$formData['longitude'];
        }

        $fieldset->addField(
            'get_geocord_custom_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->createBlock('Magento\Button')
                    ->setData([
                        'label' => $this->__('Get Latitude & Longitude'),
                        'class' => 'save primary get_geocord'
                    ])->toHtml() .
                    "<a href=\"{$googleMapHref}\" target=\"_blank\" id=\"google_map\" style=\"padding-left: 10px;\">
                    {$this->__('Show On Google Map')}
                    </a>"
            ]
        );

        $utcOffsets = [['value' => '', 'label' => '']];

        for ($i = -12; $i <= 12; $i++) {
            $sign = $i == 0 ? "+" : ($i > 0 ? '+' : '-');
            $value = $i < 0 ? $i * -1: $i;
            $offsetValue = $sign .($value < 10 ? '0'. $value : $value).':00';
            $utcOffsets[] = ['value' => $offsetValue, 'label' => $offsetValue];
        }

        $fieldset->addField(
            'utc_offset',
            self::SELECT,
            [
                'name' => 'utc_offset',
                'label' => $this->__('UTC Offset'),
                'values' => $utcOffsets,
                'value' => $formData['utc_offset'],
                'required' => true,
                'class' => 'M2ePro-pickup-store-dropdown'
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'marketplace_id' => 0,
            'country' => '',
            'region' => '',
            'city' => '',
            'postal_code' => '',
            'address_1' => '',
            'address_2' => '',
            'latitude' => '',
            'longitude' => '',
            'utc_offset' => ''
        ];

        $model = $this->getHelper('Data\GlobalData')->getValue('temp_data');
        if ($model === null) {
            return $default;
        }

        return array_merge($default, $model->toArray());
    }

    //########################################
}
