<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class ComplianceDocumentsUpload extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/complianceDocumentsUpload';

    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\PendingStatusProcessor $pendingStatusProcessor;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\PendingStatusProcessor $pendingStatusProcessor
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->pendingStatusProcessor = $pendingStatusProcessor;
    }

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();

        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process Account ' . $account->getTitle()
            );

            try {
                $this->pendingStatusProcessor->process($account);
            } catch (\Throwable $exception) {
                $message = (string)__(
                    'The "Upload Compliance Documents" Action for eBay Account "%title" was completed with error.',
                    ['title' => $account->getTitle()]
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    protected function getPermittedAccounts(): array
    {
        $accountsCollection = $this->accountCollectionFactory->createWithEbayChildMode();

        return $accountsCollection->getItems();
    }
}
