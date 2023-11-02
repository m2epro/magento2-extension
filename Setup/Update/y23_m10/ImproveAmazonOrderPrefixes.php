<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

class ImproveAmazonOrderPrefixes extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $tableName = $this->getFullTableName('amazon_account');
        $query = $this->getConnection()
                      ->select()
                      ->from($tableName)
                      ->query();

        while ($row = $query->fetch()) {
            $data = json_decode($row['magento_orders_settings'], true);
            $generalPrefix = $data['number']['prefix']['prefix'] ?? '';
            $data['number']['prefix']['afn-prefix'] = $generalPrefix . ($data['number']['prefix']['afn-prefix'] ?? '');
            $data['number']['prefix']['prime-prefix'] = $generalPrefix . ($data['number']['prefix']['prime-prefix'] ?? '');
            $data['number']['prefix']['b2b-prefix'] = $generalPrefix . ($data['number']['prefix']['b2b-prefix'] ?? '');

            $this->getConnection()->update(
                $tableName,
                ['magento_orders_settings' => json_encode($data)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }
}
