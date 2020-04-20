<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\InterfaceTab
 */
class InterfaceTab extends AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $form->addField(
            'settings_interface_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>In this section, you can set up M2E Pro interface preferences.
                    Click <strong>Save</strong> after the changes are made.</p>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'configuration_settings_interface',
            [
                'legend' => '', 'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'products_show_thumbnails',
            self::SELECT,
            [
                'name' => 'products_show_thumbnails',
                'label' => $this->__('Products Thumbnail'),
                'values' => [
                    0 => $this->__('Do Not Show'),
                    1 => $this->__('Show')
                ],
                'value' => (bool)(int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/view/',
                    'show_products_thumbnails'
                ),
                'tooltip' => $this->__(
                    'Choose whether you want to see Thumbnail Images for Products on the
                    Add Products and View Listing Pages.'
                )
            ]
        );

        $fieldset->addField(
            'block_notices_show',
            self::SELECT,
            [
                'name' => 'block_notices_show',
                'label' => $this->__('Help Information'),
                'values' => [
                    0 => $this->__('Do Not Show'),
                    1 => $this->__('Show')
                ],
                'value' => (bool)(int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/view/',
                    'show_block_notices'
                ),
                'tooltip' => $this->__(
                    '<p>Choose whether you want the help information to be available at the top of
                    each M2E Pro Page.</p><br>
                    <p><strong>Please note</strong>, it does not disable the help-tips
                    (the icons with the additional information next to the main options).</p>'
                )
            ]
        );

        $data = [
            'id' => 'restore_block_notices',
            'label'   => $this->__('Restore All Helps & Remembered Choices'),
            'class' => 'primary'
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);

        $fieldset->addField(
            'restore_block_notices',
            self::CUSTOM_CONTAINER,
            [
                'text' => $buttonBlock->toHtml(),
                'field_extra_attributes' => 'id="restore_block_notices_tr"'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/settings_interfaceTab/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_INTERFACE
        );
        $this->jsUrl->add(
            $this->getUrl('*/settings_interfaceTab/restoreRememberedChoices'),
            'settings_interface/restoreRememberedChoices'
        );

        $this->jsTranslator->add(
            'Help Blocks have been successfully restored.',
            $this->__('Help Blocks have been successfully restored.')
        );

        $this->js->addRequireJs([
            'jQuery' => 'jquery'
        ], <<<JS

        $('block_notices_show').observe('change', function() {
            if ($('block_notices_show').value == 1) {
                $('restore_block_notices_tr').show();
            } else {
                $('restore_block_notices_tr').hide();
            }
        }).simulate('change');

        $('restore_block_notices').observe('click', function() {
            SettingsObj.restoreAllHelpsAndRememberedChoices();
        });
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
