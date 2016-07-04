<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\Installation\Registration;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

abstract class Content extends AbstractForm
{
    protected $authSession;

    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->authSession = $authSession;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent($this->__(<<<HTML
M2E Pro requires activation for further work. To activate your installation,
you should obtain a <strong>License Key</strong>. For more details, please read our
<a href="%1%" target="_blank">Privacy Policy</a>.<br/><br/>
Fill out the form below with the necessary data. The information will be used to create your
<strong>Account</strong> on <a href="%2%" target="_blank">M2E Pro Clients Portal</a> and a new
License Key will be generated automatically.<br/><br/>
Having access to your Account on clients.m2epro.com will let you manage your Subscription,
monitor Trial and Paid Period terms, control License Key(s) data, etc.
HTML
            , $this->getHelper('Module\Support')->getMainWebsiteUrl() . 'privacy'
            , $this->getHelper('Module\Support')->getClientsPortalBaseUrl()
        )
        );

        parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $countries = $this->getHelper('Magento')->getCountries();
        unset($countries[0]);
        $this->setData('available_countries', $countries);
        // ---------------------------------------

        // ---------------------------------------
        $userInfo = $this->authSession->getUser()->getData();

        $defaultStore = $this->getHelper('Magento\Store')->getDefaultStore();

        $userInfo['city'] = $defaultStore->getConfig(\Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY);
        $userInfo['postal_code'] = $defaultStore->getConfig(\Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE);
        $userInfo['country'] = $defaultStore->getConfig(
            \Magento\Config\Model\Config\Backend\Admin\Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT
        );
        // ---------------------------------------

        // ---------------------------------------
        $earlierFormData = $this->getHelper('Data\GlobalData')->getValue('license_form_data');

        if ($earlierFormData) {
            $earlierFormData = $earlierFormData->getValue();
            $earlierFormData = (array)json_decode($earlierFormData, true);
            $userInfo = array_merge($userInfo, $earlierFormData);
        }

        $this->setData('user_info', $userInfo);
        $this->setData('isLicenseStepFinished', $earlierFormData && $this->getHelper('Module\License')->getKey());
        // ---------------------------------------

        $this->jsTranslator->addTranslations([
            'An error during of license creation occurred.' => $this->__(
                'The eBay token obtaining is currently unavailable. Please try again later.'
            ),
        ]);

        return parent::_beforeToHtml();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ]
        ]);

        $fieldset = $form->addFieldset(
            'block_notice_wizard_installation_step_license',
            [
                'legend' => ''
            ]
        );

        $fieldset->addField(
            'form_email',
            'text',
            [
                'name' => 'email',
                'label' => $this->__('Email'),
                'value' => $this->getUserInfoValue('email'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'first_name',
            'text',
            [
                'name' => 'firstname',
                'label' => $this->__('First Name'),
                'value' => $this->getUserInfoValue('firstname'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'last_name',
            'text',
            [
                'name' => 'lastname',
                'label' => $this->__('Last Name'),
                'value' => $this->getUserInfoValue('lastname'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'country',
            'select',
            [
                'name' => 'country',
                'label' => $this->__('Country'),
                'value' => $this->getUserInfoValue('country'),
                'values' => $this->getData('available_countries'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => $this->__('City'),
                'value' => $this->getUserInfoValue('city'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'postal_code',
            'text',
            [
                'name' => 'postal_code',
                'label' => $this->__('Postal Code'),
                'value' => $this->getUserInfoValue('postal_code'),
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getCountryLabelByCode($code, $type = 'input')
    {
        foreach ($this->getData('available_countries') as $country) {
            if ($country['value'] == $code) {
                return $country['label'];
            }
        }

        if (!empty($code)) {
            return $code;
        }

        if ($type == 'input') {
            return '';
        }

        $notSelectedWord = $this->__('not selected');
        return <<<HTML
<span style="font-style: italic; color: grey;">
    [{$notSelectedWord}]
</span>
HTML;
    }

    protected function getUserInfoValue($name, $type = 'input')
    {
        $info = $this->getData('user_info');

        if (!empty($info[$name])) {
            return $info[$name];
        }

        if ($type == 'input') {
            return '';
        }

        $notSelectedWord = $this->__('not selected');
        return <<<HTML
<span style="font-style: italic; color: grey;">
    [{$notSelectedWord}]
</span>
HTML;
    }
}