<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

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

        $urlComponents = $this->getHelper('Component')->getEnabledComponents();
        $componentForUrl = count($urlComponents) == 1
            ? array_shift($urlComponents) : \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $email = '<a href="mailto:support@m2epro.com">support@m2epro.com</a>';

        $form->addField('block_notice_configuration_license', self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__('
                    M2E Pro Extension requires activation for its work. License Key activates and identifies your
                    M2E Pro Extension. <br/><br/>
                    To obtain a License Key, press <strong>Create New License</strong>
                    button and enter the required data.<br/><br/>

                    The License Key is strictly connected to the particular
                    <strong>Domain</strong> and <strong>IP</strong>. Their validation prevents problems,
                    such as creation of Item duplicates in case of Magento relocation. For example,
                    duplicated Items can be created after you change the server and synchronization
                    continues working on both old and new server.
                    <br/><br/>
                    <b>Note:</b> If you have not received the License Key, please contact us %email%.</br></br>
                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank">here</a>.',
                    $email, $this->getHelper('Module\Support')->getDocumentationUrl(
                        $componentForUrl, 'Global+Settings#GlobalSettings-License'
                    ))
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

        $fieldSet->addField('license_text_key_container',
            'note', $fieldData
        );

        if ($this->licenseData['info']['email'] != '') {
            $fieldSet->addField('associated_email',
                'note',
                [
                    'label' => $this->__('Associated Email'),
                    'text' => $this->licenseData['info']['email'],
                    'tooltip' => $this->__(
                        'This e-mail address associated to your License. <br/>
                        Also you can use this e-mail to enter a <a href="%url%">clients portal</a>',
                        $this->getHelper('Module\Support')->getClientsPortalBaseUrl())
                ]
            );
        }

        if ($this->key != '') {
            $fieldSet->addField('manage_license',
                'note',
                [
                    'label' => '',
                    'text' => '<a href="'.$this->getHelper('Module\Support')->getClientsPortalBaseUrl()
                              .'" target="_blank">'.$this->__('Manage License').'</a>'
                ]
            );
        }

        if ($this->licenseData['domain'] != '' || $this->licenseData['ip'] != '') {
            $fieldSet = $form->addFieldset('magento_block_configuration_license_valid',
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
                    !is_null($this->licenseData['connection']['domain'])) {
                    $text .= '<span> ('.$this->__('Your Domain').': '
                          .$this->getHelper('Data')->escapeHtml($this->licenseData['connection']['domain']).')</span>';
                }

                $fieldSet->addField('domain_field',
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
                    !is_null($this->licenseData['connection']['ip'])) {
                    $text .= '<span> ('.$this->__('Your IP').': '
                        .$this->getHelper('Data')->escapeHtml($this->licenseData['connection']['ip']).')</span>';
                }

                $fieldSet->addField('ip_field',
                    'note',
                    [
                        'label' => $this->__('IP(s)'),
                        'text' => $text,
                        'after_element_html' => $this->getChildHtml('refresh_status')
                    ]
                );
            }
        }

        $fieldSet = $form->addFieldset('magento_block_configuration_license',
            [
                'legend' => $this->__($this->key == '' ? 'General' : 'Additional'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField('license_buttons',
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

        $cacheConfig = $this->modelFactory->getObject('Config\Manager\Cache');

        // Set data for form
        // ---------------------------------------
        $this->key = $this->getHelper('Data')->escapeHtml($licenseHelper->getKey());
        $this->status = $licenseHelper->getStatus();

        $this->licenseData = array(
            'domain' => $this->getHelper('Data')->escapeHtml($licenseHelper->getDomain()),
            'ip' => $this->getHelper('Data')->escapeHtml($licenseHelper->getIp()),
            'info' => array(
                'email' => $this->getHelper('Data')->escapeHtml($licenseHelper->getEmail()),
            ),
            'valid' => array(
                'domain' => $licenseHelper->isValidDomain(),
                'ip' => $licenseHelper->isValidIp()
            ),
            'connection' => array(
                'domain' => $cacheConfig->getGroupValue('/license/connection/', 'domain'),
                'ip' => $cacheConfig->getGroupValue('/license/connection/', 'ip'),
                'directory' => $cacheConfig->getGroupValue('/license/connection/', 'directory')
            )
        );

        // ---------------------------------------
        $data = array(
            'label'   => $this->__('Refresh'),
            'onclick' => 'LicenseObj.refreshStatus();',
            'class'   => 'refresh_status primary'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('refresh_status',$buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $label = $this->key == '' ? 'Use Existing License' : 'Change License';
        $data = array(
            'label'   => $this->__($label),
            'onclick' => 'LicenseObj.changeLicenseKeyPopup();',
            'class'   => 'change_license primary'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('change_license',$buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $data = array(
            'label'   => $this->__('Create New License'),
            'onclick' => 'LicenseObj.newLicenseKeyPopup();',
            'class'   => 'new_license primary'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('new_license',$buttonBlock);
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        try {
            $this->getHelper('Client')->updateBackupConnectionData(true);
        } catch (\Exception $exception) {}

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