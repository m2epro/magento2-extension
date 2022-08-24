<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure;

use Ess\M2ePro\Helper\View\Configuration;
use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

class Data extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $moduleMaintenanceHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $moduleWizardHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper
     * @param \Ess\M2ePro\Helper\Module\Wizard $moduleWizardHelper
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\Wizard $moduleWizardHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->moduleHelper = $moduleHelper;
        $this->moduleMaintenanceHelper = $moduleMaintenanceHelper;
        $this->moduleWizardHelper = $moduleWizardHelper;
    }

    /**
     * @return bool
     */
    protected function canExecute(): bool
    {
        return true;
    }

    /**
     * @param mixed $interceptor
     * @param \Closure $callback
     * @param ...$arguments
     *
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function aroundGet($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('get', $interceptor, $callback, $arguments);
    }

    /**
     * @param mixed $interceptor
     * @param \Closure $callback
     * @param array $arguments
     *
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processGet($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);

        if (
            $this->moduleMaintenanceHelper->isEnabled()
            || !$this->moduleHelper->areImportantTablesExist()
        ) {
            unset($result['sections'][Configuration::MODULE_AND_CHANNELS_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::INTERFACE_AND_MAGENTO_INVENTORY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::LOGS_CLEARING_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::EXTENSION_KEY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::MIGRATION_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        } elseif ($this->moduleHelper->isDisabled()) {
            unset($result['sections'][Configuration::INTERFACE_AND_MAGENTO_INVENTORY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::LOGS_CLEARING_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::EXTENSION_KEY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::MIGRATION_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        }

        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->moduleWizardHelper->getWizard(MigrationFromMagento1::NICK);
        if (!$wizard->isStarted()) {
            unset($result['sections'][Configuration::MIGRATION_SECTION_WIZARD]);
        }

        return $result;
    }
}
