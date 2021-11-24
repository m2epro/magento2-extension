<?php

namespace Ess\M2ePro\Setup\Update\y21_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class PartsCompatibilityImprovement extends AbstractFeature
{
    //########################################

    private $ebayMarketplacesCache = [];

    //########################################

    public function execute()
    {
        $isReviseUpdatePartsColumnExists = $this->getTableModifier('ebay_template_synchronization')
            ->isColumnExists('revise_update_parts');

        if ($isReviseUpdatePartsColumnExists) {
            return;
        }

        //----------------------------------------

        $this->clearUnnecessaryData();
        $this->modifyDBScheme();

        //----------------------------------------

        $motorsAttributesKeys = [
            'motors_epids_attribute',
            'uk_epids_attribute',
            'de_epids_attribute',
            'au_epids_attribute',
            'ktypes_attribute'
        ];

        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('config'))
            ->where('`group` = ?', '/ebay/configuration/')
            ->where('`key` IN (?)', $motorsAttributesKeys)
            ->query();

        $motorsAttributes = [];
        while ($row = $query->fetch()) {
            if ($row['value']) {
                $motorsAttributes[$row['key']] = $row['value'];
            }
        }

        //----------------------------------------

        $listingsStmt = $this->getConnection()
            ->select()
            ->from(
                [
                    'l' => $this->getFullTableName('listing')
                ],
                ['id', 'marketplace_id']
            )
            ->joinInner(
                ['el' => $this->getFullTableName('ebay_listing')],
                'l.id = el.listing_id',
                ['parts_compatibility_mode']
            )
            ->query();

        $listingsInfo = [];
        while ($row = $listingsStmt->fetch()) {
            $marketplaceId = $row['marketplace_id'];
            if (!isset($listingsInfo[$marketplaceId])) {
                $listingsInfo[$marketplaceId] = [];
            }

            $partsCompatibilityMode = $row['parts_compatibility_mode'];
            if (!isset($listingsInfo[$marketplaceId][$partsCompatibilityMode])) {
                $listingsInfo[$marketplaceId][$partsCompatibilityMode] = [];
            }

            $listingsInfo[$marketplaceId][$partsCompatibilityMode][] = $row['id'];
        }

