<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y20_m08\AmazonSkipTax
 */
class AmazonSkipTax extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $codes = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DC', 'GA', 'HI', 'ID',
            'IL', 'IN', 'IA', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS',
            'NE', 'NV', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'PA', 'PR',
            'RI', 'SC', 'SD', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
        ];

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
            $data['tax']['excluded_states'] = $codes;

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
