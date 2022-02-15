<?php

namespace Ess\M2ePro\Setup\Update\y22_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ImportTaxRegistrationId extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_order')
            ->addColumn(
                'tax_registration_id',
                'VARCHAR(72)',
                null,
                'ioss_number',
                false,
                false
            )
            ->commit();
    }
}
