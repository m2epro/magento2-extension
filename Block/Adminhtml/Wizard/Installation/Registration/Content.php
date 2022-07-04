<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\Installation\Registration;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

abstract class Content extends AbstractForm
{
    /** @var \Magento\Backend\Model\Auth\Session */
    protected $authSession;

    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    /** @var \Ess\M2ePro\Helper\Magento\Admin */
    protected $magentoAdminHelper;

    /** @var \Ess\M2ePro\Helper\Module\License */
    private $helperModuleLicense;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /**
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Helper\Module\License $helperModuleLicense
     * @param \Ess\M2ePro\Model\Registration\Manager $manager
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Ess\M2ePro\Helper\Magento\Admin $magentoAdminHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module\License $helperModuleLicense,
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Ess\M2ePro\Helper\Magento\Admin $magentoAdminHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->manager = $manager;
        $this->authSession = $authSession;
        $this->magentoAdminHelper = $magentoAdminHelper;
        $this->helperModuleLicense = $helperModuleLicense;
        $this->supportHelper = $supportHelper;
        $this->magentoHelper = $magentoHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(
                <<<HTML
M2E Pro requires activation for further work. To activate your installation,
you should obtain a <strong>License Key</strong>. For more details, please read our
<a href="%1%" target="_blank">Privacy Policy</a>.<br/><br/>
Fill out the form below with the necessary data. The information will be used to create your
<strong>Account</strong> on <a href="%2%" target="_blank">M2E Pro Clients Portal</a> and a new
License Key will be generated automatically.<br/><br/>
Having access to your Account on clients.m2epro.com will let you manage your Subscription,
monitor Trial and Paid Period terms, control License Key(s) data, etc.
HTML
                ,
                $this->supportHelper->getWebsiteUrl() . 'privacy',
                $this->supportHelper->getClientsPortalUrl()
            )
        );

        parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $countries = $this->magentoHelper->getCountries();
        unset($countries[0]);
        $this->setData('available_countries', $countries);
        // ---------------------------------------

        // ---------------------------------------
        $userInfo = $this->magentoAdminHelper->getCurrentInfo();
        // ---------------------------------------

        // ---------------------------------------
        $earlierFormData = [];

        if($this->manager->isExistInfo()){
            $earlierFormDataObj = $this->manager->getInfo();

            $earlierFormData['email'] = $earlierFormDataObj->getEmail();
            $earlierFormData['first_name'] = $earlierFormDataObj->getFirstname();
            $earlierFormData['last_name'] = $earlierFormDataObj->getLastname();
            $earlierFormData['phone'] = $earlierFormDataObj->getPhone();
            $earlierFormData['country'] = $earlierFormDataObj->getCountry();
            $earlierFormData['city'] = $earlierFormDataObj->getCity();
            $earlierFormData['postal_code'] = $earlierFormDataObj->getPostalCode();
        }

        $userInfo = array_merge($userInfo, $earlierFormData);

        $this->setData('user_info', $userInfo);
        $this->setData(
            'isLicenseStepFinished',
            !empty($earlierFormData) && $this->helperModuleLicense->getKey()
        );
        // ---------------------------------------

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
                'class' => 'M2ePro-validate-email validate-length maximum-length-80',
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
                'class' => 'validate-length maximum-length-40',
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
                'class' => 'validate-length maximum-length-40',
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        $fieldset->addField(
            'phone',
            'text',
            [
                'name' => 'phone',
                'label' => $this->__('Phone'),
                'value' => $this->getUserInfoValue('phone'),
                'class' => 'validate-length maximum-length-40',
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
                'class' => 'validate-length maximum-length-40',
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
                'class' => 'validate-length maximum-length-40',
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
                'class' => 'validate-length maximum-length-40',
                'required' => true,
                'disabled' => $this->getData('isLicenseStepFinished')
            ]
        );

        if (!$this->getData('isLicenseStepFinished')) {
            $this->css->add(
                <<<CSS
.field-licence_agreement .admin__field {
    padding-top: 8px;
}
CSS
            );

            $fieldset->addField(
                'licence_agreement',
                'checkbox',
                [
                    'name' => 'licence_agreement',
                    'class' => 'admin__control-checkbox',
                    'label' => $this->__('Terms and Privacy'),
                    'checked' => false,
                    'value' => 1,
                    'required' => true,
                    'after_element_html' => $this->__(<<<HTML
&nbsp; I agree to terms and <a href="https://m2epro.com/privacy-policy" target="_blank">privacy policy</a>
HTML
                    )
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
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
