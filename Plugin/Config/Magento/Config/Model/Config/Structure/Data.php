<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure;

use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

class Data extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    private $resourceConnection;

    private $migrationFromMagento1Status = NULL;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function canExecute()
    {
        return true;
    }

    public function aroundGet($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('get', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processGet($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);

        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() ||
            (
                $this->helperFactory->getObject('Module\Maintenance\Debug')->isEnabled() &&
                !$this->helperFactory->getObject('Module\Maintenance\Debug')->isOwner()
            )
        )
        {
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::BUY_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::ADVANCED_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);

        } elseif ($this->helperFactory->getObject('Module')->isDisabled()) {

            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::BUY_SECTION_COMPONENT]);
            unset(
                $result['sections'][\Ess\M2ePro\Helper\View\Configuration::ADVANCED_SECTION_COMPONENT]
                    ['children']['migration_from_magento1']
            );

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        }

        if (!$this->isMigrationFromMagento1InProgress()) {
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::ADVANCED_SECTION_WIZARD]);
        }

        return $result;
    }

    //########################################

    private function isMigrationFromMagento1InProgress()
    {
        if (is_null($this->migrationFromMagento1Status)) {
            $select = $this->resourceConnection->getConnection()
                ->select()
                ->from($this->resourceConnection->getTableName('core_config_data'), 'value')
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', BaseMigrationFromMagento1::WIZARD_STATUS_CONFIG_PATH);

            $this->migrationFromMagento1Status = $this->resourceConnection->getConnection()->fetchOne($select);
        }

        return $this->migrationFromMagento1Status === BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED ||
            $this->migrationFromMagento1Status === BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS;
    }

    //########################################
}