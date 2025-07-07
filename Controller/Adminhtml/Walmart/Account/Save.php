<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Save extends Account
{
    private \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder;
    private \Ess\M2ePro\Model\Walmart\Account\MagentoOrderCreateService $magentoOrderCreateService;
    private \Ess\M2ePro\Helper\Module\Wizard $helperWizard;
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Helper\Url $urlHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Walmart\Account\MagentoOrderCreateService $magentoOrderCreateService,
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
            $this->saveSettings((int)$id, $data);
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
            'id' => $id,
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

    private function saveSettings(int $id, array $data): \Ess\M2ePro\Model\Account
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObjectLoaded('Account', $id);
        /** @var \Ess\M2ePro\Model\Walmart\Account $account */
        $walmartAccount = $account->getChildObject();

        $previousMagentoOrdersSettings = $this->getPreviousMagentoOrdersSettings($walmartAccount);

        $this->saveAccount($account, $data);

        try {
            $this->createMagentoOrders($walmartAccount, $previousMagentoOrdersSettings);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);
        }

        return $account;
    }

    private function saveAccount(\Ess\M2ePro\Model\Account $account, array $data): void
    {
        if (!empty($data['magento_orders_settings']['listing']['create_from_date'])) {
            $data['magento_orders_settings']['listing']['create_from_date'] =
                \Ess\M2ePro\Helper\Date::createDateInCurrentZone(
                    $data['magento_orders_settings']['listing']['create_from_date']
                );
        }

        if (!empty($data['magento_orders_settings']['listing_other']['create_from_date'])) {
            $data['magento_orders_settings']['listing_other']['create_from_date'] =
                \Ess\M2ePro\Helper\Date::createDateInCurrentZone(
                    $data['magento_orders_settings']['listing_other']['create_from_date']
                );
        }

        $this->accountBuilder->build($account, $data);
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
            $this->isNeedCreateMagentoOrdersListing(
                $walmartAccount,
                $previousMagentoOrdersSettings
            )
        ) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsByFromDate(
                (int)$walmartAccount->getId(),
                $walmartAccount->getMagentoOrdersListingsCreateFromDate()
            );
        }

        if (
            $this->isNeedCreateMagentoOrdersListingOther(
                $walmartAccount,
                $previousMagentoOrdersSettings
            )
        ) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsOtherByFromDate(
                (int)$walmartAccount->getId(),
                $walmartAccount->getMagentoOrdersListingsOtherCreateFromDate()
            );
        }
    }

    private function isNeedCreateMagentoOrdersListing(
        \Ess\M2ePro\Model\Walmart\Account $walmartAccount,
        array $previousMagentoOrdersSettings
    ): bool {
        if (!$walmartAccount->isMagentoOrdersListingsModeEnabled()) {
            return false;
        }

        if (!$walmartAccount->getMagentoOrdersListingsCreateFromDate()) {
            return false;
        }

        if (
            $previousMagentoOrdersSettings['listing']['is_enabled'] === false
            || $previousMagentoOrdersSettings['listing']['create_from_date'] === null
        ) {
            return true;
        }

        return $walmartAccount->getMagentoOrdersListingsCreateFromDate()->format('Y-m-d H:i')
            !== $previousMagentoOrdersSettings['listing']['create_from_date']->format('Y-m-d H:i');
    }

    private function isNeedCreateMagentoOrdersListingOther(
        \Ess\M2ePro\Model\Walmart\Account $walmartAccount,
        array $previousMagentoOrdersSettings
    ): bool {
        if (!$walmartAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            return false;
        }

        if (!$walmartAccount->getMagentoOrdersListingsOtherCreateFromDate()) {
            return false;
        }

        if (
            $previousMagentoOrdersSettings['listing_other']['is_enabled'] === false
            || $previousMagentoOrdersSettings['listing_other']['create_from_date'] === null
        ) {
            return true;
        }

        return $walmartAccount->getMagentoOrdersListingsOtherCreateFromDate()->format('Y-m-d H:i')
            !== $previousMagentoOrdersSettings['listing_other']['create_from_date']->format('Y-m-d H:i');
    }
}
