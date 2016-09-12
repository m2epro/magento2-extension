<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeDevelopment\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RenameServerBaseurlGroup extends AbstractFeature
{
    //########################################

    public function execute()
    {
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
{

}