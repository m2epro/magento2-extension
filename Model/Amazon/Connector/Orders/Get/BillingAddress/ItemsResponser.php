<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress;

/**
 * @method ResponseItem[] getPreparedResponseData()
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    protected function validateResponse(): bool
    {
        return true;
    }

    protected function prepareResponseData(): void
    {
        $rawResponseData = $this->getResponse()->getResponseData();

        $responseItems = [];
        foreach ($rawResponseData['data'] as $orderBillingAddressData) {
            $responseItems[] = new ResponseItem(
                $orderBillingAddressData['order_id'],
                $orderBillingAddressData['billing_address']['country'],
                $orderBillingAddressData['billing_address']['city'],
                $orderBillingAddressData['billing_address']['state'],
                $orderBillingAddressData['billing_address']['postal_code'],
                array_filter([
                    $orderBillingAddressData['billing_address']['address_line_1'],
                    $orderBillingAddressData['billing_address']['address_line_2'],
                    $orderBillingAddressData['billing_address']['address_line_3'],
                ])
            );
        }

        $this->preparedResponseData = $responseItems;
    }
}
