<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account\UnitedStates;

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
        string $authCode,
        string $sellerId,
        int $accountId,
        ?string $clientId
    ): void {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->accountRepository->get($accountId);

        try {
            $responseData = $this->updateOnServer(
                $authCode,
                $sellerId,
                $clientId,
                $account
            );

            if (empty($account->getChildObject()->getIdentifier())) {
                $account->getChildObject()->addData(
                    [
                        AccountResource::COLUMN_IDENTIFIER => $responseData['identifier'],
                    ]
                );
            }
            $account->getChildObject()->save();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    // ----------------------------------------

    private function updateOnServer(
        string $authCode,
        string $sellerId,
        ?string $clientId,
        \Ess\M2ePro\Model\Account $account
    ): array {
        $params = [];
        $params['marketplace_id'] = $account->getChildObject()->getMarketplaceId();
        $params['seller_id'] = $sellerId;
        $params['auth_code'] = $authCode;
        $params['client_id'] = $clientId;

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
