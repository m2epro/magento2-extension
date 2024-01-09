<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

class AddTecdocKtypesIt extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $config = $this->getConfigModifier('module');
        $config->insert(
            '/ebay/configuration/',
            'tecdoc_ktypes_product_mpn_attribute'
        );
        $config->insert(
            '/ebay/configuration/',
            'tecdoc_ktypes_it_vat_id'
        );

        $this->getTableModifier('ebay_listing_product')
             ->addColumn(
                 'ktypes_resolve_status',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'template_synchronization_id',
                 false,
                 false
             )
             ->addColumn(
                 'ktypes_resolve_last_try_date',
                 'DATETIME',
                 'NULL',
                 'ktypes_resolve_status',
                 false,
                 false
             )
             ->commit();
    }
}
