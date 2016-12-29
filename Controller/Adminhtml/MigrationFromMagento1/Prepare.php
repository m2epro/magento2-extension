<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

class Prepare extends Base
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module\Maintenance\General')->enable();

        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED);

        try {
            $this->prepareDatabase();
        } catch (\Exception $exception) {
            $this->getMessageManager()->addErrorMessage(
                $this->__(
                    'Module was not prepared for migration. Reason: %error_message%.',
                    array('error_message' => $exception->getMessage())
                )
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/disableModule'));
        }

        $this->getHelper('Magento')->clearCache();

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
    }

    //########################################

    private function prepareDatabase()
    {
        $allTables = $this->getHelper('Module\Database\Structure')->getMySqlTables();

        foreach ($allTables as $tableName) {
            $this->resourceConnection->getConnection()->dropTable(
                $this->resourceConnection->getTableName($tableName)
            );
        }
    }

    //########################################
}