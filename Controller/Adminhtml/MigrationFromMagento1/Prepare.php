<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

class Prepare extends Base
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module\Maintenance\General')->enable();

        try {
            $this->prepareDatabase();
        } catch (\Exception $exception) {
            $this->getRawResult()->setContents(
                $this->__(
                    'Module was not prepared for migration. Reason: %error_message%.',
                    array('error_message' => $exception->getMessage())
                )
            );

            return $this->getRawResult();
        }

        $this->getHelper('Magento')->clearCache();

        $this->getRawResult()->setContents(
            $this->__('Module was successfully prepared for migration.')
        );

        return $this->getRawResult();
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