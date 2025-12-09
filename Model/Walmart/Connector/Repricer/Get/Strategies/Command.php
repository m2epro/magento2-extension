<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies;

/**
 * @method StrategyEntity[] getResponseData()
 */
class Command extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->account->getChildObject()->getServerHash(),
        ];
    }

    protected function getCommand(): array
    {
        return ['repricer', 'get', 'strategies'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['entities']);
    }

    protected function prepareResponseData()
    {
        $result = [];
        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['entities'] as $entity) {
            $result[] = new StrategyEntity(
                $entity['strategy_name'],
                $entity['collection_id'],
                $entity['enabled'],
                $entity['assigned_count'],
                $entity['enable_for_promotion'],
                $entity['restore_seller_price_without_target'],
                $entity['enable_buybox_meet_external'],
                $entity['compare_with_third_party_offer_only'],
                $entity['strategies'],
            );
        }

        $this->responseData = $result;
    }
}
