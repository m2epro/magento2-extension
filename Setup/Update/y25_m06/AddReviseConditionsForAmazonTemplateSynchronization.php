<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m06;

class AddReviseConditionsForAmazonTemplateSynchronization extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this
            ->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_TEMPLATE_SYNCHRONIZATION);

        $modifier
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization::COLUMN_REVISE_UPDATE_MAIN_DETAILS,
                'SMALLINT UNSIGNED NOT NULL',
                null,
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization::COLUMN_REVISE_UPDATE_PRICE,
                false,
                false
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization::COLUMN_REVISE_UPDATE_IMAGES,
                'SMALLINT UNSIGNED NOT NULL',
                null,
                \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization::COLUMN_REVISE_UPDATE_MAIN_DETAILS,
                false,
                false
            );

        $modifier->commit();
    }
}
