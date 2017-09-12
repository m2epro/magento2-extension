<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License;

class Create extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $country;
    protected $authSession;
    protected $user;

    //########################################

    public function __construct(
        \Magento\Config\Model\Config\Source\Locale\Country $country,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\User\Model\User $user,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->country = $country;
        $this->authSession = $authSession;
        $this->user = $user;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $defaultStoreId = $this->getHelper('Magento\Store')->getDefaultStoreId();

        // ---------------------------------------
        $userId = $this->authSession->getUser()->getId();
        $userInfo = $this->user->load($userId)->getData();

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_CITY')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY : 'shipping/origin/city';
        $userInfo['city'] = $this->_storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_POSTCODE')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE : 'shipping/origin/postcode';
        $userInfo['postal_code'] = $this->_storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $userInfo['country'] = $this->_storeManager->getStore($defaultStoreId)->getConfig('general/country/default');
        // ---------------------------------------

        $licenseFormData = $userInfo;

        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldSet = $form->addFieldset('create_new_license', ['legend' => '', 'collapsable' => false]);

        $fieldSet->addField('create_new_license_email',
            'text',
            [
                'name' => 'email',
                'label' => $this->__('Email'),
                'title' => $this->__('Email'),
                'class' => 'M2ePro-validate-email',
                'value' => isset($licenseFormData['email']) ? $licenseFormData['email'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_firstname',
            'text',
            [
                'name' => 'firstname',
                'label' => $this->__('First Name'),
                'title' => $this->__('First Name'),
                'value' => isset($licenseFormData['firstname']) ? $licenseFormData['firstname'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_lastname',
            'text',
            [
                'name' => 'lastname',
                'label' => $this->__('Last Name'),
                'title' => $this->__('Last Name'),
                'value' => isset($licenseFormData['lastname']) ? $licenseFormData['lastname'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_phone',
            'text',
            [
                'name' => 'phone',
                'label' => $this->__('Phone'),
                'title' => $this->__('Phone'),
                'value' => '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_country',
            self::SELECT,
            [
                'name' => 'country',
                'label' => $this->__('Country'),
                'title' => $this->__('Country'),
                'values' => $this->country->toOptionArray(),
                'value' => isset($licenseFormData['country']) ? $licenseFormData['country'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_city',
            'text',
            [
                'name' => 'city',
                'label' => $this->__('City'),
                'title' => $this->__('City'),
                'value' => isset($licenseFormData['city']) ? $licenseFormData['city'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField('create_new_license_postal_code',
            'text',
            [
                'name' => 'postal_code',
                'label' => $this->__('Postal Code'),
                'title' => $this->__('Postal Code'),
                'value' => isset($licenseFormData['postal_code']) ? $licenseFormData['postal_code'] : '',
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}