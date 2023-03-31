<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m03;

class RenameClientsToAccounts extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            [
                'key' => 'accounts_url',
                'value' => 'https://accounts.m2e.cloud/'
            ],
            [
                "`key` = ?" => "clients_portal_url"
            ]
        );
    }
}
