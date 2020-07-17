<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\v6_7;

use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\v6_0\Modifier
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
        $this->migrateWalmartAccount();
    }

    //########################################

    private function migrateConfig()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->getEntity('/', 'is_disabled')->updateValue('0');

        $value = $moduleConfig->getEntity('/cron/service/', 'disabled')->getValue();
        $moduleConfig->insert('/cron/service_pub/', 'disabled', $value);
        $moduleConfig->getEntity('/cron/service/', 'disabled')->updateGroup('/cron/service_controller/');

        $moduleConfig->insert('/health_status/notification/', 'mode', 1);
        $moduleConfig->insert('/health_status/notification/', 'email', '');
        $moduleConfig->insert('/health_status/notification/', 'level', 40);

        $migrateMap = [
            '/server/##application_key',
            '/license/##key',
            '/license/##status',
            '/license/info/##email',
            '/license/domain/##valid',
            '/license/domain/##real',
            '/license/domain/##is_valid',
            '/license/ip/##valid',
            '/license/ip/##real',
            '/license/ip/##is_valid',
            '/support/##magento_marketplace_url'
        ];

        $originalValues = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('config_m2_original'))
            ->query()
            ->fetchAll();

        $config = $this->getConfigModifier();
        foreach ($originalValues as $original) {
            $key = "{$original['group']}##{$original['key']}";
            if (in_array($key, $migrateMap)) {
                $config->getEntity($original['group'], $original['key'])
                       ->updateValue($original['value']);
            }
        }
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
            ],
            [
                'nick'     => 'migrationToInnodb',
                'view'     => '*',
                'status'   => 3,
                'step'     => null,
                'type'     => 1,
                'priority' => 5,
            ]
        ];

        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), $wizardsData);
    }

    /**
     * todo temporary
     */
    private function migrateWalmartAccount()
    {
        $this->getTableModifier('walmart_account')
            ->changeColumn('consumer_id', 'VARCHAR(255)', 'NULL', 'marketplace_id')
            ->renameColumn('old_private_key', 'private_key');
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
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
     */
    protected function getConfigModifier()
    {
        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Config',
            [
                'installer' => $this->installer,
                'tableName' => 'config',
            ]
        );
    }

    //########################################
}