        $isRowIdColumnExists = $this->getConnection()->tableColumnExists(
            $this->installer->getTable('catalog_product_entity_text'),
            'row_id'
        );
        $entityIdColumnName = $isRowIdColumnExists ? 'row_id' : 'entity_id';
        foreach ($listingsInfo as $marketplaceId => $marketplaceInfo) {
            foreach ($marketplaceInfo as $partsCompatibilityMode => $listingsIds) {
                $attributeConfigKey = $this->getPartsCompatibilityAttributeConfigKey(
                    $marketplaceId,
                    $partsCompatibilityMode
                );
                if (!$attributeConfigKey) {
                    continue;
                }

                if (!isset($motorsAttributes[$attributeConfigKey]) || !$motorsAttributes[$attributeConfigKey]) {
                    continue;
                }

                $attributeCode = $motorsAttributes[$attributeConfigKey];
                $template = implode(',', $listingsIds);

                $this->getConnection()->query(
                    <<<SQL
UPDATE `{$this->getFullTableName('ebay_listing_product')}` AS `main`
    INNER JOIN `{$this->getFullTableName('listing_product')}` AS `lp`
        ON lp.id = main.listing_product_id
    INNER JOIN `{$this->getFullTableName('listing')}` AS `l` ON lp.listing_id = l.id
        AND l.id IN ({$template})
    INNER JOIN `{$this->getFullTableName('ebay_listing')}` AS `al` ON al.listing_id = lp.listing_id
    INNER JOIN `{$this->installer->getTable('eav_attribute')}` AS `ea`
        ON ea.attribute_code = '$attributeCode'
    LEFT JOIN `{$this->installer->getTable('catalog_product_entity_text')}` AS `cpev_default`
        ON cpev_default.attribute_id = ea.attribute_id
            AND cpev_default.{$entityIdColumnName}=lp.product_id AND cpev_default.store_id = 0
    LEFT JOIN `{$this->installer->getTable('catalog_product_entity_text')}` AS `cpev`
        ON cpev.attribute_id = ea.attribute_id AND cpev.{$entityIdColumnName}=lp.product_id
            AND cpev.store_id = l.store_id
SET main.online_parts_data = MD5(IFNULL(cpev.value, cpev_default.value))
WHERE IFNULL(cpev.value, cpev_default.value) IS NOT NULL;
SQL
                );
            }
        }
    }

    //----------------------------------------

    private function modifyDBScheme()
    {
        $this->getTableModifier('ebay_listing_product')
            ->addColumn(
                'online_parts_data',
                'VARCHAR(32)',
                null,
                'online_categories_data',
                false,
                false
            )
            ->commit();

        $this->getTableModifier('ebay_template_synchronization')
            ->addColumn(
                'revise_update_parts',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'revise_update_categories',
                false,
                false
            )
            ->commit();

        $this->getConnection()->query(
            <<<SQL
UPDATE `{$this->getFullTableName('ebay_template_synchronization')}`
SET `revise_update_parts` = `revise_update_categories`;
SQL
        );
    }

    //----------------------------------------

    private function clearUnnecessaryData()
    {
        // clear unnecessary data from online_categories_data (motors_epids)
        $this->getConnection()->query(
            <<<SQL
UPDATE `{$this->getFullTableName('ebay_listing_product')}`
SET online_categories_data = CASE
    WHEN INSTR(online_categories_data, 'motors_epids') > 0
    THEN CONCAT(SUBSTRING(online_categories_data, 1, INSTR(online_categories_data, 'motors_epids')-3), '}')
    ELSE online_categories_data
END
WHERE online_categories_data IS NOT NULL;
SQL
        );

        // clear unnecessary data from online_categories_data (motors_ktypes)
        $this->getConnection()->query(
            <<<SQL
UPDATE `{$this->getFullTableName('ebay_listing_product')}`
SET online_categories_data = CASE
    WHEN INSTR(online_categories_data, 'motors_ktypes') > 0
    THEN CONCAT(SUBSTRING(online_categories_data, 1, INSTR(online_categories_data, 'motors_ktypes')-3), '}')
    ELSE online_categories_data
END
WHERE online_categories_data IS NOT NULL;
SQL
        );
    }

    //########################################

    private function getPartsCompatibilityAttributeConfigKey($marketplaceId, $partsCompatibilityMode)
    {
        // https://docs.m2epro.com/help/m1/ebay-integration/parts-compatibility
        // motors_epids_attribute, uk_epids_attribute, de_epids_attribute, au_epids_attribute, ktypes_attribute
        $this->initEbayMarketplacesCache();

        if ($marketplaceId === \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_MOTORS) {
            return 'motors_epids_attribute';
        } elseif ($marketplaceId === \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_UK) {
            if ($partsCompatibilityMode == \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_EPIDS) {
                return 'uk_epids_attribute';
            } else {
                return 'ktypes_attribute';
            }
        } else {
            if (!isset($this->ebayMarketplacesCache[$marketplaceId])) {
                return null;
            }

            if ($this->ebayMarketplacesCache[$marketplaceId]['is_epid'] &&
                $this->ebayMarketplacesCache[$marketplaceId]['is_ktype']
            ) {
                return $partsCompatibilityMode === \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_EPIDS ?
                    $this->ebayMarketplacesCache[$marketplaceId]['origin_country'] . '_epids_attribute'
                    : 'ktypes_attribute';
            } else {
                return $this->ebayMarketplacesCache[$marketplaceId]['is_epid'] ?
                    $this->ebayMarketplacesCache[$marketplaceId]['origin_country'] . '_epids_attribute'
                    : 'ktypes_attribute';
            }
        }
    }

    private function initEbayMarketplacesCache()
    {
        if (!empty($this->ebayMarketplacesCache)) {
            return;
        }

        $query = $this->installer->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_marketplace'))
            ->where('`is_epid` = 1 OR `is_ktype` = 1')
            ->query();

        while ($row = $query->fetch()) {
            $this->ebayMarketplacesCache[$row['marketplace_id']] = [
                'origin_country' => $row['origin_country'],
                'is_epid' => $row['is_epid'],
                'is_ktype' => $row['is_ktype'],
            ];
        }
    }
}
