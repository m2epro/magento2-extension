<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Save extends Account
{
    private \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder;
    private \Ess\M2ePro\Model\Walmart\Account\MagentoOrderCreateService $magentoOrderCreateService;
    private \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher;
    private \Ess\M2ePro\Helper\Module\Wizard $helperWizard;
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Helper\Url $urlHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Walmart\Account\MagentoOrderCreateService $magentoOrderCreateService,
        \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher,
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Helper\Url $urlHelper,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->accountBuilder = $accountBuilder;
        $this->magentoOrderCreateService = $magentoOrderCreateService;
        $this->connectorDispatcher = $connectorDispatcher;
        $this->helperWizard = $helperWizard;
        $this->helperException = $helperException;
        $this->urlHelper = $urlHelper;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');
        $data = $post->toArray();

        try {
            $account = $id
                ? $this->updateAccount((int)$id, $data)
                : $this->addAccount($data);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);

            $message = __(
                'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message',
                ['error_message' => $exception->getMessage()]
            );

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $message,
                ]);

                return $this->getResult();
            }

            $this->messageManager->addErrorMessage($message);

            return $this->_redirect('*/walmart_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccessMessage(__('Account was saved'));

        $routerParams = [
            'id' => $account->getId(),
            '_current' => true,
        ];

        if (
            $this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK)
            && $this->helperWizard->getStep(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) == 'account'
        ) {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->urlHelper->getBackUrl('list', [], ['edit' => $routerParams]));
    }

    private function updateAccount(int $id, array $data): \Ess\M2ePro\Model\Account
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObjectLoaded('Account', $id);
        /** @var \Ess\M2ePro\Model\Walmart\Account $account */
        $walmartAccount = $account->getChildObject();

        $oldData = array_merge($account->getOrigData(), $walmartAccount->getOrigData());

        $previousMagentoOrdersSettings = $this->getPreviousMagentoOrdersSettings($walmartAccount);

        $this->saveAccount($account, $data);

        try {
            $this->updateAccountOnServer($account, $data, $oldData);
        } catch (\Throwable $exception) {
            $this->accountBuilder->build($account, $oldData);

            throw $exception;
        }

        try {
            $this->createMagentoOrders($walmartAccount, $previousMagentoOrdersSettings);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);
        }

        return $account;
    }

    private function saveAccount(\Ess\M2ePro\Model\Account $account, array $data): void
    {
        $data['magento_orders_settings']['listing']['create_from_date'] = new \DateTime(
            $data['magento_orders_settings']['listing']['create_from_date'],
            \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order::getDateTimeZone()
        );

        $data['magento_orders_settings']['listing_other']['create_from_date'] = new \DateTime(
            $data['magento_orders_settings']['listing_other']['create_from_date'],
            \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order::getDateTimeZone()
        );

        $this->accountBuilder->build($account, $data);
    }

    private function updateAccountOnServer(\Ess\M2ePro\Model\Account $account, array $data, array $oldData): void
    {
        $params = $this->getDataForServer($data);

        if (!$this->isNeedSendDataToServer($params, $oldData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Update\EntityRequester $connectorObj */
        $connectorObj =  $this->connectorDispatcher->getConnector(
            'account',
            'update',
            'entityRequester',
            $params,
            $account
        );
        $this->connectorDispatcher->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $account->getChildObject()->addData(
            [
                'info' => \Ess\M2ePro\Helper\Json::encode($responseData['info']),
            ]
        );
        $account->getChildObject()->save();
    }

    private function isNeedSendDataToServer($newData, $oldData): bool
    {
        return !empty(array_diff_assoc($newData, $oldData));
    }

    private function getDataForServer(array $data): array
    {
        $params = [
            'marketplace_id' => (int)$data['marketplace_id'],
        ];

        if ($data['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
            $params['client_id'] = $data['client_id'];
            $params['client_secret'] = $data['client_secret'];
        } else {
            $params['consumer_id'] = $data['consumer_id'];
            $params['private_key'] = $data['private_key'];
        }

        return $params;
    }

    private function getPreviousMagentoOrdersSettings(\Ess\M2ePro\Model\Walmart\Account $walmartAccount): array
    {
        return [
            'listing' => [
                'is_enabled' => $walmartAccount->isMagentoOrdersListingsModeEnabled(),
                'create_from_date' => $walmartAccount->getMagentoOrdersListingsCreateFromDate(),
            ],
            'listing_other' => [
                'is_enabled' => $walmartAccount->isMagentoOrdersListingsOtherModeEnabled(),
                'create_from_date' => $walmartAccount->getMagentoOrdersListingsOtherCreateFromDate(),
            ],
        ];
    }

    private function createMagentoOrders(
        \Ess\M2ePro\Model\Walmart\Account $walmartAccount,
        array $previousMagentoOrdersSettings
    ): void {
        if (
            $walmartAccount->isMagentoOrdersListingsModeEnabled()
            && (
                $previousMagentoOrdersSettings['listing']['is_enabled'] === false
                || $walmartAccount->getMagentoOrdersListingsCreateFromDate()->format('Y-m-d H:i:s')
                !== $previousMagentoOrdersSettings['listing']['create_from_date']->format('Y-m-d H:i:s')
            )
        ) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsByFromDate(
                (int)$walmartAccount->getId(),
                $walmartAccount->getMagentoOrdersListingsCreateFromDate()
            );
        }

        if (
            $walmartAccount->isMagentoOrdersListingsOtherModeEnabled()
            && (
                $previousMagentoOrdersSettings['listing_other']['is_enabled'] === false
                || $walmartAccount->getMagentoOrdersListingsOtherCreateFromDate()->format('Y-m-d H:i:s')
                !== $previousMagentoOrdersSettings['listing_other']['create_from_date']->format('Y-m-d H:i:s')
            )
        ) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsOtherByFromDate(
                (int)$walmartAccount->getId(),
                $walmartAccount->getMagentoOrdersListingsOtherCreateFromDate()
            );
        }
    }

    private function addAccount(array $data): \Ess\M2ePro\Model\Account
    {
        $searchField = empty($data['client_id']) ? 'consumer_id' : 'client_id';
        $searchValue = empty($data['client_id']) ? $data['consumer_id'] : $data['client_id'];

        if ($this->isAccountExists($searchField, $searchValue)) {
            throw new \Ess\M2ePro\Model\Exception(
                'An account with the same Walmart Client ID already exists.'
            );
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObject('Account');

        $this->accountBuilder->build($account, $data);

        try {
            $params = $this->getDataForServer($data);

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Add\EntityRequester $connectorObj */
            $connectorObj = $this->connectorDispatcher->getConnector(
                'account',
                'add',
                'entityRequester',
                $params,
                $account
            );
            $this->connectorDispatcher->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            $account->getChildObject()->addData(
                [
                    'server_hash' => $responseData['hash'],
                    'info' => \Ess\M2ePro\Helper\Json::encode($responseData['info']),
                ]
            );
            $account->getChildObject()->save();
        } catch (\Throwable $exception) {
            $account->delete();

            throw $exception;
        }

        return $account;
    }

    private function isAccountExists($search, $value): bool
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->walmartFactory->getObject('Account')->getCollection()
                                           ->addFieldToFilter($search, $value);

        return (bool)$collection->getSize();
    }
}
