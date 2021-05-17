<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\ChangeSingleItemOption
 */
class ChangeSingleItemOption extends AbstractFeature
{
    //########################################

    private $tables = [
        'ebay_account_pickup_store',
        'ebay_template_selling_format',
        'amazon_template_selling_format',
        'walmart_template_selling_format'
    ];

    public function execute()
    {
        foreach ($this->tables as $table) {
            $this->getConnection()->update(
                $this->getFullTableName($table),
                [
                    'qty_mode' => 3, // Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER
                    'qty_custom_value' => 1,
                ],
                [
                    'qty_mode = ?' => 2
                ]
            );
        }
    }

    //########################################
}
