<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m08;

class AddDateOfInvoiceSendingToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ORDER)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_DATE_OF_INVOICE_SENDING,
                 'DATETIME',
                 'NULL'
             );
    }
}
