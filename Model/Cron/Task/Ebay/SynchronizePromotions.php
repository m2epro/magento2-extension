<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class SynchronizePromotions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/synchronize_promotions';

    /** @var int (in seconds) */
    protected $interval = 1800;

    private \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper;
    private \Ess\M2ePro\Model\Ebay\Promotion\Synchronization $promotionsSynchronization;
    private \Ess\M2ePro\Model\Ebay\Promotion\TimingManager $timingManager;
    private \Ess\M2ePro\Model\Ebay\Marketplace\Repository $marketplaceRepository;
    private \Ess\M2ePro\Model\Ebay\Account\Repository $accountRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Model\Ebay\Promotion\Synchronization $promotionsSynchronization,
        \Ess\M2ePro\Model\Ebay\Promotion\TimingManager $timingManager,
        \Ess\M2ePro\Model\Ebay\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Model\Ebay\Account\Repository $accountRepository,
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

        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
        $this->promotionsSynchronization = $promotionsSynchronization;
        $this->timingManager = $timingManager;
        $this->marketplaceRepository = $marketplaceRepository;
        $this->accountRepository = $accountRepository;
    }

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions(): void
    {
        $accounts = $this->accountRepository->getAll();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            $marketplaces = $this->marketplaceRepository->findMarketplacesWithExistListing($account);

            if (empty($marketplaces)) {
                continue;
            }

            foreach ($marketplaces as $marketplace) {
                $this->getOperationHistory()->addText(
                    'Starting account "' . $account->getTitle() . '" and marketplace "' . $marketplace->getTitle()
                );

                if (!$this->timingManager->isAttemptIntervalExceeded($account->getId(), $marketplace->getId())) {
                    continue;
                }

                $this->getOperationHistory()->addTimePoint(
                    __METHOD__ . 'process' . $account->getId() . $marketplace->getId(),
                    'Process account ' . $account->getTitle() . ' and marketplace ' . $marketplace->getTitle()
                );

                try {
                    $this->promotionsSynchronization->process($account->getChildObject(), $marketplace);

                    $this->timingManager->setLastProcessed($account->getId(), $marketplace->getId());
                } catch (\Throwable $exception) {
                    $message = (string)__(
                        'The "SynchronizePromotion" Action for eBay Account "%account" and Marketplace "%marketplace"'
                        . ' was completed with error.',
                        [
                            'account' => $account->getTitle(),
                            'marketplace' => $marketplace->getTitle(),
                        ]
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getOperationHistory()->saveTimePoint(
                    __METHOD__ . 'process' . $account->getId() . $marketplace->getId()
                );
            }
        }
    }
}
