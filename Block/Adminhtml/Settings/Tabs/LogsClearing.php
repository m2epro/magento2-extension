<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

use \Ess\M2ePro\Model\Log\Clearing as LogClearing;

class LogsClearing extends AbstractTab
{
    protected $modes;
    protected $days;

    //########################################

    protected function _prepareForm()
    {
        $this->prepareFormData();

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $urlComponents = $this->getHelper('Component')->getEnabledComponents();
        $componentForUrl = count($urlComponents) == 1
            ? array_shift($urlComponents) : \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $form->addField('settings_tab_logs_clearing', self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'Set preferences for automatic clearing of Log data then click <b>Save Config</b>.<br/><br/>
                    To clear a Log completely, click <b>Clear All</b>. To clear a Log of data more than
                    a certain number of days old, choose the number of days then click <b>Run Now</b>.<br/><br/>
                    More detailed information about ability to work with this Page you can find
                    <a href="%url%" target="_blank">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationUrl(
                        $componentForUrl, 'Global+Settings#GlobalSettings-LogsClearing'
                    )
                )
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_configuration_logs_clearing_listings',
            [
                'legend' => $this->__('M2E Pro Listings Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_LISTINGS]) ? $this->modes[LogClearing::LOG_LISTINGS] : 1;

        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );
        $fieldSet->addField(LogClearing::LOG_LISTINGS . '_log_mode',
            self::SELECT,
            [
                'name' => LogClearing::LOG_LISTINGS . '_log_mode',
                'label' => $this->__('Enabled'),
                'title' => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $mode,
                'style' => 'margin-right: 1.5rem',
                'onchange' => "LogClearingObj.changeModeLog('".LogClearing::LOG_LISTINGS."')",
                'field_extra_attributes' => 'id="'.LogClearing::LOG_LISTINGS . '_log_mode_container"',
                'after_element_html' => $tooltip
                                        . '<span id="'.LogClearing::LOG_LISTINGS.'_log_button_clear_all_container">'
                                        . $this->getChildHtml('clear_all_'.LogClearing::LOG_LISTINGS).'</span>'
            ]
        );

        $fieldSet->addField(LogClearing::LOG_LISTINGS . '_log_days',
            'text',
            [
                'name' => LogClearing::LOG_LISTINGS . '_log_days',
                'label' => $this->__('Keep For (days)'),
                'title' => $this->__('Keep For (days)'),
                'value' => $this->days[LogClearing::LOG_LISTINGS],
                'class' => 'M2ePro-logs-clearing-interval',
                'required' => true,
                'field_extra_attributes' => 'id="'.LogClearing::LOG_LISTINGS . '_log_days_container"',
                'tooltip' => $this->__(
                    'Specify for how long you want to keep Log data before it is automatically cleared.'
                )
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_configuration_logs_clearing_listings_other',
            [
                'legend' => $this->__('3rd Party Listings Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_OTHER_LISTINGS])
            ? $this->modes[LogClearing::LOG_OTHER_LISTINGS] : 1;
        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );

        $fieldSet->addField(LogClearing::LOG_OTHER_LISTINGS . '_log_mode',
            self::SELECT,
            [
                'name' => LogClearing::LOG_OTHER_LISTINGS . '_log_mode',
                'label' => $this->__('Enabled'),
                'title' => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $mode,
                'style' => 'margin-right: 1.5rem',
                'onchange' => "LogClearingObj.changeModeLog('".LogClearing::LOG_OTHER_LISTINGS."')",
                'field_extra_attributes' => 'id="'.LogClearing::LOG_OTHER_LISTINGS . '_log_mode_container"',
                'after_element_html' => $tooltip
                                    . '<span id="'.LogClearing::LOG_OTHER_LISTINGS.'_log_button_clear_all_container">'
                                    . $this->getChildHtml('clear_all_'.LogClearing::LOG_OTHER_LISTINGS).'</span>'
            ]
        );

        $fieldSet->addField(LogClearing::LOG_OTHER_LISTINGS . '_log_days',
            'text',
            [
                'name' => LogClearing::LOG_OTHER_LISTINGS . '_log_days',
                'label' => $this->__('Keep For (days)'),
                'title' => $this->__('Keep For (days)'),
                'value' => $this->days[LogClearing::LOG_OTHER_LISTINGS],
                'class' => 'M2ePro-logs-clearing-interval',
                'required' => true,
                'field_extra_attributes' => 'id="'.LogClearing::LOG_OTHER_LISTINGS . '_log_days_container"',
                'tooltip' => $this->__(
                    'Specify for how long you want to keep Log data before it is automatically cleared.'
                )
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_configuration_logs_clearing_synch',
            [
                'legend' => $this->__('Synchronization Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_SYNCHRONIZATIONS])
            ? $this->modes[LogClearing::LOG_SYNCHRONIZATIONS] : 1;
        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );

        $fieldSet->addField(LogClearing::LOG_SYNCHRONIZATIONS . '_log_mode',
            self::SELECT,
            [
                'name' => LogClearing::LOG_SYNCHRONIZATIONS . '_log_mode',
                'label' => $this->__('Enabled'),
                'title' => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $mode,
                'style' => 'margin-right: 1.5rem',
                'onchange' => "LogClearingObj.changeModeLog('".LogClearing::LOG_SYNCHRONIZATIONS."')",
                'field_extra_attributes' => 'id="'.LogClearing::LOG_SYNCHRONIZATIONS . '_log_mode_container"',
                'after_element_html' => $tooltip
                                    . '<span id="'.LogClearing::LOG_SYNCHRONIZATIONS.'_log_button_clear_all_container">'
                                    . $this->getChildHtml('clear_all_'.LogClearing::LOG_SYNCHRONIZATIONS).'</span>'
            ]
        );

        $fieldSet->addField(LogClearing::LOG_SYNCHRONIZATIONS . '_log_days',
            'text',
            [
                'name' => LogClearing::LOG_SYNCHRONIZATIONS . '_log_days',
                'label' => $this->__('Keep For (days)'),
                'title' => $this->__('Keep For (days)'),
                'value' => $this->days[LogClearing::LOG_SYNCHRONIZATIONS],
                'class' => 'M2ePro-logs-clearing-interval',
                'required' => true,
                'field_extra_attributes' => 'id="'.LogClearing::LOG_SYNCHRONIZATIONS . '_log_days_container"',
                'tooltip' => $this->__(
                    'Specify for how long you want to keep Log data before it is automatically cleared.'
                )
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_logs_configuration_clearing_orders',
            [
                'legend' => $this->__('Orders Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_ORDERS]) ? $this->modes[LogClearing::LOG_ORDERS] : 1;
        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );

        $fieldSet->addField(LogClearing::LOG_ORDERS . '_log_mode',
            self::SELECT,
            [
                'name' => LogClearing::LOG_ORDERS . '_log_mode',
                'label' => $this->__('Enabled'),
                'title' => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $mode,
                'style' => 'margin-right: 1.5rem',
                'onchange' => "LogClearingObj.changeModeLog('".LogClearing::LOG_ORDERS."')",
                'field_extra_attributes' => 'id="'.LogClearing::LOG_ORDERS . '_log_mode_container"',
                'after_element_html' => $tooltip
                                        . '<span id="'.LogClearing::LOG_ORDERS.'_log_button_clear_all_container">'
                                        . $this->getChildHtml('clear_all_'.LogClearing::LOG_ORDERS).'</span>'
            ]
        );

        $fieldSet->addField(LogClearing::LOG_ORDERS . '_log_days',
            'text',
            [
                'name' => LogClearing::LOG_ORDERS . '_log_days',
                'label' => $this->__('Keep For (days)'),
                'title' => $this->__('Keep For (days)'),
                'value' => $this->days[LogClearing::LOG_ORDERS],
                'class' => 'M2ePro-logs-clearing-interval',
                'required' => true,
                'field_extra_attributes' => 'id="'.LogClearing::LOG_ORDERS . '_log_days_container"',
                'tooltip' => $this->__(
                    'Specify for how long you want to keep Log data before it is automatically cleared.'
                ),
                'disabled' => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function prepareFormData()
    {
        $config = $this->getHelper('Module')->getConfig();
        $tasks = array(
            LogClearing::LOG_LISTINGS,
            LogClearing::LOG_OTHER_LISTINGS,
            LogClearing::LOG_SYNCHRONIZATIONS,
            LogClearing::LOG_ORDERS,
            //LogClearing::LOG_EBAY_PICKUP_STORE,
        );

        //TODO NOT SUPPORTED FEATURE
//        $this->isPickupStoreFeatureEnabled = false;
//        if (Mage::helper('M2ePro/Component_Ebay_PickupStore')->isFeatureEnabled()) {
//            $this->isPickupStoreFeatureEnabled = true;
//            $tasks[] = LogClearing::LOG_EBAY_PICKUP_STORE;
//        }

        // ---------------------------------------
        $modes = array();
        $days  = array();

        foreach ($tasks as $task) {
            $modes[$task] = $config->getGroupValue('/logs/clearing/'.$task.'/','mode');
            $days[$task] = $config->getGroupValue('/logs/clearing/'.$task.'/','days');
        }

        $this->modes = $modes;
        $this->days = $days;
        // ---------------------------------------

        foreach ($tasks as $task) {

            if ($task == LogClearing::LOG_ORDERS) {
                continue;
            }

            // ---------------------------------------
            $data = array(
                'label'   => $this->__('Clear All'),
                'onclick' => 'LogClearingObj.clearAllLog(\'' . $task . '\', this)',
                'class'   => 'clear_all_' . $task . ' primary'
            );
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('clear_all_'.$task, $buttonBlock);
            // ---------------------------------------
        }
    }

    //########################################

    protected function _beforeToHtml()
    {

        $this->jsUrl->add(
            $this->getUrl('*/settings_logsClearing/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_LOGS_CLEARING
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Settings\LogsClearing'));
        $this->jsUrl->add($this->getUrl('*/settings_logsClearing/save'), 'formSubmit');

        $this->jsTranslator->add(
            'Please enter a valid value greater than 14 days.',
            $this->__('Please enter a valid value greater than 14 days.')
        );

        $logData = [
            LogClearing::LOG_LISTINGS,
            LogClearing::LOG_OTHER_LISTINGS,
            LogClearing::LOG_SYNCHRONIZATIONS,
            LogClearing::LOG_ORDERS,
        ];

        $this->js->addRequireJs([
            's' => 'M2ePro/Settings/LogClearing'
        ], <<<JS
        window.LogClearingObj = new SettingsLogClearing();

        LogClearingObj.changeModeLog('{$logData[0]}');
        LogClearingObj.changeModeLog('{$logData[1]}');
        LogClearingObj.changeModeLog('{$logData[2]}');
        LogClearingObj.changeModeLog('{$logData[3]}');
JS
);

        return parent::_beforeToHtml();
    }

    //########################################
}