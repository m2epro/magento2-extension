<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License
 */
class License extends AbstractTab
{
    public $key;
    public $status;
    public $licenseData;
    public $licenseFormData;

    //########################################

    protected function _prepareForm()
    {
        $this->prepareLicenseData();

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $form->addField(
            'block_notice_configuration_license',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    '<p>To use M2E Pro Extension, the Clients Portal Account and License Key are required.</p><br>

                    <p>Clients Portal Account is created automatically based on the email address provided during the
                    initial configuration of your M2E Pro instance. After you log into Account, you will be able
                    to manage your Subscription and Billing information.</p><br>

                    <p>License Key is a unique identifier of M2E Pro instance which is generated automatically
                    and strictly associated with the current IP and Domain of your Magento.</p><br>

                    <p>The same License Key cannot be used for different domains, sub-domains or IPs.
                    If your Magento Server changes its location, the new License Key must be obtained and provided
                    to M2E Pro License section. Click <strong>Save</strong> after the changes are made.</p><br>

                    <p><strong>Note:</strong> If you need some assistance to activate your M2E Pro instance,
                    please contact Support Team at <a href="mailto:support@m2epro.com">support@m2epro.com</a>.</p>'
                )
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_configuration_license_data', [
           'legend' => $this->__('General'),
            'collapsable' => false
        ]);

        $fieldData = [
            'label' => $this->__('License Key'),
            'text' => $this->key
        ];

        if ($this->key && $this->licenseData['domain'] && $this->licenseData['ip'] && !$this->status) {
            $fieldData['text'] .= ' <span style="color: red;">('.$this->__('Suspended').')</span>';
        }

        $fieldSet->addField(
            'license_text_key_container',
            'note',
            $fieldData
        );

        if ($this->licenseData['info']['email'] != '') {
            $fieldSet->addField(
                'associated_email',
                'note',
                [
                    'label' => $this->__('Associated Email'),
                    'text' => $this->licenseData['info']['email'],
                    'tooltip' => $this->__(
                        'That is an e-mail address associated to your License.
                        Also, you can use this e-mail to access a
                        <a href="%url%" target="_blank" class="external-link">clients portal</a>',
                        $this->getHelper('Module\Support')->getClientsPortalUrl()
                    )
                ]
            );
        }

        if ($this->key != '') {
            $fieldSet->addField(
                'manage_license',
                'link',
                [
                    'label' => '',
                    'value' => $this->__('Manage License'),
                    'href' => $this->getHelper('Module\Support')->getClientsPortalUrl(),
                    'class' => 'external-link',
                    'target' => '_blank'
                ]
            );
        }

        if ($this->licenseData['domain'] != '' || $this->licenseData['ip'] != '') {
            $fieldSet = $form->addFieldset(
                'magento_block_configuration_license_valid',
                [
                    'legend' => $this->__('Valid Location'),
                    'collapsable' => false
                ]
            );

            if ($this->licenseData['domain'] != '') {
                $text = '<span '.($this->licenseData['valid']['domain'] ? '' : 'style="color: red;"').'>
                            '.$this->licenseData['domain'].'
                        </span>';
                if (!$this->licenseData['valid']['domain'] &&
                    $this->licenseData['connection']['domain'] !== null) {
                    $text .= '<span> ('.$this->__('Your Domain').': '
                          .$this->getHelper('Data')->escapeHtml($this->licenseData['connection']['domain']).')</span>';
                }

                $fieldSet->addField(
                    'domain_field',
                    'note',
                    [
                        'label' => $this->__('Domain'),
                        'text' => $text
                    ]
                );
            }

            if ($this->licenseData['ip'] != '') {
                $text = '<span '.($this->licenseData['valid']['ip'] ? '' : 'style="color: red;"').'>
                            '.$this->licenseData['ip'].'
                        </span>';
                if (!$this->licenseData['valid']['ip'] &&
                    $this->licenseData['connection']['ip'] !== null) {
                    $text .= '<span> ('.$this->__('Your IP').': '
                        .$this->getHelper('Data')->escapeHtml($this->licenseData['connection']['ip']).')</span>';
                }

                $fieldSet->addField(
                    'ip_field',
                    'note',
                    [
                        'label' => $this->__('IP(s)'),
                        'text' => $text,
                        'after_element_html' => $this->getChildHtml('refresh_status')
                    ]
                );
            }
        }

        $fieldSet = $form->addFieldset(
            'magento_block_configuration_license',
            [
                'legend' => $this->__($this->key == '' ? 'General' : 'Additional'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField(
            'license_buttons',
            'note',
            [
                'text' => '<span style="padding-right: 10px;">'.$this->getChildHtml('new_license').'</span>'
                        . '<span>'.$this->getChildHtml('change_license').'</span>'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ---------------------------------------

    protected function prepareLicenseData()
    {
        /** @var \Ess\M2ePro\Helper\Module\License $licenseHelper */
        $licenseHelper = $this->getHelper('Module\License');

        $cacheConfig = $this->modelFactory->getObject('Config_Manager_Cache');

        // Set data for form
        // ---------------------------------------
        $this->key = $this->getHelper('Data')->escapeHtml($licenseHelper->getKey());
        $this->status = $licenseHelper->getStatus();

        $this->licenseData = [
            'domain' => $this->getHelper('Data')->escapeHtml($licenseHelper->getDomain()),
            'ip' => $this->getHelper('Data')->escapeHtml($licenseHelper->getIp()),
            'info' => [
                'email' => $this->getHelper('Data')->escapeHtml($licenseHelper->getEmail()),
            ],
            'valid' => [
                'domain' => $licenseHelper->isValidDomain(),
                'ip' => $licenseHelper->isValidIp()
            ],
            'connection' => [
                'domain' => $cacheConfig->getGroupValue('/license/connection/', 'domain'),
                'ip' => $cacheConfig->getGroupValue('/license/connection/', 'ip'),
                'directory' => $cacheConfig->getGroupValue('/license/connection/', 'directory')
            ]
        ];

        // ---------------------------------------
        $data = [
            'label'   => $this->__('Refresh'),
            'onclick' => 'LicenseObj.refreshStatus();',
            'class'   => 'refresh_status primary'
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('refresh_status', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $label = $this->key == '' ? 'Use Existing License' : 'Change License';
        $data = [
            'label'   => $this->__($label),
            'onclick' => 'LicenseObj.changeLicenseKeyPopup();',
            'class'   => 'change_license primary'
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('change_license', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'label'   => $this->__('Create New License'),
            'onclick' => 'LicenseObj.newLicenseKeyPopup();',
            'class'   => 'new_license primary'
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('new_license', $buttonBlock);
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        try {
            $this->getHelper('Client')->updateBackupConnectionData(true);
        } catch (\Exception $exception) {
        }

        $this->jsTranslator->addTranslations([
            'Use Existing License' => $this->__('Use Existing License'),
            'Create New License' => $this->__('Create New License'),
            'Cancel' => $this->__('Cancel'),
            'Confirm' => $this->__('Confirm'),
            'Internal Server Error' => $this->__('Internal Server Error'),
            'The License Key has been successfully created.' => $this->__(
                'The License Key has been successfully created.'
            ),
        ]);
        $this->jsUrl->add(
            $this->getUrl('*/settings_license/refreshStatus'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_LICENSE
        );
        $this->jsUrl->add($this->getUrl('*/settings_license/refreshStatus'), 'settings_license/refreshStatus');
        $this->jsUrl->add($this->getUrl('*/settings_license/create'), 'settings_license/create');
        $this->jsUrl->add($this->getUrl('*/settings_license/change'), 'settings_license/change');

        $this->js->addRequireJs([
            'l' => 'M2ePro/Settings/License'
        ], <<<JS

            window.LicenseObj = new License();
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
