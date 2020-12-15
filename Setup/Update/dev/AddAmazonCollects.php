<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\dev;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\dev\AddAmazonCollects
 * @codingStandardsIgnoreFile
 */
class AddAmazonCollects extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('amazon_account'))
            ->where('marketplace_id = ?', 29)
            ->query();

        while ($row = $query->fetch()) {
            $data = json_decode($row['magento_orders_settings'], true);

            if (!$this->canAddExcludedStates($data)) {
                continue;
            }

            $data['tax']['amazon_collects'] = 1;

            $this->getConnection()->update(
                $this->getFullTableName('amazon_account'),
                ['magento_orders_settings' => json_encode($data)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }

    private function canAddExcludedStates($data)
    {
        if (!isset($data['tax']['mode'])) {
            return false;
        }

        if ($data['tax']['mode'] != 1 && $data['tax']['mode'] != 3) {
            return false;
        }

        return true;
    }

    //########################################
}
