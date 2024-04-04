<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class CombineInactiveProductStatuses extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute()
    {
        $oldStatuses = [1, 3, 4];
        $tables = ['listing_product', 'listing_other'];

        foreach ($tables as $table) {
            $this->getConnection()->update(
                $this->getFullTableName($table),
                ['status' => \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE],
                ['status IN (?)' => $oldStatuses]
            );
        }
    }
}
