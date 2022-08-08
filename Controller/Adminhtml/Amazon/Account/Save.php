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
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder */
    private $repricingSnapshotBuilder;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff */
    private $repricingSnapshotDiff;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder */
    private $repricingBuilder;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts */
    private $repricingAffectedListingsProducts;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor */
    private $repricingChangeProcessor;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $repricingSnapshotDiff
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder $repricingBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $repricingSnapshotBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $repricingAffectedListingsProducts
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $repricingChangeProcessor
     * @param \Ess\M2ePro\Helper\Module\Wizard $helperWizard
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Server\Create $serverAccountCreate,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $repricingSnapshotDiff,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder $repricingBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $repricingSnapshotBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $repricingAffectedListingsProducts,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $repricingChangeProcessor,
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
        $this->repricingSnapshotBuilder = $repricingSnapshotBuilder;
        $this->repricingSnapshotDiff = $repricingSnapshotDiff;
        $this->repricingBuilder = $repricingBuilder;
        $this->repricingAffectedListingsProducts = $repricingAffectedListingsProducts;
        $this->repricingChangeProcessor = $repricingChangeProcessor;
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

        // Repricing
        // ---------------------------------------
        if (!empty($post['repricing']) && $account->getChildObject()->isRepricing()) {
            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $repricingModel */
            $repricingModel = $account->getChildObject()->getRepricing();

            $this->repricingSnapshotBuilder->setModel($repricingModel);

            $repricingOldData = $this->repricingSnapshotBuilder->getSnapshot();

            $this->repricingBuilder->build($repricingModel, $post['repricing']);

            $this->repricingSnapshotBuilder->setModel($repricingModel);

            $repricingNewData = $this->repricingSnapshotBuilder->getSnapshot();

            $this->repricingSnapshotDiff->setOldSnapshot($repricingOldData);
            $this->repricingSnapshotDiff->setNewSnapshot($repricingNewData);

            $this->repricingAffectedListingsProducts->setModel($repricingModel);

            $this->repricingChangeProcessor->process(
                $this->repricingSnapshotDiff,
                $this->repricingAffectedListingsProducts->getObjectsData(['id', 'status'])
            );
        }
        // ---------------------------------------

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
