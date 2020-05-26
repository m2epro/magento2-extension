<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 *
 * Migration from 6.5.8 to 1.5.0
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\v1_2_0;

use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\v1_2_0\Modifier
 */
class Modifier implements \Ess\M2ePro\Setup\MigrationFromMagento1\IdModifierInterface
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var MagentoSetup */
    protected $installer;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory  = $modelFactory;

        $this->installer = new MagentoSetup($resourceConnection);
    }

    //########################################

    public function process()
    {
        $this->migrateConfig();
        $this->migrateWizards();
    }

    //########################################

    private function migrateConfig()
    {
        if ($this->getConnection()->isTableExists($this->getFullTableName('module_config')) &&
            $this->getConnection()->isTableExists($this->getFullTableName('config'))
        ) {
            $this->getConnection()->dropTable($this->getFullTableName('module_config'));
        }

        $this->renameTable(
            'config',
            'module_config'
        );

        $moduleConfig = $this->getConfigModifier('module');

        $moduleConfig->getEntity('/support/', 'magento_marketplace_url')->updateValue(
            'https://marketplace.magento.com/m2e-ebay-amazon-magento2.html'
        );

        $value = $moduleConfig->getEntity('/cron/service/', 'disabled')->getValue();
        $moduleConfig->insert('/cron/service_pub/', 'disabled', $value);
        $moduleConfig->getEntity('/cron/service/', 'disabled')->updateGroup('/cron/service_controller/');

        $moduleConfig->getEntity('/', 'is_disabled')->updateValue('0');

        $moduleConfig->insert('/health_status/notification/', 'mode', 1);
        $moduleConfig->insert('/health_status/notification/', 'email', '');
        $moduleConfig->insert('/health_status/notification/', 'level', 40);

        // ---------------------------------------

        $primaryConfig = $this->getConfigModifier('primary');

        $primaryConfig->getEntity('/server/', 'application_key')->updateValue(
            '02edcc129b6128f5fa52d4ad1202b427996122b6'
        );
        $primaryConfig->getEntity('/server/', 'messages')->delete();
    }

    private function migrateWizards()
    {
        $this->getConnection()->truncateTable($this->getFullTableName('wizard'));

        $wizardsData = [
            [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 0,
                'step'     => null,
                'type'     => 1,
                'priority' => 1,
            ],
            [
                'nick'     => 'installationEbay',
                'view'     => 'ebay',
                'status'   => empty($this->getTableRows($this->getFullTableName('ebay_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => empty($this->getTableRows($this->getFullTableName('amazon_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 3,
            ],
            [
                'nick'     => 'installationWalmart',
                'view'     => 'walmart',
                'status'   => empty($this->getTableRows($this->getFullTableName('walmart_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 4,
            ]
        ];

        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), $wizardsData);
    }

    //########################################

    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    private function renameTable($oldTable, $newTable)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->renameTable($oldTable, $newTable);
    }

    //########################################

    private function getTableRows($tableName)
    {
        $select = $this->getConnection()->select()->from($tableName);
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $tableName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Table',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @param $configName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName . '_config';

        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Config',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################
}
