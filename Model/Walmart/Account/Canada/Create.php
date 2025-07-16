<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account\Canada;

use Ess\M2ePro\Model\ResourceModel\Walmart\Account as AccountResource;

class Create
{
    private \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository;
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
        $this->connectorDispatcher = $connectorDispatcher;
    }
    public function createAccount(
        int $marketplaceId,
        string $consumerId,
        string $privateKey,
        string $title
    ): \Ess\M2ePro\Model\Account {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObject('Account');
        $data = $this->accountBuilder->getDefaultData();

        $data[AccountResource::COLUMN_MARKETPLACE_ID] = $marketplaceId;
        $data[\Ess\M2ePro\Model\ResourceModel\Account::COLUMN_TITLE] = $title;

        $this->accountBuilder->build($account, $data);

        try {
            $responseData = $this->createOnServer(
                $consumerId,
                $privateKey,
                $marketplaceId,
                $account
            );

            $existAccount = $this->findExistAccountByIdentifier($responseData['identifier']);
            if ($existAccount !== null) {
                throw new \Ess\M2ePro\Model\Exception(
                    'An account with the same details has already been added. Please make sure you provide unique information.',
                );
            }

            $account->getChildObject()->addData(
                [
                    AccountResource::COLUMN_SERVER_HASH => $responseData['hash'],
                    AccountResource::COLUMN_IDENTIFIER => $responseData['identifier'],
                    AccountResource::COLUMN_INFO => \Ess\M2ePro\Helper\Json::encode($responseData['info'] ?? []),
                ]
            );

            $account->getChildObject()->save();
        } catch (\Throwable $exception) {
            $account->delete();

            throw $exception;
        }

        return $account;
    }

    private function createOnServer(
        string $consumerId,
        string $privateKey,
        int $marketplaceId,
        \Ess\M2ePro\Model\Account $account
    ): array {
        $params = [];
        $params['consumer_id'] = $consumerId;
        $params['private_key'] = $privateKey;
        $params['marketplace_id'] = $marketplaceId;

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
