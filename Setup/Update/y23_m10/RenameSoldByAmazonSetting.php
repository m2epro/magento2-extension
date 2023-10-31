<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

class RenameSoldByAmazonSetting extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $amazonAccountTable = $this->getFullTableName('amazon_account');

        $query = $this->getConnection()
                      ->select()
                      ->from($amazonAccountTable)
                      ->query();

        while ($row = $query->fetch()) {
            $magentoOrdersSettings = json_decode($row['magento_orders_settings'], true);

            if (isset($magentoOrdersSettings['shipping_information']['sold_by_amazon'])) {
                $magentoOrdersSettings['shipping_information']['import_labels'] =
                    $magentoOrdersSettings['shipping_information']['sold_by_amazon'];

                unset($magentoOrdersSettings['shipping_information']['sold_by_amazon']);

                $this->getConnection()->update(
                    $amazonAccountTable,
                    ['magento_orders_settings' => json_encode($magentoOrdersSettings)],
                    ['account_id = ?' => $row['account_id']]
                );
            }
        }
    }
}
