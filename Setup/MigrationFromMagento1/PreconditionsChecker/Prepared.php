<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\Prepared
 */
class Prepared extends AbstractModel
{
    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function checkPreconditions()
    {
        $configTableName = $this->getOldTablesPrefix() . 'm2epro_config';

        if (!$this->helperFactory->getObject('Module\Maintenance')->isEnabled() ||
            !$this->getConnection()->isTableExists($configTableName)
        ) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'M2E Pro tables dump from Magento v1.x was not imported to the Magneto v2.x database. 
                    Please complete the action, then click <b>Continue</b>.'
                ])
            );
        }

        $select = $this->getConnection()
            ->select()
            ->from($configTableName)
            ->where('`group` LIKE ?', '/migrationtomagento2/source/%');

        $sourceParams = [];

        foreach ($this->getConnection()->fetchAll($select) as $paramRow) {
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

        if (!$this->compareVersions($sourceParams['/migrationtomagento2/source/m2epro/']['version'])) {
            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Your current Module version <b>%v%</b> for Magento v1.x does not support Data Migration.
                    Please read our <a href="%url%" target="_blank">Migration Guide</a> for more details.',
                    $sourceParams['/migrationtomagento2/source/m2epro/']['version'],
                    $this->helperFactory->getObject('Module\Support')->getDocumentationArticleUrl('x/EgA9AQ')
                ])
            );
        }
    }

    //########################################
}
