<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y20_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m07\EbayTemplateCustomTemplateId
 */
class EbayTemplateCustomTemplateId extends AbstractFeature
{
    private $templates = [
        'payment',
        'shipping',
        'description',
        'return_policy',
        'selling_format',
        'synchronization'
    ];

    //########################################

    public function execute()
    {
        $this->updateTable('ebay_listing');
        $this->updateTable('ebay_listing_product');
    }

    private function updateTable($tableName)
    {
        $modifier = $this->getTableModifier($tableName);

        foreach ($this->templates as $template) {
            if (!$modifier->isColumnExists("template_{$template}_custom_id")) {
                continue;
            }

            $this->installer->run(
                <<<SQL
UPDATE `{$this->getFullTableName($tableName)}`
SET `template_{$template}_id` = template_{$template}_custom_id
WHERE `template_{$template}_mode` = 1

SQL
            );
        }

        $this->getTableModifier($tableName)
            ->dropColumn("template_payment_custom_id", true, false)
            ->dropColumn("template_shipping_custom_id", true, false)
            ->dropColumn("template_description_custom_id", true, false)
            ->dropColumn("template_return_policy_custom_id", true, false)
            ->dropColumn("template_selling_format_custom_id", true, false)
            ->dropColumn("template_synchronization_custom_id", true, false)
            ->commit();
    }

    //########################################
}
