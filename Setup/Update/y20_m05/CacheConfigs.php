<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\CacheConfigs
 */
class CacheConfigs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('cache_config'))) {
            return;
        }

        $queryStmt = $this->getConnection()->select()->from($this->getFullTableName('cache_config'))->query();

        while ($row = $queryStmt->fetch()) {
            $this->getConnection()->insert(
                $this->getFullTableName('registry'),
                [
                    'key'         => $this->getNewRegistryKey($row),
                    'value'       => $row['value'],
                    'update_date' => $row['update_date'],
                    'create_date' => $row['create_date']
                ]
            );
        }

        $this->getConnection()->dropTable($this->getFullTableName('cache_config'));
    }

    protected function getNewRegistryKey($row)
    {
        $cacheKeysForRename = [
            '/view/ebay/listing/advanced/autoaction_popup/##shown/'  => '/ebay/listing/autoaction_popup/is_shown/',
            '/ebay/synchronization/orders/receive/timeout##fails/'   => '/ebay/orders/receive/timeout_fails/',
            '/ebay/synchronization/orders/receive/timeout##rise/'    => '/ebay/orders/receive/timeout_rise/',
            '/amazon/synchronization/orders/receive/timeout##fails/' => '/amazon/orders/receive/timeout_fails/',
            '/amazon/synchronization/orders/receive/timeout##rise/'  => '/amazon/orders/receive/timeout_rise/',
            '/ebay/motors/##was_instruction_shown/'                  => '/ebay/motors/instruction/is_shown/',
            '/global/notification/message/##skip_static_content_validation_message/' =>
                '/global/notification/static_content/skip_for_version/'
        ];

        $newKey = $row['group'] . '##' . $row['key'] . '/';
        if (array_key_exists($newKey, $cacheKeysForRename)) {
            $newKey = $cacheKeysForRename[$newKey];
        }

        return str_replace('##', '', $newKey);
    }

    //########################################
}
