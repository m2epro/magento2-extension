<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonBusinessDataMigration extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'amazon_listing_product',
            'amazon_template_selling_format',
        ];
    }

    public function execute()
    {
        $this->getConnection()->exec(<<<SQL

UPDATE `{$this->getFullTableName('amazon_listing_product')}`
SET `online_business_discounts` = NULL
WHERE `online_business_discounts` IS NOT NULL;

SQL
        );

        $this->getConnection()->exec(<<<SQL

UPDATE `{$this->getFullTableName('amazon_template_selling_format')}`
SET `business_discounts_mode` = 0
WHERE `business_discounts_mode` = 3;

SQL
        );

        $this->getConnection()->exec(<<<SQL

UPDATE `{$this->getFullTableName('amazon_template_selling_format')}`
SET `business_discounts_tier_customer_group_id` = 0
WHERE `business_discounts_mode` = 1 AND `business_discounts_tier_customer_group_id` IS NULL;

SQL
        );

        $this->getConnection()->exec(<<<SQL

DELETE `atsfbd`.* FROM `{$this->getFullTableName('amazon_template_selling_format_business_discount')}` AS `atsfbd`
LEFT JOIN `{$this->getFullTableName('amazon_template_selling_format')}` AS `atsf`
  ON `atsf`.`template_selling_format_id` = `atsfbd`.`template_selling_format_id`
WHERE `mode` NOT IN (1, 2, 3) OR `atsf`.`business_discounts_mode` != 2;

SQL
        );
    }

    //########################################
}