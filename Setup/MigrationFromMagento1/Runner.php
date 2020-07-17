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
    const SUPPORTED_MIGRATION_VERSION = '6.7.*';

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

    public function prepare()
    {
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');
        $this->helperFactory->getObject('Module\Maintenance')->enable();

        $allTables  = $structureHelper->getModuleTables();
        $skipTables = [
            'm2epro_setup',
            'm2epro_config',
        ];

        foreach ($allTables as $tableName) {
            if (in_array($tableName, $skipTables)) {
                continue;
            }

            $this->resourceConnection->getConnection()->dropTable(
                $structureHelper->getTableNameWithPrefix($tableName)
            );
        }

        $this->resourceConnection->getConnection()->renameTable(
            $structureHelper->getTableNameWithPrefix('m2epro_config'),
            $structureHelper->getTableNameWithPrefix('m2epro_config_m2_original')
        );

        $this->helperFactory->getObject('Magento')->clearCache();
    }

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

        $this->cleanUp();
    }

    protected function cleanUp()
    {
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');
        $this->resourceConnection->getConnection()->dropTable(
            $structureHelper->getTableNameWithPrefix('m2epro_config_m2_original')
        );
    }

    //----------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function checkPreconditions()
    {
        $configTableName = $this->getOldTablesPrefix() . 'm2epro_config';

        if (!$this->helperFactory->getObject('Module\Maintenance')->isEnabled() ||
            !$this->resourceConnection->getConnection()->isTableExists($configTableName)) {
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
                                           ->from($configTableName)
                                           ->where('`group` LIKE ?', '/migrationtomagento2/source/%');

        $sourceParams = [];

        foreach ($this->resourceConnection->getConnection()->fetchAll($select) as $paramRow) {
            $sourceParams[$paramRow['group']][$paramRow['key']] = $paramRow['value'];
        }

        if (empty($sourceParams['/migrationtomagento2/source/']['is_prepared_for_migration']) ||
            empty($sourceParams['/migrationtomagento2/source/']['version'])
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

        if (!$this->compareVersions($sourceParams['/migrationtomagento2/source/']['version'])) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Your current Module version <b>%v%</b> for Magento v1.x does not support Data Migration.
                    Please read our <a href="%url%" target="_blank">Migration Guide</a> for more details.',
                    $sourceParams['/migrationtomagento2/source/']['version'],
                    $this->helperFactory->getObject('Module\Support')->getDocumentationArticleUrl('x/EgA9AQ')
                ])
            );
        }
    }

    /**
     * Example: v6.0.0 and v6.0.10 will pass 6.0.*
     */
    private function compareVersions($version)
    {
        $pattern = explode('.', self::SUPPORTED_MIGRATION_VERSION);

        foreach (explode('.', $version) as $vIndex => $vPart) {
            if (!isset($pattern[$vIndex])) {
                return false;
            }

            if ($pattern[$vIndex] === '*') {
                return true;
            }

            if ($pattern[$vIndex] != $vPart) {
                return false;
            }
        }

        return true;
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
     * @return IdModifierInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getDbModifierObject()
    {
        $pattern = str_replace('.*', '', self::SUPPORTED_MIGRATION_VERSION);
        $className = 'Ess\M2ePro\Setup\MigrationFromMagento1\\v' . str_replace('.', '_', $pattern) . '\Modifier';

        if (!class_exists($className)) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Migration version %v% doesn\'t exists.',
                    self::SUPPORTED_MIGRATION_VERSION
                ])
            );
        }

        /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\IdModifierInterface $dbModifier */
        $dbModifier = $this->objectManager->create($className);

        if (!$dbModifier instanceof \Ess\M2ePro\Setup\MigrationFromMagento1\IdModifierInterface) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Migration modifier object must implement IdModifierInterface.'
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
        $configTables = $this->installer->getConnection()->getTables('%m2epro_config');

        if (count($configTables) === 1) {
            $prefix = $this->installer->getConnection()
                                      ->select()
                                      ->from(reset($configTables), ['value'])
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
