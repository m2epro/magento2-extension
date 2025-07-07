<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account\Canada;

use Ess\M2ePro\Model\ResourceModel\Walmart\Account as AccountResource;

class Update
{
    private \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository;
    private \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository,
        \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher
    ) {
        $this->accountRepository = $accountRepository;
        $this->connectorDispatcher = $connectorDispatcher;
    }

    public function updateAccount(
        string $consumerId,
        string $privateKey,
        int $accountId
    ): void {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->accountRepository->get($accountId);

        try {
            $responseData = $this->updateOnServer($consumerId, $privateKey, $account);

            if (empty($account->getChildObject()->getIdentifier())) {
                $account->getChildObject()->addData(
                    [
                        AccountResource::COLUMN_IDENTIFIER => $responseData['identifier'],
                    ]
                );
            }

            $account->getChildObject()->addData(
                [
                    AccountResource::COLUMN_INFO => \Ess\M2ePro\Helper\Json::encode($responseData['info'] ?? []),
                ]
            );
            $account->getChildObject()->save();
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    private function updateOnServer(
        string $consumerId,
        string $privateKey,
        \Ess\M2ePro\Model\Account $account
    ): array {
        $params = [];
        $params['consumer_id'] = $consumerId;
        $params['private_key'] = $privateKey;
        $params['marketplace_id'] = $account->getChildObject()->getMarketplaceId();

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Update\EntityRequester $connectorObj */
        $connectorObj = $this->connectorDispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Walmart\Connector\Account\Update\EntityRequester::class,
            $params,
            $account
        );
        $this->connectorDispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
