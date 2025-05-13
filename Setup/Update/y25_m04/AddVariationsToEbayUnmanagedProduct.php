<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m04;

class AddVariationsToEbayUnmanagedProduct extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier('m2epro_ebay_listing_other')
             ->addColumn(
                 'online_variations',
                 'LONGTEXT',
                 'NULL',
                 null,
                 false,
                 false
             );
        $modifier->commit();
    }
}
