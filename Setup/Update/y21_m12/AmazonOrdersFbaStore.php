<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y21_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y21_m12\AmazonOrdersFbaStore
 */

class AmazonOrdersFbaStore extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('amazon_account'))
            ->query();

        while ($row = $query->fetch()) {
            $data = json_decode($row['magento_orders_settings'], true);

            $data['fba']['store_mode'] = '0';
            $data['fba']['store_id'] = null;

            $this->getConnection()->update(
                $this->getFullTableName('amazon_account'),
                ['magento_orders_settings' => json_encode($data)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }

    //########################################
}
