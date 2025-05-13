<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m04;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item as AmazonItemResource;

class AddCustomizationDetailsToAmazonOrderItem extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConfigModifier()->insert(
            '/amazon/configuration/',
            'is_need_parse_buyer_customized_data',
            '0'
        );

        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER_ITEM);

        $modifier->addColumn(
            AmazonItemResource::COLUMN_CUSTOMIZATION_DETAILS,
            'LONGTEXT',
            'NULL',
            null,
            false,
            false
        );

        $modifier->commit();
    }
}
