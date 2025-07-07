<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

class AfterGetToken extends InstallationWalmart
{
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;
    private \Ess\M2ePro\Helper\Magento\Store $storeHelper;
    private \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Create $accountCreate;
    private \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Magento\Store $storeHelper,
        \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Create $accountCreate,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->exceptionHelper = $exceptionHelper;
        $this->storeHelper = $storeHelper;
        $this->accountCreate = $accountCreate;
        $this->accountBuilder = $accountBuilder;
        $this->marketplaceRepository = $marketplaceRepository;
    }

    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $authCode = $this->getRequest()->getParam('code');
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $sellerId = $this->getRequest()->getParam('sellerId');
        /** @var string|null $clientId */
        $clientId = $this->getRequest()->getParam('clientId');

        if (!$authCode) {
            $this->messageManager->addError(__('Auth Code is not defined'));

            return $this->_redirect('*/*/installation');
        }

        try {
            $account = $this->accountCreate->createAccount($authCode, $marketplaceId, $sellerId, $clientId);
            $this->accountBuilder->build($account, $this->getAccountDefaultStoreId());

            $marketplace = $this->marketplaceRepository->get($marketplaceId);
            $marketplace->enable();
            $this->marketplaceRepository->save($marketplace);

            $this->setStep($this->getNextStep());
        } catch (\Throwable $throwable) {
            $this->exceptionHelper->process($throwable);
            $this->messageManager->addError(__('Account Add Entity failed.'));
        }

        return $this->_redirect('*/*/installation');
    }

    private function getAccountDefaultStoreId(): array
    {
        $data['magento_orders_settings']['listing_other']['store_id'] = $this->storeHelper->getDefaultStoreId();

        return $data;
    }
}
