<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account\UnitedStates;

use Ess\M2ePro\Model\ResourceModel\Walmart\Account as AccountResource;

class Create
{
    private \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository;
    private \Ess\M2ePro\Model\Walmart\Account\AuthCodeSessionStorage $authCodeSessionStorage;
    private \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory;
    private \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository,
        \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Walmart\Account\AuthCodeSessionStorage $authCodeSessionStorage,
        \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->accountRepository = $accountRepository;
        $this->accountBuilder = $accountBuilder;
        $this->authCodeSessionStorage = $authCodeSessionStorage;
        $this->connectorDispatcher = $connectorDispatcher;
    }

    public function createAccount(
        string $authCode,
        int $marketplaceId,
        string $sellerId,
        ?string $clientId
    ): \Ess\M2ePro\Model\Account {
        $accountId = $this->authCodeSessionStorage->getAccountId($authCode);
        if ($accountId !== null) {
            $account = $this->accountRepository->find($accountId);
            if ($account !== null) {
                return $account;
            }
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObject('Account');

        $responseData = $this->createOnServer(
            $authCode,
            $marketplaceId,
            $sellerId,
            $clientId,
            $account
        );

        $existAccount = $this->findExistAccountByIdentifier($responseData['identifier']);
        if ($existAccount !== null) {
            throw new \Ess\M2ePro\Model\Exception(
                'An account with the same details has already been added. Please make sure you provide unique information.',
            );
        }

        $data = $this->accountBuilder->getDefaultData();
        $data[AccountResource::COLUMN_MARKETPLACE_ID] = $marketplaceId;
        $data[\Ess\M2ePro\Model\ResourceModel\Account::COLUMN_TITLE] = $responseData['identifier'];

        $this->accountBuilder->build($account, $data);

        $account->getChildObject()->addData(
            [
                AccountResource::COLUMN_SERVER_HASH => $responseData['hash'],
                AccountResource::COLUMN_IDENTIFIER => $responseData['identifier'],
            ]
        );

        $account->getChildObject()->save();

        $this->authCodeSessionStorage->setAccountId($authCode, (int)$account->getId());

        return $account;
    }

    // ----------------------------------------

    private function createOnServer(
        string $authCode,
        int $marketplaceId,
        string $sellerId,
        ?string $clientId,
        \Ess\M2ePro\Model\Account $account
    ): array {
        $params = [];
        $params['marketplace_id'] = $marketplaceId;
        $params['seller_id'] = $sellerId;
        $params['auth_code'] = $authCode;
        $params['client_id'] = $clientId;

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Add\EntityRequester $connectorObj */
        $connectorObj = $this->connectorDispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Walmart\Connector\Account\Add\EntityRequester::class,
            $params,
            $account
        );
        $this->connectorDispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
    private function findExistAccountByIdentifier(string $identifier): ?\Ess\M2ePro\Model\Account
    {
        return $this->accountRepository->findByIdentifier($identifier);
    }
}
