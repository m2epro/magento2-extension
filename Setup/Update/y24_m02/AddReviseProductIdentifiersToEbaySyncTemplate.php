<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class AddReviseProductIdentifiersToEbaySyncTemplate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $ebaySyncTemplateModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_SYNCHRONIZATION
        );
        $ebaySyncTemplateModifier->addColumn(
            'revise_update_product_identifiers',
            'SMALLINT UNSIGNED',
            0,
            'revise_update_images'
        );

        $modifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING_PRODUCT
        );
        $modifier->addColumn(
            'online_product_identifiers_hash',
            'VARCHAR(255)',
            'NULL',
            'online_images'
        );
    }
}
