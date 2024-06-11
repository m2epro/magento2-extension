<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

class AddAmazonShippingPalletDelivery extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ORDER_ITEM)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_IS_SHIPPING_PALLET_DELIVERY,
                 'SMALLINT UNSIGNED NOT NULL',
                 '0',
                 'buyer_customized_info'
             );
    }
}
