<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Magento\Setup\Module\Setup as MagentoSetup;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\Runner
 */
class Runner
{
    const SUPPORTED_MIGRATION_VERSION = '1.0.0';

    private $mapper;
    private $helperFactory;
    private $objectManager;
    private $resourceConnection;
    private $installer;
    private $deploymentConfig;

    //########################################

    /**
     * Runner constructor.
     * @param Mapper $mapper
     * @param HelperFactory $helperFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Mapper $mapper,
        HelperFactory $helperFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig
    ) {
        $this->mapper             = $mapper;
        $this->helperFactory      = $helperFactory;
        $this->objectManager      = $objectManager;
        $this->resourceConnection = $resourceConnection;
        $this->installer          = new MagentoSetup($resourceConnection);
        $this->deploymentConfig   = $deploymentConfig;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function run()
    {
        $this->checkPreconditions();
        $this->prepareTablesPrefixes();

        $dbModifier = $this->getDbModifierObject();
        $dbModifier->process();

        $this->mapper->map();
    }

    //----------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function checkPreconditions()
    {
        $primaryConfigTableName = $this->getOldTablesPrefix() . 'm2epro_primary_config';

        if (!$this->helperFactory->getObject('Module\Maintenance')->isEnabled() ||
            !$this->resourceConnection->getConnection()->isTableExists($primaryConfigTableName)) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'It seems that M2E Pro MySQL tables dump from Magento v1.x has not been copied into the database
                    of Magento v2.x. You should complete all the required actions before you proceed to the next step.
                    Please, follow the instructions below.'
                ])
            );
        }

        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from($primaryConfigTableName)
                                           ->where('`group` LIKE ?', '/migrationtomagento2/source/%');

        $sourceParams = [];

        foreach ($this->resourceConnection->getConnection()->fetchAll($select) as $paramRow) {
            $sourceParams[$paramRow['group']][$paramRow['key']] = $paramRow['value'];
        }

        if (empty($sourceParams['/migrationtomagento2/source/']['is_prepared_for_migration']) ||
            empty($sourceParams['/migrationtomagento2/source/m2epro/']['version'])
        ) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'M2E pro tables dump for Magento v1.x was not properly configured
                    before transferring to M2E Pro for Magento v2.x. To prepare it properly,
                    you should press Proceed button in
                    System > Configuration > M2E Pro > Advanced section, then create
                    new dump of M2E Pro tables from the database and transfer it to your
                    Magento v2.x.'
                ])
            );
        }

        if ($sourceParams['/migrationtomagento2/source/']['version'] != self::SUPPORTED_MIGRATION_VERSION) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'M2E pro tables dump for Magento v1.x cannot be migrated to Magento v2.x as your current
                    version %v% of M2E Pro for Magento v1.x does not support the ability to migrate.
                    Please, upgrade your M2E Pro to %v2% version, then prepare data by pressing
                    Proceed button in System > Configuration > M2E Pro > Advanced section, create a dump of M2E Pro
                    tables from Magento v1.x database and transfer it to Magento v2.x.',
                    $sourceParams['/migrationtomagento2/source/m2epro/']['version'],
                    self::SUPPORTED_MIGRATION_VERSION
                ])
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function prepareTablesPrefixes()
    {
        $oldTablesPrefix = $this->getOldTablesPrefix();
        $currentTablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (trim($oldTablesPrefix) == trim($currentTablesPrefix)) {
            return;
        }

        foreach ($this->installer->getConnection()->getTables($oldTablesPrefix.'m2epro_%') as $oldTableName) {
            $clearTableName = str_replace($oldTablesPrefix.'m2epro_', '', $oldTableName);
            $this->installer->getConnection()->renameTable(
                $oldTableName,
                $this->helperFactory->getObject('Module_Database_Tables')->getFullName($clearTableName)
            );
        }
    }

    //########################################

    /**
     * @return IModifier
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getDbModifierObject()
    {
        $className = 'Ess\M2ePro\Setup\MigrationFromMagento1\\v'
                     . str_replace('.', '_', self::SUPPORTED_MIGRATION_VERSION) . '\Modifier';

        if (!class_exists($className)) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Migration version %v% doesn\'t exists.',
                    self::SUPPORTED_MIGRATION_VERSION
                ])
            );
        }

        /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\IModifier $dbModifier */
        $dbModifier = $this->objectManager->create($className);

        if (!$dbModifier instanceof \Ess\M2ePro\Setup\MigrationFromMagento1\IModifier) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Migration modifier object must implement IModifier interface.'
                ])
            );
        }

        return $dbModifier;
    }

    /**
     * @return string
     */
    private function getOldTablesPrefix()
    {
        $prefix = false;
        $primaryConfigTables = $this->installer->getConnection()->getTables('%m2epro_primary_config');

        if (count($primaryConfigTables) === 1) {
            $prefix = $this->installer->getConnection()
                                      ->select()
                                      ->from(reset($primaryConfigTables), ['value'])
                                      ->where('`group` = ?', '/migrationToMagento2/source/magento/')
                                      ->where('`key` = ?', 'tables_prefix')
                                      ->query()->fetchColumn();
        }

        if ($prefix === false) {
            $allM2eProTables = $this->installer->getConnection()->getTables('%m2epro_%');
            $prefix = (string)preg_replace('/m2epro_[A-Za-z0-9_]+$/', '', reset($allM2eProTables));
        }

        return (string)$prefix;
    }

    //########################################
}
