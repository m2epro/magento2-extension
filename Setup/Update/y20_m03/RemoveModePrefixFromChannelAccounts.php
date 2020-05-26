<?php

namespace Ess\M2ePro\Setup\Update\y20_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m03\RemoveModePrefixFromChannelAccounts
 */
class RemoveModePrefixFromChannelAccounts extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $dataHelper = $this->helperFactory->getObject('Data');

        foreach ($this->helperFactory->getObject('Component')->getComponents() as $component) {
            $accountChannelTable = $this->getFullTableName("{$component}_account");

            $query = $this->installer->getConnection()
                ->select()
                ->from($accountChannelTable)
                ->query();

            while ($row = $query->fetch()) {
                $magentoOrdersSettings = $dataHelper->jsonDecode($row['magento_orders_settings']);
                if (!isset($magentoOrdersSettings['number']['prefix']['mode'])) {
                    continue;
                }

                if ($magentoOrdersSettings['number']['prefix']['mode'] == 0) {
                    foreach ($magentoOrdersSettings['number']['prefix'] as &$setting) {
                        $setting = '';
                    }
                    unset($setting);
                }
                unset($magentoOrdersSettings['number']['prefix']['mode']);

                $this->installer->getConnection()->update(
                    $accountChannelTable,
                    ['magento_orders_settings' => $dataHelper->jsonEncode($magentoOrdersSettings)],
                    ['account_id = ?' => (int)$row['account_id']]
                );
            }
        }
    }

    //########################################
}
