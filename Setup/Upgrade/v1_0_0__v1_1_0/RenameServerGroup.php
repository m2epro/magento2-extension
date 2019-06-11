<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RenameServerGroup extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['primary_config', 'cache_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('primary')->getEntity('/server/', 'default_baseurl_index')
                                           ->updateGroup('/server/location/');

        $this->getConfigModifier('primary')->getEntity('/server/location/', 'default_baseurl_index')
                                           ->updateKey('default_index');

        $query = $this->getConnection()->select()
            ->from($this->getFullTableName('primary_config'))
            ->where("`group` = '/server/' AND (`key` LIKE 'baseurl_%' OR `key` LIKE 'hostname_%')");

        $result = $this->getConnection()->fetchAll($query);

        foreach ($result as $row) {

            $key = (strpos($row['key'], 'baseurl') !== false) ? 'baseurl' : 'hostname';
            $index = str_replace($key.'_', '', $row['key']);
            $group = "/server/location/{$index}/";

            $this->getConfigModifier('primary')->getEntity('/server/', $row['key'])
                                               ->updateGroup($group);

            $this->getConfigModifier('primary')->getEntity($group, $row['key'])
                                               ->updateKey($key);
        }

        $this->getConfigModifier('cache')->getEntity('/server/baseurl/', 'datetime_of_last_switching')
                                         ->updateGroup('/server/location/');
        $this->getConfigModifier('cache')->getEntity('/server/baseurl/', 'current_index')
                                         ->updateGroup('/server/location/');

        $this->getConfigModifier('cache')->getEntity('/default_baseurl_index/','given_by_server_at')
                                         ->updateGroup('/server/location/');
        $this->getConfigModifier('cache')->getEntity('/server/location/','given_by_server_at')
                                         ->updateKey('default_index_given_by_server_at');
    }

    //########################################
}