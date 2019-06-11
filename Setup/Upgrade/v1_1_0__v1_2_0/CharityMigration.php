<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class CharityMigration extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_template_selling_format'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')
            ->changeColumn(
                'charity', 'TEXT', NULL, 'best_offer_reject_attribute', true
            );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['charity' => NULL],
            '`charity` = "" OR `charity` = "[]" OR `charity` = "{}"'
        );

        $select = $this->getConnection()->select()->from(
            ['etsf' => $this->getFullTableName('ebay_template_selling_format')]
        );
        $select->where('`etsf`.`is_custom_template` = ?', 1);
        $select->where('`etsf`.`charity` IS NOT NULL');
        $select->group('template_selling_format_id');

        // Joining Listings and Products with template mode Custom
        $select->joinLeft(
            ['el' => $this->getFullTableName('ebay_listing')],
            '`etsf`.`template_selling_format_id`=`el`.`template_selling_format_custom_id` AND
                `el`.`template_selling_format_mode` = 1',
            ['listing_id']
        );

        $select->joinLeft(
            ['elp' => $this->getFullTableName('ebay_listing_product')],
            '`etsf`.`template_selling_format_id`=`elp`.`template_selling_format_custom_id` AND
                `elp`.`template_selling_format_mode` = 1',
            ['listing_product_id']
        );

        $select->where('`el`.`listing_id` IS NOT NULL OR `elp`.`listing_product_id` IS NOT NULL ');

        $select->joinLeft(
            ['l' => $this->getFullTableName('listing')],
            '`el`.`listing_id`=`l`.`id`',
            []
        );

        $select->joinLeft(
            ['lp' => $this->getFullTableName('listing_product')],
            '`elp`.`listing_product_id`=`lp`.`id`',
            []
        );

        $select->joinLeft(
            ['lpl' => $this->getFullTableName('listing')],
            '`lp`.`listing_id`=`lpl`.`id`',
            []
        );

        $select->columns([
            'marketplace_id' => 'IF(
                `el`.`listing_id` IS NOT NULL,
                `l`.`marketplace_id`,
                IF(
                    `elp`.`listing_product_id` IS NOT NULL,
                    `lpl`.`marketplace_id`,
                    NULL
                )
            )'
        ]);

        $sellingFormatTemplates = $this->getConnection()->fetchAll($select, [], \PDO::FETCH_ASSOC);

        $resetCharityConditions = [];
        if (!empty($sellingFormatTemplates)) {
            $resetCharityConditions[] = $this->getConnection()->quoteInto(
                '`template_selling_format_id` NOT IN (?)',
                array_column($sellingFormatTemplates, 'template_selling_format_id')
            );
        }

        $resetCharityConditions[] = '`charity` IS NOT NULL';

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['charity' => NULL],
            $resetCharityConditions
        );

        if (empty($sellingFormatTemplates)) {
            return;
        }

        foreach ($sellingFormatTemplates as $sellingFormatTemplate) {

            $oldCharity = json_decode($sellingFormatTemplate['charity'], true);

            if (!empty($oldCharity[$sellingFormatTemplate['marketplace_id']])) {
                continue;
            }

            $newCharity = [];
            $newCharity[$sellingFormatTemplate['marketplace_id']] = [
                'marketplace_id' => $sellingFormatTemplate['marketplace_id'],
                'organization_id' => $oldCharity['id'],
                'organization_name' => $oldCharity['name'],
                'organization_custom' => 1,
                'percentage' => $oldCharity['percentage'],
            ];

            $this->getConnection()->update(
                $this->getFullTableName('ebay_template_selling_format'),
                ['charity' => json_encode($newCharity)],
                $this->getConnection()->quoteInto(
                    '`template_selling_format_id` = ?', $sellingFormatTemplate['template_selling_format_id']
                )
            );
        }
    }

    //########################################
}