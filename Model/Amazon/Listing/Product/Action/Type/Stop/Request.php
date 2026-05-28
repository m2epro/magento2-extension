<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop;

class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    protected function getActionData()
    {
        $data = [
            'sku' => $this->getAmazonListingProduct()->getSku(),
        ];

        return array_merge(
            $data,
            $this->getQtyData()
        );
    }

    public function getQtyData(): array
    {
        $qtyData = ['qty' => 0];

        $onlineMultiLocationInventory = $this->getAmazonListingProduct()->getOnlineMultiLocationInventory();

        if (
            !$this->getAmazonListingProduct()->isMultiLocationInventory()
            || $onlineMultiLocationInventory->isEmpty()
        ) {
            return $qtyData;
        }

        foreach ($onlineMultiLocationInventory->getLocations() as $location) {
            $qtyData['multi_location_inventory'][] = [
                'supply_source_id' => $location->amazonLocationCode,
                'qty' => 0,
            ];
        }

        return $qtyData;
    }
}
