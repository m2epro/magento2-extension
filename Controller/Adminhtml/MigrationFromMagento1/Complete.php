<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Setup\MigrationFromMagento1;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

class Complete extends Base
{
    const SUPPORTED_SOURCE_VERSION = '6.5.0.9';

    protected $dbModifier;

    //########################################

    public function __construct(
        Context $context,
        MigrationFromMagento1 $dbModifier
    ) {
        $this->dbModifier = $dbModifier;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        if ($this->getCurrentWizardStatus() === BaseMigrationFromMagento1::WIZARD_STATUS_COMPLETED) {
            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
        }

        if ($this->getCurrentWizardStatus() === BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS) {
            $this->getMessageManager()->addNoticeMessage(
                $this->__('Currently the database migration process is already running in the background.
                           So, its restarting is impossible. Please, wait for a while and press Continue button
                           again once the migration will be completed.')
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS);

        try {

            $this->checkPreconditions();

        } catch (\Exception $exception) {

            $this->getMessageManager()->addErrorMessage(
                $this->__('Migration is failed. Reason: %error_message%', $exception->getMessage())
            );
            $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED);

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->dbModifier->prepareTablesPrefixes();

        try {

            $this->dbModifier->process();

        } catch (\Exception $exception) {

            $this->getMessageManager()->addErrorMessage(
                $this->__('Migration is failed. Reason: %error_message%', $exception->getMessage())
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->getHelper('Module\Maintenance\General')->disable();

        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_COMPLETED);
        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
            'migrationFromMagento1', 'completed', 1
        );

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
    }

    //########################################

    private function checkPreconditions()
    {
        $primaryConfigTableName = $this->dbModifier->getOldTablesPrefix() . 'm2epro_primary_config';

        if (!$this->getHelper('Module\Maintenance\General')->isEnabled() ||
            !$this->resourceConnection->getConnection()->isTableExists($primaryConfigTableName))
        {
            throw new \Exception(
                $this->__(
                    'It seems that M2E Pro MySQL tables dump from Magento v1.x has not been copied into the database
                    of Magento v2.x. You should complete all the required actions before you proceed to the next step.
                    Please, follow the instructions below.'
                )
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
                $this->__(
                    'M2E pro tables dump for Magento v1.x was not properly configured
                    before transferring to M2E Pro for Magento v2.x. To prepare it properly,
                    you should press Proceed button in
                    System > Configuration > M2E Pro > Advanced section, then create
                    new dump of M2E Pro tables from the database and transfer it to your
                    Magento v2.x.'
                )
            );
        }

        if ($sourceParams['/migrationtomagento2/source/m2epro/']['version'] != self::SUPPORTED_SOURCE_VERSION) {
            throw new \Exception(
                $this->__(
                    'M2E pro tables dump for Magento v1.x cannot be migrated to Magento v2.x as your current
                    version %v% of M2E Pro for Magento v1.x does not support the ability to migrate.
                    Please, upgrade your M2E Pro to %v2% version, then prepare data by pressing
                    Proceed button in System > Configuration > M2E Pro > Advanced section, create a dump of M2E Pro
                    tables from Magento v1.x database and transfer it to Magento v2.x.',
                    $sourceParams['/migrationtomagento2/source/m2epro/']['version'],
                    self::SUPPORTED_SOURCE_VERSION
                )
            );
        }
    }

    //########################################
}