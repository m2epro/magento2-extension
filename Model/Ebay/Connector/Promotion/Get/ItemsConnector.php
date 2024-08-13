<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\Promotion\Get;

class ItemsConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'marketplace' => $this->params['marketplace'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotion', 'get', 'items'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['promotions']);
    }

    protected function prepareResponseData(): void
    {
        $this->responseData = [];

        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['promotions'] as $promotion) {
            $discounts = [];
            if (isset($promotion['discounts'])) {
                foreach ($promotion['discounts'] as $discountData) {
                    $discounts[] = new \Ess\M2ePro\Model\Ebay\Promotion\Channel\Discount(
                        $discountData['id'],
                        $discountData['title'],
                    );
                }
            }

            $channelPromotion = new \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion(
                $promotion['id'],
                $promotion['name'],
                $promotion['type'],
                $promotion['status'],
                $promotion['priority'],
                $this->createDateTime($promotion['start_date']),
                $this->createDateTime($promotion['end_date']),
                $discounts,
                $promotion['listing_ids']
            );

            $this->responseData[] = $channelPromotion;
        }
    }

    private function createDateTime(?string $value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($value);
    }
}
