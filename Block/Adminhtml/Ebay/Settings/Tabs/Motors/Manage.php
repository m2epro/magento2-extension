<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Motors;

class Manage extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'ebay_settings_motors_form',
                'method' => 'post',
                'action' => $this->getUrl('*/ebay_settings_motors/importMotorsData'),
                'enctype' => 'multipart/form-data'
            ]
        ]);

        $form->addField('motors_type',
            'hidden',
            [
                'id' => 'motors_type',
                'name' => 'motors_type'
            ]
        );

        $fieldset = $form->addFieldset('general', []);

        $clearButton = $this->createBlock('Magento\Button')->addData([
            'label' => $this->__('Clear'),
            'class' => 'action-primary',
            'onclick' => 'EbaySettingsMotorsObj.clearAddedMotorsRecords()',
            'style' => 'margin-left: 15px;'
        ]);

        $fieldset->addField('database',
            self::CUSTOM_CONTAINER,
            [
                'label' => $this->__('Database'),
                'text' => <<<HTML
    <span id="database-custom-count" style="font-weight: bold;"></span>
    {$clearButton->toHtml()}
HTML
            ]
        );

        $importButton = $this->createBlock('Magento\Button')->addData([
            'label' => $this->__('Import'),
            'class' => 'action-primary',
            'onclick' => 'EbaySettingsMotorsObj.importMotorsRecords()',
            'style' => 'margin-left: 15px;'
        ]);

        $fieldset->addField('motors_custom_file',
            'file',
            [
                'label' => $this->__('File for import'),
                'name' => 'source',
                'required' => true,
                'after_element_html' => <<<HTML
    {$importButton->toHtml()}
HTML
            ]
        )->addCustomAttribute('accept', '.csv');

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $this->css->add(<<<CSS
    #database {
        padding-top: 0px;
    }

    #motors_custom_file {
        padding-top: 0px;
        float: left;
    }

    label.mage-error[for="motors_custom_file"] {
        top: 30px;
    }
CSS
        );

        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->setData([
            'content' => $this->__(<<<HTML
    In this Section you can <strong>Add/Update</strong> Custom Compatible Vehicles information using prepared file.
    This file should be in CSV format, where each line describes one Compatible Vehicle.<br/><br/>
    First line of the file should contain names of Columns:
    <ul class="list">
    <li>for ePIDs: epid,product_type,make,model,submodel,year,trim,engine</li>
    <li>for kTypes: ktype,make,model,variant,body_style,type,from_year,to_year,engine</li>
    </ul>
    There are several required fields:
    <ul class="list">
    <li>for ePIDs: epid,product_type,make,model,submodel,year</li>
    <li>for kTypes: ktype</li>
    </ul>
    For ePIDs product_type column you should provide one of these possible Values:
    <ul class="list">
    <li>0 - for Car / Truck type</li>
    <li>1 - for Motorcycle type</li>
    <li>2 - for ATV / Snowmobiles type</li>
    </ul>
    You can always clear Added Compatible Vehicles by pressing <strong>Clear</strong> Button.
HTML
            )
        ]);

        return '<div id="ebay_settings_motors_manage_popup">' . $helpBlock->toHtml() . parent::_toHtml() . '</div>';
    }

    //########################################
}