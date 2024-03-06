<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class CombineInactiveEbayProductStatuses extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute()
    {
        $oldStatuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED
        ];
        $tables = ['listing_product', 'listing_other', 'ebay_listing_product_variation'];

        foreach ($tables as $table) {
            $conditions = [
                'status IN (?)' => $oldStatuses,
            ];

            if ($table === 'listing_product' || $table === 'listing_other') {
                $conditions['component_mode = ?'] = 'ebay';
            }

            $this->getConnection()->update(
                $this->getFullTableName($table),
                ['status' => \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE],
                $conditions
            );
        }
    }
}
