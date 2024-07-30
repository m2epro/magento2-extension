<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class VideoUpload extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/videoUpload';

    /** @var int (in seconds) */
    protected $interval = 600;
    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;
    private \Ess\M2ePro\Model\Ebay\Video\PendingStatusProcessor $pendingStatusProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\PendingStatusProcessor $pendingStatusProcessor,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
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

    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();
        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        return $synchronizationLog;
    }

    protected function performActions(): void
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
                    'The "Upload Video" Action for eBay Account "%title" was completed with error.',
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
