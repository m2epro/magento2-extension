<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

use \Ess\M2ePro\Model\Log\Clearing as LogClearing;

class LogsClearing extends AbstractTab
{
    protected $modes;
    protected $days;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->componentEbayPickupStore = $componentEbayPickupStore;
        $this->config = $config;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $this->prepareFormData();

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $form->addField(
            'settings_tab_logs_clearing',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'Set preferences for automatic clearing of Log data, then click <strong>Save</strong>.
                    You may clear the relevant logs manually by clicking <strong>Clear All</strong>.'
                )
            ]
        );

        $fieldSet = $form->addFieldset(
            'magento_block_configuration_logs_clearing_listings',
            [
                'legend' => $this->__('M2E Pro Listings Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_LISTINGS]) ? $this->modes[LogClearing::LOG_LISTINGS] : 1;

        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );
        $logsType = LogClearing::LOG_LISTINGS;

        $fieldSet->addField(
            LogClearing::LOG_LISTINGS . '_log_mode',
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
                'after_element_html' => <<<HTML
                    {$tooltip}
                    <span id="{$logsType}_log_button_clear_all_container">
                        {$this->getChildHtml('clear_all_'.LogClearing::LOG_LISTINGS)}
                    </span>
HTML
            ]
        );

        $fieldSet->addField(
            LogClearing::LOG_LISTINGS . '_log_days',
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

        $fieldSet = $form->addFieldset(
            'magento_block_logs_configuration_clearing_orders',
            [
                'legend' => $this->__('Orders Logs & Events Clearing'),
                'collapsable' => false
            ]
        );

        $mode = isset($this->modes[LogClearing::LOG_ORDERS]) ? $this->modes[LogClearing::LOG_ORDERS] : 1;
        $tooltip = $this->getTooltipHtml(
            $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
        );
        $logsType = LogClearing::LOG_ORDERS;

        $fieldSet->addField(
            LogClearing::LOG_ORDERS . '_log_mode',
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
                'after_element_html' => <<<HTML
                    {$tooltip}
                    <span id="{$logsType}_log_button_clear_all_container">
                        {$this->getChildHtml('clear_all_'.LogClearing::LOG_ORDERS)}
                    </span>
HTML
            ]
        );

        $fieldSet->addField(
            LogClearing::LOG_ORDERS . '_log_days',
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

        $fieldSet = $form->addFieldset(
            'magento_block_configuration_logs_clearing_synch',
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
        $logsType = LogClearing::LOG_SYNCHRONIZATIONS;

        $fieldSet->addField(
            LogClearing::LOG_SYNCHRONIZATIONS . '_log_mode',
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
                'after_element_html' => <<<HTML
                    {$tooltip}
                    <span id="{$logsType}_log_button_clear_all_container">
                        {$this->getChildHtml('clear_all_'.LogClearing::LOG_SYNCHRONIZATIONS)}
                    </span>
HTML
            ]
        );

        $fieldSet->addField(
            LogClearing::LOG_SYNCHRONIZATIONS . '_log_days',
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

        if ($this->componentEbayPickupStore->isFeatureEnabled()) {
            $fieldSet = $form->addFieldset(
                'magento_block_configuration_logs_clearing_instore_pickup',
                [
                    'legend' => $this->__('In-Store Pickup Log Clearing'),
                    'collapsable' => false
                ]
            );

            $mode = isset($this->modes[LogClearing::LOG_EBAY_PICKUP_STORE])
                ? $this->modes[LogClearing::LOG_EBAY_PICKUP_STORE] : 1;
            $tooltip = $this->getTooltipHtml(
                $this->__('Enables automatic clearing of Log data. Can help reduce Database size.')
            );
            $logsType = LogClearing::LOG_EBAY_PICKUP_STORE;

            $fieldSet->addField(
                LogClearing::LOG_EBAY_PICKUP_STORE . '_log_mode',
                self::SELECT,
                [
                    'name' => LogClearing::LOG_EBAY_PICKUP_STORE . '_log_mode',
                    'label' => $this->__('Enabled'),
                    'title' => $this->__('Enabled'),
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes'),
                    ],
                    'value' => $mode,
                    'style' => 'margin-right: 1.5rem',
                    'onchange' => "LogClearingObj.changeModeLog('".LogClearing::LOG_EBAY_PICKUP_STORE."')",
                    'field_extra_attributes' => 'id="'.LogClearing::LOG_EBAY_PICKUP_STORE . '_log_mode_container"',
                    'after_element_html' => <<<HTML
                    {$tooltip}
                    <span id="{$logsType}_log_button_clear_all_container">
                        {$this->getChildHtml('clear_all_'.LogClearing::LOG_EBAY_PICKUP_STORE)}
                    </span>
HTML
                ]
            );

            $fieldSet->addField(
                LogClearing::LOG_EBAY_PICKUP_STORE . '_log_days',
                'text',
                [
                    'name' => LogClearing::LOG_EBAY_PICKUP_STORE . '_log_days',
                    'label' => $this->__('Keep For (days)'),
                    'title' => $this->__('Keep For (days)'),
                    'value' => $this->days[LogClearing::LOG_EBAY_PICKUP_STORE],
                    'class' => 'M2ePro-logs-clearing-interval',
                    'required' => true,
                    'field_extra_attributes' => 'id="'.LogClearing::LOG_EBAY_PICKUP_STORE . '_log_days_container"',
                    'tooltip' => $this->__(
                        'Specify for how long you want to keep Log data before it is automatically cleared.'
                    )
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function prepareFormData()
    {
        $tasks = [
            LogClearing::LOG_LISTINGS,
            LogClearing::LOG_SYNCHRONIZATIONS,
            LogClearing::LOG_ORDERS
        ];

        if ($this->componentEbayPickupStore->isFeatureEnabled()) {
            $tasks[] = LogClearing::LOG_EBAY_PICKUP_STORE;
        }

        // ---------------------------------------
        $modes = [];
        $days  = [];

        foreach ($tasks as $task) {
            $modes[$task] = $this->config->getGroupValue('/logs/clearing/'.$task.'/', 'mode');
            $days[$task] = $this->config->getGroupValue('/logs/clearing/'.$task.'/', 'days');
        }

        $this->modes = $modes;
        $this->days = $days;
        // ---------------------------------------

        foreach ($tasks as $task) {
            if ($task == LogClearing::LOG_ORDERS) {
                continue;
            }

            // ---------------------------------------
            $data = [
                'label'   => $this->__('Clear All'),
                'onclick' => 'LogClearingObj.clearAllLog(\'' . $task . '\', this)',
                'class'   => 'clear_all_' . $task . ' primary',
                'style'   => 'margin-left: 15px'
            ];
            $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                             ->setData($data);
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

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Settings\LogsClearing'));
        $this->jsUrl->add($this->getUrl('*/settings_logsClearing/save'), 'formSubmit');

        $this->jsTranslator->add(
            'logs_clearing_keep_for_days_validation_message',
            $this->__('Please enter a valid value greater than 14 and less than 90 days.')
        );

        $logData = [
            LogClearing::LOG_LISTINGS,
            LogClearing::LOG_SYNCHRONIZATIONS,
            LogClearing::LOG_ORDERS,
            LogClearing::LOG_EBAY_PICKUP_STORE
        ];

        $pickupStoreJs = '';
        if ($this->componentEbayPickupStore->isFeatureEnabled()) {
            $pickupStoreJs = "LogClearingObj.changeModeLog('{$logData[3]}');";
        }

        $this->js->addRequireJs([
            's' => 'M2ePro/Settings/LogClearing'
        ], <<<JS
        window.LogClearingObj = new SettingsLogClearing();

        LogClearingObj.changeModeLog('{$logData[0]}');
        LogClearingObj.changeModeLog('{$logData[1]}');
        LogClearingObj.changeModeLog('{$logData[2]}');
        {$pickupStoreJs}
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    public function getTooltipHtml($content, $directionToRight = false)
    {
        $tooltip = parent::getTooltipHtml($content, $directionToRight);

        return <<<HTML
<div class="fix-magento-tooltip">
    {$tooltip}
</div>
HTML;
    }

    //########################################
}
