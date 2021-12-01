<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y21_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m11\EbayAddVatMode
 */

class EbayAddVatMode extends AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')->addColumn(
            'vat_mode',
            'SMALLINT(5) UNSIGNED NOT NULL',
            0,
            'lot_size_attribute'
        );

        $tableName =  $this->getFullTableName('ebay_template_selling_format');

        $query = $this->getConnection()
            ->select()
            ->from($tableName)
            ->query();

        while ($row = $query->fetch()) {
            if ($row['vat_percent'] > 0) {
                $this->installer->getConnection()->update(
                    $tableName,
                    ['vat_mode' => 1],
                    ['template_selling_format_id = ?' => $row['template_selling_format_id']]
                );
            }
        }

        $this->helperFactory->getObject('Data_Cache_Permanent')
            ->removeTagValues('template_sellingformat');
    }

    //########################################
}
