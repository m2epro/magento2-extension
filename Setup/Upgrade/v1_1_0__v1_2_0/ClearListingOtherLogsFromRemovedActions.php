<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ClearListingOtherLogsFromRemovedActions extends AbstractFeature
{
    //########################################

    /**
     * Backup is not required due to possible huge size of the table
     * @return array
     */
    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        $this->getConnection()->delete($this->getFullTableName('listing_other_log'), [
            'action IN (?)' => [2, 3, 9, 10, 11, 12, 13, 14, 15, 16, 17]
        ]);
    }

    //########################################
}