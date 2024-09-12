<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Magento\Framework\App\ResponseInterface;

class AfterGetToken extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Update */
    private $accountServerUpdate;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Create */
    private $serverAccountCreate;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;
    /** @var \Ess\M2ePro\Model\AccountFactory */
    private $accountFactory;
    /** @var \Ess\M2ePro\Model\MarketplaceFactory */
    protected $marketplaceFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    protected $marketplaceResource;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\CreateService  */
    private $accountMerchantSettingsCreateService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\CreateService $accountMerchantSettingsCreateService,
        \Ess\M2ePro\Model\Amazon\Account\Server\Update $accountServerUpdate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate,
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceFactory = $marketplaceFactory;
        $this->helperException = $helperException;
        $this->accountServerUpdate = $accountServerUpdate;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->serverAccountCreate = $serverAccountCreate;
        $this->accountBuilder = $accountBuilder;
        $this->accountFactory = $accountFactory;
        $this->accountMerchantSettingsCreateService = $accountMerchantSettingsCreateService;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $marketplaceId = (int)$params['marketplace_id'];
        $marketplace = $this->marketplaceFactory->create();
        $this->marketplaceResource->load($marketplace, $marketplaceId);

        if (empty($params)) {
            return $this->_redirect('*/*/new');
        }

        $incorrectInput = false;
        $requiredFields = [
            'selling_partner_id',
            'spapi_oauth_code',
            'marketplace_id'
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                $incorrectInput = true;
                break;
            }
        }

        if (!isset($params['title']) && !isset($params['account_id'])) {
            $incorrectInput = true;
        }

        if ($incorrectInput) {
            $error = $this->__('The Amazon token obtaining is currently unavailable.');
            $this->messageManager->addErrorMessage($error);

            return $this->_redirect('*/*/new');
        }

        if (isset($params['title'])) {
            $result =  $this->processNewAccount(
                (string)$params['selling_partner_id'],
                (string)$params['spapi_oauth_code'],
                rawurldecode((string)$params['title']),
                (int)$params['marketplace_id']
            );
            $marketplace->enable();
            $this->marketplaceResource->save($marketplace);
        } else {
            $result = $this->processExistingAccount(
                (int)$params['account_id'],
                (string)$params['spapi_oauth_code'],
                (string)$params['selling_partner_id']
            );
        }

        return $result;
    }

    private function processNewAccount(
        string $sellingPartnerId,
        string $spApiOAuthCode,
        string $title,
        int $marketplaceId
    ): ResponseInterface {
        if ($this->isAccountExists($sellingPartnerId, $marketplaceId)) {
            $this->messageManager->addErrorMessage(
                $this->__(
                    'An account with the same Amazon Merchant ID and Marketplace already exists.'
                )
            );

            return $this->_redirect('*/*/index');
        }

        try {
            $result = $this->serverAccountCreate->process(
                $spApiOAuthCode,
                $sellingPartnerId,
                $marketplaceId
            );
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $message = $this->__(
                'The Amazon access obtaining is currently unavailable. Reason: %error_message%',
                $exception->getMessage()
            );
            $this->messageManager->addErrorMessage($message);

            return $this->_redirect('*/*/index');
        }

        $account = $this->createAccount(
            $sellingPartnerId,
            $marketplaceId,
            $title,
            $result
        );

        $accountId = (int)$account->getId();

        if ($accountId) {
            $this->messageManager->addSuccessMessage($this->__('Account was saved'));

            return $this->_redirect('*/*/edit', [
                'id' => $accountId,
                'close_on_save' => $this->getRequest()->getParam('close_on_save'),
            ]);
        }

        $this->messageManager->addErrorMessage(
            $this->__(
                'The account creation is currently unavailable.'
            )
        );

        return $this->_redirect('*/*/index');
    }

    private function processExistingAccount(
        int $accountId,
        string $spApiOAuthCode,
        string $sellingPartnerId
    ): ResponseInterface {
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $accountId);
            $this->accountServerUpdate->process($account->getChildObject(), $spApiOAuthCode, $sellingPartnerId);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $this->messageManager->addError(
                $this->__(
                    'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                    $exception->getMessage()
                )
            );

            return $this->_redirect('*/*/index');
        }

        $this->messageManager->addSuccessMessage($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', [
            'id' => $accountId,
            'close_on_save' => $this->getRequest()->getParam('close_on_save'),
        ]);
    }

    /**
     * @param string $merchantId
     * @param int $marketplaceId
     *
     * @return bool
     */
    private function isAccountExists(string $merchantId, int $marketplaceId): bool
    {
        $collection = $this->accountCollectionFactory->createWithAmazonChildMode()
            ->addFieldToFilter('merchant_id', $merchantId)
            ->addFieldToFilter('marketplace_id', $marketplaceId);

        return (bool)$collection->getSize();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createAccount(
        string $merchantId,
        int $marketplaceId,
        string $title,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result $serverResult
    ): \Ess\M2ePro\Model\Account {
        $account = $this->accountFactory->create()
            ->setChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $accountData = array_merge(
            $this->accountBuilder->getDefaultData(),
            [
                'merchant_id' => $merchantId,
                'marketplace_id' => $marketplaceId,
                'title' => $title,
                'server_hash' => $serverResult->getHash(),
                'info' => $serverResult->getInfo(),
            ]
        );
        $accountData['magento_orders_settings']['tax']['excluded_states'] = implode(
            ',',
            $accountData['magento_orders_settings']['tax']['excluded_states']
        );
        $accountData['magento_orders_settings']['tax']['excluded_countries'] = implode(
            ',',
            $accountData['magento_orders_settings']['tax']['excluded_countries']
        );

        $this->accountBuilder->build($account, $accountData);

        $this->accountMerchantSettingsCreateService->createDefault($account->getChildObject());

        return $account;
    }
}
