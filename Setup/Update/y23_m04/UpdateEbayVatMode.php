<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m04;

class UpdateEbayVatMode extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('ebay_template_selling_format');

        if (!$modifier->isColumnExists('price_increase_vat_percent')) {
            return;
        }

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_selling_format'),
            ['vat_mode' => '2'],
            [
                '`vat_mode` = ?' => '1',
                '`price_increase_vat_percent` = ?' => '1'
            ]
        );

        $modifier->dropColumn('price_increase_vat_percent', true, false);
        $modifier->commit();
    }
}
