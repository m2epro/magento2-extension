<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\DisableUploadInvoicesAvailableNl
 */
class DisableUploadInvoicesAvailableNl extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()
            ->update(
                $this->getFullTableName('amazon_marketplace'),
                ['is_upload_invoices_available' => 0],
                ['marketplace_id = ?' => 39]
            );
    }

    //########################################
}
