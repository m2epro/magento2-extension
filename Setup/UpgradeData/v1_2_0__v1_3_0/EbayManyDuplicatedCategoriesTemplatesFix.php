<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayManyDuplicatedCategoriesTemplatesFix extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['listing'];
    }

    public function execute()
    {
        $listingTable = $this->getFullTableName('listing');

        $listings = $this->getConnection()->query(<<<SQL
SELECT * FROM {$listingTable} WHERE `additional_data` LIKE '%mode_same_category_data%';
SQL
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($listings as $listing) {

            $listingId = $listing['id'];
            $additionalData = (array)@json_decode($listing['additional_data'], true);

            if (!empty($additionalData['mode_same_category_data']['specifics'])) {
                foreach ($additionalData['mode_same_category_data']['specifics'] as &$specific) {
                    unset($specific['attribute_id'], $specific['mode_relation_id']);
                }
                unset($specific);
            }

            $this->getConnection()->update(
                $listingTable,
                array('additional_data' => json_encode($additionalData)),
                array('id = ?' => $listingId)
            );
        }
    }

    //########################################
}