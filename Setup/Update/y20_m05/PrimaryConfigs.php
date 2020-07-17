<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\PrimaryConfigs
 */
class PrimaryConfigs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('primary_config'))) {
            return;
        }

        $select = $this->installer->getConnection()
            ->select()
            ->from(
                $this->getFullTableName('primary_config'),
                ['group', 'key', 'value', 'update_date', 'create_date']
            );

        $this->installer->getConnection()->query(
            $this->installer->getConnection()
                ->insertFromSelect(
                    $select,
                    $this->getFullTableName('module_config'),
                    ['group','key','value','update_date','create_date']
                )
        );

        $this->installer->getConnection()->dropTable(
            $this->getFullTableName('primary_config')
        );

        $this->migrateConfig();
    }

    protected function migrateConfig()
    {
        $this->updateConfig(
            $this->from('/server/', 'installation_key'),
            $this->to('/', 'installation_key')
        );

        $this->updateConfig(
            $this->from('/license/', 'domain'),
            $this->to('/license/domain/', 'valid')
        );
        $this->updateConfig(
            $this->from('/license/valid/', 'domain'),
            $this->to('/license/domain/', 'is_valid')
        );
        $this->updateConfig(
            $this->from('/license/', 'ip'),
            $this->to('/license/ip/', 'valid')
        );
        $this->updateConfig(
            $this->from('/license/valid/', 'ip'),
            $this->to('/license/ip/', 'is_valid')
        );

        $realDomain = $this->getConfigModifier('cache')
            ->getEntity('/location_info/', 'domain')
            ->getValue();

        $realIp = $this->getConfigModifier('cache')
            ->getEntity('/location_info/', 'ip')
            ->getValue();

        $this->getConfigModifier('module')->insert('/license/domain/', 'real', $realDomain);
        $this->getConfigModifier('module')->insert('/license/ip/', 'real', $realIp);

        //----------------------------------------

        $this->updateConfig(
            $this->from('/debug/exceptions/', 'send_to_server'),
            $this->to('/server/exceptions/', 'send')
        );
        $this->updateConfig(
            $this->from('/debug/exceptions/', 'filters_mode'),
            $this->to('/server/exceptions/', 'filters')
        );
        $this->updateConfig(
            $this->from('/debug/fatal_error/', 'send_to_server'),
            $this->to('/server/fatal_error/', 'send')
        );
        $this->updateConfig(
            $this->from('/debug/logging/', 'send_to_server'),
            $this->to('/server/logging/', 'send')
        );

        //----------------------------------------

        $this->getConfigModifier('module')->insert('/server/location/', 'current_index', '1');
    }

    //########################################

    protected function updateConfig(array $from, array $to)
    {
        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            [
                'group' => $to['group'],
                'key'   => $to['key']
            ],
            [
                '`key` = ?'   => $from['key'],
                '`group` = ?' => $from['group']
            ]
        );
    }

    protected function from($group, $key)
    {
        return ['group' => $group, 'key' => $key];
    }

    protected function to($group, $key)
    {
        return ['group' => $group, 'key' => $key];
    }

    //########################################
}
