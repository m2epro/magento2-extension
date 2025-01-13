<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    private \Ess\M2ePro\Model\Ebay\Account\MagentoOrderCreateService $magentoOrderCreateService;
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Model\Ebay\Account\Update $accountUpdate;
    private \Ess\M2ePro\Helper\Url $urlHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\MagentoOrderCreateService $magentoOrderCreateService,
        \Ess\M2ePro\Model\Ebay\Account\Update $accountUpdate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Url $urlHelper,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->magentoOrderCreateService = $magentoOrderCreateService;
        $this->accountUpdate = $accountUpdate;
        $this->helperException = $helperException;
        $this->urlHelper = $urlHelper;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = (int)$this->getRequest()->getParam('id');
        $data = $post->toArray();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObjectLoaded('Account', $id);
        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();

        $previousMagentoOrdersSettings = $this->getPreviousMagentoOrdersSettings($ebayAccount);

        try {
            $account = $this->updateSettings($account, $data);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);

            $message = __(
                'The Ebay access obtaining is currently unavailable.<br/>Reason: %1',
                $exception->getMessage()
            );

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $message,
                ]);

                return $this->getResult();
            }

            $this->messageManager->addErrorMessage($message);

            return $this->_redirect('*/ebay_account');
        }

        try {
            $this->createMagentoOrders($ebayAccount, $previousMagentoOrdersSettings);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccessMessage(__('Account was saved'));

        return $this->_redirect(
            $this->urlHelper->getBackUrl(
                'list',
                [],
                [
                    'edit' => [
                        'id' => $account->getId(),
                        '_current' => true,
                    ],
                ]
            )
        );
    }

    private function updateSettings(\Ess\M2ePro\Model\Account $account, array $data): \Ess\M2ePro\Model\Account
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

        return $this->accountUpdate->updateSettings($account, $data);
    }

    private function getPreviousMagentoOrdersSettings(\Ess\M2ePro\Model\Ebay\Account $ebayAccount): array
    {
        return [
            'listing' => [
                'is_enabled' => $ebayAccount->isMagentoOrdersListingsModeEnabled(),
                'create_from_date' => $ebayAccount->getMagentoOrdersListingsCreateFromDate(),
            ],
            'listing_other' => [
                'is_enabled' => $ebayAccount->isMagentoOrdersListingsOtherModeEnabled(),
                'create_from_date' => $ebayAccount->getMagentoOrdersListingsOtherCreateFromDate(),
            ],
        ];
    }

    private function createMagentoOrders(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        array $previousMagentoOrdersSettings
    ): void {
        if ($this->isNeedCreateMagentoOrdersListing($ebayAccount, $previousMagentoOrdersSettings)) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsByFromDate(
                (int)$ebayAccount->getId(),
                $ebayAccount->getMagentoOrdersListingsCreateFromDate()
            );
        }

        if ($this->isNeedCreateMagentoOrdersListingOther($ebayAccount, $previousMagentoOrdersSettings)) {
            $this->magentoOrderCreateService->createMagentoOrdersListingsOtherByFromDate(
                (int)$ebayAccount->getId(),
                $ebayAccount->getMagentoOrdersListingsOtherCreateFromDate()
            );
        }
    }

    private function isNeedCreateMagentoOrdersListing(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        array $previousMagentoOrdersSettings
    ): bool {
        if (!$ebayAccount->isMagentoOrdersListingsModeEnabled()) {
            return false;
        }

        if (!$ebayAccount->getMagentoOrdersListingsCreateFromDate()) {
            return false;
        }

        if (
            $previousMagentoOrdersSettings['listing']['is_enabled'] === false
            || $previousMagentoOrdersSettings['listing']['create_from_date'] === null
        ) {
            return true;
        }

        return $ebayAccount->getMagentoOrdersListingsCreateFromDate()->format('Y-m-d H:i')
            !== $previousMagentoOrdersSettings['listing']['create_from_date']->format('Y-m-d H:i');
    }

    private function isNeedCreateMagentoOrdersListingOther(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        array $previousMagentoOrdersSettings
    ): bool {
        if (!$ebayAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            return false;
        }

        if (!$ebayAccount->getMagentoOrdersListingsOtherCreateFromDate()) {
            return false;
        }

        if (
            $previousMagentoOrdersSettings['listing_other']['is_enabled'] === false
            || $previousMagentoOrdersSettings['listing_other']['create_from_date'] === null
        ) {
            return true;
        }

        return $ebayAccount->getMagentoOrdersListingsOtherCreateFromDate()->format('Y-m-d H:i')
            !== $previousMagentoOrdersSettings['listing_other']['create_from_date']->format('Y-m-d H:i');
    }
}
