<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RemoveUnsupportedAmazonMarketplaces extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['marketplace', 'amazon_marketplace'];
    }

    public function execute()
    {
        $this->getConnection()->delete($this->getFullTableName('marketplace'), [
            'id IN (?)' => [27, 32]
        ]);
        $this->getConnection()->delete($this->getFullTableName('amazon_marketplace'), [
            'marketplace_id IN (?)' => [27, 32]
        ]);
    }

    //########################################
}