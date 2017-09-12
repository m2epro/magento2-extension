<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AdditionalFieldsForListingProduct extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier("ebay_listing_product")
             ->addColumn("online_is_variation", "SMALLINT(5) UNSIGNED", "NULL", "is_duplicate", true);

        $mainTableName      = $this->getFullTableName("ebay_listing_product");
        $productTableName   = $this->getFullTableName("listing_product");
        $variationTableName = $this->getFullTableName("listing_product_variation");
        $optionTableName    = $this->getFullTableName("listing_product_variation_option");

        for ($i = 0, $step = 1000 ;; $i++) {

            $offset = $step * $i;

            $stmt = $this->getConnection()->query(<<<SQL
                SELECT DISTINCT `elp`.`listing_product_id` as `id`
                FROM {$mainTableName} as `elp`
                INNER JOIN {$productTableName} as `lp` ON `elp`.`listing_product_id` = `lp`.`id`
                INNER JOIN {$variationTableName} as `lpv` ON `elp`.`listing_product_id` = `lpv`.`listing_product_id`
                INNER JOIN {$optionTableName} as `lpvo` ON `lpv`.`id` = `lpvo`.`listing_product_variation_id`
                WHERE `lp`.`status` != 0
                LIMIT {$offset}, {$step}
SQL
            );

            $itemsIdsWithVariations = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $itemsIdsWithVariations[] = $row["id"];
            }

            if (empty($itemsIdsWithVariations)) {
                break;
            }

            $this->getConnection()->update($mainTableName,
                array("online_is_variation" => 1),
                array("listing_product_id IN (?)" => $itemsIdsWithVariations)
            );

            if (count($itemsIdsWithVariations) < $step) {
                break;
            }
        }

        // ---------------------------------------

        $this->getTableModifier("ebay_listing_product")
             ->addColumn("online_is_auction_type", "SMALLINT(5) UNSIGNED", "NULL", "online_is_variation", true);

        for ($i = 0, $step = 1000 ;; $i++) {

            $offset = $step * $i;

            $stmt = $this->getConnection()->query(<<<SQL
                SELECT `elp`.`listing_product_id` as `id`
                FROM {$mainTableName} as `elp`
                INNER JOIN {$productTableName} as `lp` ON `elp`.`listing_product_id` = `lp`.`id`
                WHERE `lp`.`status` != 0 AND `lp`.`status` != 6
                AND `elp`.`online_start_price` > 0 AND `elp`.`online_is_variation` = 0
                LIMIT {$offset}, {$step}
SQL
            );

            $itemsIds = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $itemsIds[] = $row["id"];
            }

            if (empty($itemsIds)) {
                break;
            }

            $this->getConnection()->update($mainTableName,
                array("online_is_auction_type" => 1),
                array("listing_product_id IN (?)" => $itemsIds)
            );

            if (count($itemsIds) < $step) {
                break;
            }
        }
    }

    //########################################
}