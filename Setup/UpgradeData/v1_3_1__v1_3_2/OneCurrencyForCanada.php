<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class OneCurrencyForCanada extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_marketplace'];
    }

    public function execute()
    {
        $mainTableName = $this->getFullTableName("ebay_marketplace");

        $this->getConnection()->update($mainTableName,
            array("currency" => "CAD"),
            array("marketplace_id IN (?)" => array(2, 19))
        );

        $this->getConfigModifier("module")->delete("/ebay/selling/currency/");

        $this->getConfigModifier("cache")->delete("/view/ebay/template/selling_format/multi_currency_marketplace_2/");
        $this->getConfigModifier("cache")->delete("/view/ebay/template/selling_format/multi_currency_marketplace_19/");

        $this->getTableModifier("ebay_marketplace")->dropColumn("is_multi_currency");
    }

    //########################################
}