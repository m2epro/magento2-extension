<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m03;

class AddKtypesResolveAttemptColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->getTableModifier('ebay_listing_product')
             ->addColumn(
                 'ktypes_resolve_attempt',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'ktypes_resolve_last_try_date'
             );
    }
}
