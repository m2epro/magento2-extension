<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m11\EbayOrderCancelRefund
 */
class EbayOrderCancelRefund extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_order')->addColumn(
            'cancellation_status',
            'TINYINT UNSIGNED NOT NULL',
            '0',
            'payment_status'
        );

        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_account'))
            ->query();

        while ($row = $query->fetch()) {
            $data = \Ess\M2ePro\Helper\Json::decode($row['magento_orders_settings']);

            $data['refund_and_cancellation']['refund_mode'] = '0';

            $this->getConnection()->update(
                $this->getFullTableName('ebay_account'),
                ['magento_orders_settings' => \Ess\M2ePro\Helper\Json::encode($data)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }

    //########################################
}
