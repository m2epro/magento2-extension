<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Account;

use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

class Create
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update */
    private $storeCategoryUpdate;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Account\BuilderFactory */
    private $builderFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $dispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\Ebay\Account\BuilderFactory $builderFactory,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
    ) {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->builderFactory = $builderFactory;
        $this->ebayFactory = $ebayFactory;
        $this->storeCategoryUpdate = $storeCategoryUpdate;
    }

    public function create(string $authCode, int $mode): \Ess\M2ePro\Model\Account
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObject('Account');

        $responseData = $this->createOnServer($authCode, $mode);

        $builder = $this->builderFactory->create();
        $data = $builder->getDefaultData();

        $existsAccount = $this->isAccountExists((string)$responseData['user_id'], (int)$account->getId());
        if (!empty($existsAccount)) {
            throw new \Ess\M2ePro\Model\Exception('An account with the same eBay User ID already exists.');
        }

        $data['mode'] = $mode;
        $data['is_token_exist'] = 1;
        $data['info'] = \Ess\M2ePro\Helper\Json::encode($responseData['info']);
        $data['sell_api_token_expired_date'] = $responseData['token_expired_date'];
        $data['server_hash'] = $responseData['hash'];
        $data['user_id'] = $responseData['user_id'];
        $data['title'] = $responseData['user_id'];

        $builder->build($account, $data);

        $this->storeCategoryUpdate->process($account->getChildObject());

        $account->getChildObject()->updateUserPreferences();

        return $account;
    }

    private function isAccountExists(string $userId, int $newAccountId): int
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->ebayFactory->getObject('Account')->getCollection()
                                        ->addFieldToSelect('title')
                                        ->addFieldToFilter('user_id', $userId)
                                        ->addFieldToFilter('id', ['neq' => $newAccountId]);

        return $collection->getSize();
    }

    private function createOnServer(string $authCode, int $mode): array
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Account\Add\Entity $connectorObj */
        $connectorObj = $this->dispatcherFactory
            ->create()
            ->getConnector(
                'account',
                'add',
                'entity',
                [
                    'mode' => $mode == EbayAccount::MODE_PRODUCTION ? 'production' : 'sandbox',
                    'auth_code' => $authCode,
                ]
            );

        $connectorObj->process();

        return $connectorObj->getResponseData();
    }
}
