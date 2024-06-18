<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

class RemoveEbayCharity extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_SELLING_FORMAT)
             ->dropColumn('charity')
             ->commit();

        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_MARKETPLACE)
             ->dropColumn('is_charity')
             ->commit();

        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_DICTIONARY_MARKETPLACE)
             ->dropColumn('charities')
             ->commit();
    }
}
