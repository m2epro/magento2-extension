<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayVariationsWithASpace extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'listing_product_variation',
            'listing_product_variation_option',
            'ebay_listing_product_variation',
            'ebay_listing_product_variation_option',
        ];
    }

    public function execute()
    {
        $listingProductVariationOption = $this->getFullTableName('listing_product_variation_option');
        $listingProductVariation       = $this->getFullTableName('listing_product_variation');

        $ebayListingProductVariationOption = $this->getFullTableName('ebay_listing_product_variation_option');
        $ebayListingProductVariation       = $this->getFullTableName('ebay_listing_product_variation');

        $this->getConnection()->query(<<<SQL
UPDATE `{$listingProductVariationOption}`

SET `attribute` = TRIM(`attribute`),
    `option` = TRIM(`option`)

WHERE `component_mode` = 'ebay' AND
      `product_type` = 'configurable' AND
      (
         `attribute` LIKE '% ' OR `attribute` LIKE ' %' OR
         `option` LIKE '% ' OR `option` LIKE ' %'
      );
SQL
        );

        $stmt = $this->getConnection()->query(<<<SQL
SELECT
  COUNT(`lpv`.`id`) - 1 AS `duplicates`,
  GROUP_CONCAT(DISTINCT `lpvo`.`listing_product_variation_id` SEPARATOR ',') AS `duplicated_variations_ids`

FROM `{$listingProductVariationOption}` `lpvo`
  INNER JOIN `{$listingProductVariation}` `lpv` ON `lpv`.`id` = `lpvo`.`listing_product_variation_id`

WHERE
  `lpvo`.`component_mode` = 'ebay' AND
  `lpvo`.`product_type` = 'configurable'

GROUP BY `lpv`.`listing_product_id`, `lpvo`.`product_id`, `lpvo`.`attribute`
HAVING `duplicates` >= 1;
SQL
        );

        $duplicatedVariationsIds = array(); // will be deleted
        $originalVariationsIds   = array(); // will be updated

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $idsForRemove = explode(',', $row['duplicated_variations_ids']);
            $originalVariationsIds[] = array_shift($idsForRemove);

            $duplicatedVariationsIds = array_merge($duplicatedVariationsIds, $idsForRemove);
        }

        foreach (array_chunk($originalVariationsIds, 1000) as $originalVariationsIdsPart) {

            $originalVariationsIdsPart = implode(',', $originalVariationsIdsPart);
            $this->getConnection()->query(<<<SQL
UPDATE `{$ebayListingProductVariation}`
SET `add` = 0, `delete` = 0
WHERE `listing_product_variation_id` IN ({$originalVariationsIdsPart});
SQL
            );
        }

        foreach (array_chunk($duplicatedVariationsIds, 1000) as $duplicatedVariationsIdsPart) {

            $duplicatedVariationsIdsPart = implode(',', $duplicatedVariationsIdsPart);
            $this->getConnection()->query(<<<SQL
DELETE `lpv`, `elpv`
    FROM `{$listingProductVariation}` `lpv`
INNER JOIN `{$ebayListingProductVariation}` `elpv`
    ON `lpv`.`id` = `elpv`.`listing_product_variation_id`
WHERE `lpv`.`id` IN ({$duplicatedVariationsIdsPart});
SQL
            );

            $this->getConnection()->query(<<<SQL
DELETE `lpvo`, `elpvo`
    FROM `{$listingProductVariationOption}` `lpvo`
INNER JOIN `{$ebayListingProductVariationOption}` `elpvo`
    ON `lpvo`.`id` = `elpvo`.`listing_product_variation_option_id`
WHERE `lpvo`.`listing_product_variation_id` IN ({$duplicatedVariationsIdsPart});
SQL
            );
        }
    }

    //########################################
}