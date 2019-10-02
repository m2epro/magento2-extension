<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SaveOnlineIdentifiers extends AbstractFeature
{
    public function getBackupTables()
    {
        return ['listing_product_variation'];
    }

    public function execute()
    {
        $queryStmt = $this->getConnection()
            ->select()
            ->from(
                $this->getFullTableName('listing_product_variation'),
                array('id', 'additional_data')
            )
            ->where("component_mode = 'ebay'")
            ->where("additional_data LIKE '%ebay_mpn_value%'")
            ->query();

        while ($row = $queryStmt->fetch()) {

            $additionalData = (array)json_decode($row['additional_data'], true);
            $additionalData['online_product_details']['mpn'] = $additionalData['ebay_mpn_value'];
            unset($additionalData['ebay_mpn_value']);
            $additionalData = json_encode($additionalData);

            $this->getConnection()->update(
                $this->getFullTableName('listing_product_variation'),
                array('additional_data' => $additionalData),
                array('id = ?' => (int)$row['id'])
            );
        }
    }
}