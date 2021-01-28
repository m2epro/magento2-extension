<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\AddShipmentToAmazonListing
 */
class AddShipmentToAmazonListing extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if (!$this->getTableModifier('amazon_listing')->isColumnExists('template_shipping_id')) {
            $this->getTableModifier('amazon_listing')
                ->addColumn(
                    'template_shipping_id',
                    'INT(11) UNSIGNED DEFAULT NULL',
                    null,
                    'template_synchronization_id',
                    true
                );
        }
    }

    //########################################
}
