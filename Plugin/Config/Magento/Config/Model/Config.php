<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model;

use Ess\M2ePro\Helper\View\Configuration;

class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $moduleMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfigurationHelper;
    /** @var \Ess\M2ePro\Model\Log\Clearing */
    private $logClearing;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper
     * @param \Ess\M2ePro\Helper\Module\Configuration $moduleConfigurationHelper
     * @param \Ess\M2ePro\Model\Log\Clearing $logClearing
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfigurationHelper,
        \Ess\M2ePro\Model\Log\Clearing $logClearing,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->request = $request;
        $this->moduleHelper = $moduleHelper;
        $this->moduleMaintenanceHelper = $moduleMaintenanceHelper;
        $this->moduleConfigurationHelper = $moduleConfigurationHelper;
        $this->logClearing = $logClearing;
    }

    /**
     * @return bool
     */
    protected function canExecute(): bool
    {
        if ($this->moduleMaintenanceHelper->isEnabled()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Magento\Config\Model\Config $interceptor
     * @param \Closure $callback
     * @param ...$arguments
     *
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function aroundSave(\Magento\Config\Model\Config $interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('save', $interceptor, $callback, $arguments);
    }

    /**
     * @param \Magento\Config\Model\Config $interceptor
     * @param \Closure $callback
     * @param array $arguments
     *
     * @return \Magento\Config\Model\Config|mixed
     */
    protected function processSave(\Magento\Config\Model\Config $interceptor, \Closure $callback, array $arguments)
    {
        $saveData = $this->request->getParams();

        $availableSections = [
            Configuration::MODULE_AND_CHANNELS_SECTION_COMPONENT,
            Configuration::INTERFACE_AND_MAGENTO_INVENTORY_SECTION_COMPONENT,
            Configuration::LOGS_CLEARING_SECTION_COMPONENT,
            Configuration::EXTENSION_KEY_SECTION_COMPONENT,
            Configuration::MIGRATION_SECTION_COMPONENT
        ];

        if (
            !isset($saveData['section'])
            || !in_array($saveData['section'], $availableSections)
        ) {
            return $callback(...$arguments);
        } else {
            switch ($saveData['section']) {
                case Configuration::MODULE_AND_CHANNELS_SECTION_COMPONENT:
                    $this->processModuleAndChannelsSection($saveData['groups']);
                    break;
                case Configuration::INTERFACE_AND_MAGENTO_INVENTORY_SECTION_COMPONENT:
                    $this->processInterfaceAndMagentoInventorySection($saveData['groups']);
                    break;
                case Configuration::LOGS_CLEARING_SECTION_COMPONENT:
                    $this->processLogsClearingSection($saveData['groups']);
                    break;
            }
        }

        return $interceptor;
    }

    /**
     * @param array $groups
     *
     * @return void
     */
    private function processModuleAndChannelsSection(array $groups): void
    {
        if (isset($groups['module_mode']['fields']['module_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/',
                'is_disabled',
                (int)!$groups['module_mode']['fields']['module_mode_field']['value']
            );
        }

        if (isset($groups['module_mode']['fields']['cron_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/cron/',
                'mode',
                (int)$groups['module_mode']['fields']['cron_mode_field']['value']
            );
        }

        //----------------------------------------

        if (isset($groups['channels']['fields']['ebay_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/component/ebay/',
                'mode',
                (int)$groups['channels']['fields']['ebay_mode_field']['value']
            );
        }

        if (isset($groups['channels']['fields']['amazon_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/component/amazon/',
                'mode',
                (int)$groups['channels']['fields']['amazon_mode_field']['value']
            );
        }

        if (isset($groups['channels']['fields']['walmart_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/component/walmart/',
                'mode',
                (int)$groups['channels']['fields']['walmart_mode_field']['value']
            );
        }
    }

    /**
     * @param array $groups
     *
     * @return void
     */
    private function processInterfaceAndMagentoInventorySection(array $groups): void
    {
        $fields = array_merge(
            $groups['interface']['fields'],
            $groups['quantity_and_price']['fields'],
            $groups['variational_product_settings']['fields'],
            $groups['direct_database_changes']['fields']
        );

        foreach ($fields as $field => $value) {
            $fields[$field] = (int)$value['value'];
        }

        // allowed field names is checking in setConfigValues() method
        $this->moduleConfigurationHelper->setConfigValues($fields);
    }

    /**
     * @param array $groups
     *
     * @return void
     */
    private function processLogsClearingSection(array $groups): void
    {
        $this->logClearing->saveSettings(
            \Ess\M2ePro\Model\Log\Clearing::LOG_LISTINGS,
            $groups['listings_logs_and_events_clearing']['fields']['listings_log_mode_field']['value'],
            $groups['listings_logs_and_events_clearing']['fields']['listings_log_days_field']['value']
        );
        $this->logClearing->saveSettings(
            \Ess\M2ePro\Model\Log\Clearing::LOG_ORDERS,
            $groups['orders_logs_and_events_clearing']['fields']['orders_log_mode_field']['value'],
            90
        );
        $this->logClearing->saveSettings(
            \Ess\M2ePro\Model\Log\Clearing::LOG_SYNCHRONIZATIONS,
            $groups['sync_logs_and_events_clearing']['fields']['sync_log_mode_field']['value'],
            $groups['sync_logs_and_events_clearing']['fields']['sync_log_days_field']['value']
        );
    }
}
