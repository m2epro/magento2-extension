<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Save extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $helperWizard;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Create */
    private $serverAccountCreate;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $accountBuilder;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate
     * @param \Ess\M2ePro\Helper\Module\Wizard $helperWizard
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate,
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperWizard = $helperWizard;
        $this->helperException = $helperException;
        $this->helperData = $helperData;
        $this->serverAccountCreate = $serverAccountCreate;
        $this->accountBuilder = $accountBuilder;
    }

    // ----------------------------------------

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');
        $data = $post->toArray();

        // new account
        if (empty($id)) {
            if ($this->isAccountExists($data['merchant_id'], (int)$data['marketplace_id'])) {
                $this->messageManager->addError(
                    $this->__(
                        'An account with the same Amazon Merchant ID and Marketplace already exists.'
                    )
                );

                return $this->_redirect('*/amazon_account');
            }

            try {
                $result = $this->serverAccountCreate->process(
                    $data['token'],
                    $data['merchant_id'],
                    $data['marketplace_id']
                );
            } catch (\Exception $exception) {
                $this->helperException->process($exception);

                $message = $this->__(
                    'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                    $exception->getMessage()
                );

                if ($this->isAjax()) {
                    $this->setJsonContent([
                        'success' => false,
                        'message' => $message,
                    ]);

                    return $this->getResult();
                }

                $this->messageManager->addError($message);

                return $this->_redirect('*/amazon_account');
            }

            $account = $this->createAccount($data, $result);
        } else {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $id);

            $this->updateAccount($account, $data);
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was saved'));

        $routerParams = ['id' => $account->getId(), '_current' => true];
        if (
            $this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) &&
            $this->helperWizard->getStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) === 'account'
        ) {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->helperData->getBackUrl('list', [], ['edit' => $routerParams]));
    }

    // ----------------------------------------

    /**
     * @param string $merchantId
     * @param int $marketplaceId
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function isAccountExists(string $merchantId, int $marketplaceId): bool
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->amazonFactory->getObject('Account')->getCollection()
                                          ->addFieldToFilter('merchant_id', $merchantId)
                                          ->addFieldToFilter('marketplace_id', $marketplaceId);

        return (bool)$collection->getSize();
    }

    /**
     * @param array $data
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result $serverResult
     *
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createAccount(
        array $data,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result $serverResult
    ): \Ess\M2ePro\Model\Account {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObject('Account');

        $this->accountBuilder->build(
            $account,
            $data + ['server_hash' => $serverResult->getHash(), 'info' => $serverResult->getInfo()]
        );

        return $account;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function updateAccount(\Ess\M2ePro\Model\Account $account, array $data): void
    {
        $this->accountBuilder->build($account, $data);
    }
}
