<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

class RemoveLastAccessAndRunFromConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->copyDataFromConfig();
        $this->removeDataFromConfig();
    }

    private function copyDataFromConfig()
    {
        $configTable = $this->getFullTableName('config');

        $registryTable = $this->getFullTableName('registry');

        $select = $this->getConnection()->select()
                             ->from($configTable)
                             ->where('`group` = ?', '/cron/')
                             ->where('`key` IN (?)', ['last_run', 'last_access']);

        $data = $this->getConnection()->fetchAll($select);

        foreach ($data as $row) {
            $key = '/cron/' . $row['key'] . '/';

            $this->getConnection()->insert(
                $registryTable,
                [
                    'key' => $key,
                    'value' => $row['value'],
                    'update_date' => $row['update_date'],
                    'create_date' => $row['create_date'],
                ]
            );
        }
    }

    private function removeDataFromConfig()
    {
        $this->getConnection()->delete(
            $this->getFullTableName('config'),
            [
                '`key` IN (?)' => ['last_access', 'last_run']
            ]
        );
    }
}
