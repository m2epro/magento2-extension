<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y21_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y21_m04\AmazonChangeSalesPriceDate
 */

class AddShipByDate extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $dataHelper = $this->helperFactory->getObject('Data');
        $this->getTableModifier('walmart_order')
            ->addColumn('shipping_date_to', 'DATETIME', 'NULL', 'shipping_price', true);
        $this->getTableModifier('ebay_order')
            ->addColumn('shipping_date_to', 'DATETIME', 'NULL', 'shipping_details', true);

        $this->getTableModifier('amazon_order')
            ->addColumn('shipping_date_to', 'DATETIME', 'NULL', 'shipping_price', true, false)
            ->addColumn('delivery_date_to', 'DATETIME', 'NULL', 'shipping_date_to', false, false)
            ->commit();

        // ---------------------------------------

        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('amazon_order'))
            ->query();

        while ($row = $query->fetch()) {
            $data = $dataHelper->jsonDecode($row['shipping_dates']);

            $shippingDateTo = isset($data['ship']['to']) ? $data['ship']['to'] : '';
            $deliveryDateTo = isset($data['delivery']['to']) ? $data['delivery']['to'] : '';

            $this->getConnection()->update(
                $this->getFullTableName('amazon_order'),
                [
                    'shipping_date_to' => $shippingDateTo,
                    'delivery_date_to' => $deliveryDateTo
                ],
                ['order_id = ?' => $row['order_id']]
            );
        }

        $this->getTableModifier('amazon_order')->dropColumn('shipping_dates');
    }

    //########################################
}
