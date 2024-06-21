<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\FbaInventory as FbaInventoryForm;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $helperWizard;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\CreateService */
    private $accountMerchantSettingsCreateService;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\CreateService $accountMerchantSettingsCreateService,
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->magentoHelper = $magentoHelper;
        $this->accountBuilder = $accountBuilder;
        $this->helperWizard = $helperWizard;
        $this->helperData = $helperData;
        $this->exceptionHelper = $exceptionHelper;
        $this->supportHelper = $supportHelper;
        $this->accountMerchantSettingsCreateService = $accountMerchantSettingsCreateService;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = (int)$this->getRequest()->getParam('id');
        $formData = $post->toArray();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id);

        if (empty($id) || !$account->getId()) {
            $this->messageManager->addErrorMessage(__('Account does not exists.'));

            return $this->_redirect('*/*/index');
        }

        try {
            $this->updateAccount($account, $formData);
        } catch (\Throwable $e) {
            $this->exceptionHelper->process($e);
            $this->messageManager->addErrorMessage(
                __(
                    'Unable to save configuration changes. If the issue persists,'
                    . ' please contact our support team at %supportEmail for further assistance.',
                    ['supportEmail' => $this->supportHelper->getContactEmail()]
                )
            );

            return $this->_redirect('*/*/index');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess(__('Account was saved'));

        $routerParams = ['id' => $account->getId(), '_current' => true];
        if (
            $this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) &&
            $this->helperWizard->getStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) === 'account'
        ) {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->helperData->getBackUrl('list', [], ['edit' => $routerParams]));
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function updateAccount(\Ess\M2ePro\Model\Account $account, array $data): void
    {
        $this->accountBuilder->build($account, $data);

        if ($this->magentoHelper->isMSISupportingVersion()) {
            $this->accountMerchantSettingsCreateService->update(
                $account->getChildObject(),
                (bool)$data[FbaInventoryForm::FORM_KEY_FBA_INVENTORY_MODE],
                $data[FbaInventoryForm::FORM_KEY_FBA_INVENTORY_SOURCE_NAME] ?? null
            );
        } else {
            $this->accountMerchantSettingsCreateService->update(
                $account->getChildObject(),
                false
            );
        }
    }
}
