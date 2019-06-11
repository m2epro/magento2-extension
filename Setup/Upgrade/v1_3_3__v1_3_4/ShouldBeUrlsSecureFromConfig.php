<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ShouldBeUrlsSecureFromConfig extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $value = $this->getConnection()
            ->select()
            ->from($this->installer->getTable('core_config_data'), array('value'))
            ->where('path = ?', 'web/secure/use_in_frontend')
            ->where('scope_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            ->query()
            ->fetchColumn();

        $this->getConfigModifier('module')
             ->insert(
                 '/ebay/description/', 'should_be_ulrs_secure', (int)$value, '0 - No, \r\n1 - Yes'
             );
    }

    //########################################
}