<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class SynchronizePromotedListingCampaigns extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/synchronize_promoted_listing_campaigns';
    private \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper;
    private \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\RefreshCampaigns $refreshCampaigns;
    private \Ess\M2ePro\Model\Registry\Manager $registryManager;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository,
        \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\RefreshCampaigns $refreshCampaigns,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
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
        $this->ebayAccountRepository = $ebayAccountRepository;
        $this->refreshCampaigns = $refreshCampaigns;
        $this->registryManager = $registryManager;
    }

    /** @var int (in seconds) */
    protected $interval = 15 * 60;

    public function isPossibleToRun(): bool
    {
        if ($this->serverMaintenanceHelper->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions(): void
    {
        $accounts = $this->ebayAccountRepository->getAll();
        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            if (!$this->isNeedRefresh($account)) {
                $this->getOperationHistory()->addText(
                    sprintf(
                        'No need refresh promoted listing campaigns for account "%s"',
                        $account->getTitle(),
                    )
                );

                continue;
            }

            $this->getOperationHistory()->addText(
                sprintf(
                    'Starting refresh promoted listing campaigns for account "%s"',
                    $account->getTitle(),
                )
            );

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                sprintf(
                    'Process refresh promoted listing campaigns for account %s.',
                    $account->getTitle(),
                )
            );

            try {
                $this->refreshCampaigns
                    ->execute($account->getChildObject(), null);
            } catch (\Throwable $exception) {
                $message = (string)__(
                    'The "SynchronizePromotedListingCampaigns" Action for eBay Account "%account" ' .
                    'was completed with error.',
                    [
                        'account' => $account->getTitle(),
                    ]
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(
                __METHOD__ . 'process' . $account->getId()
            );

            $this->updateLastSynchronizeDate($account);
        }
    }

    private function isNeedRefresh(\Ess\M2ePro\Model\Account $account): bool
    {
        $lastSynchronize = $this->registryManager->getValue(
            $this->makeRegistryKey($account),
        );

        if (empty($lastSynchronize)) {
            return true;
        }

        $synchronizeDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastSynchronize)->modify('+1 day');
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        return $currentDate >= $synchronizeDate;
    }

    private function updateLastSynchronizeDate(\Ess\M2ePro\Model\Account $account): void
    {
        $this->registryManager->setValue(
            $this->makeRegistryKey($account),
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }

    private function makeRegistryKey(\Ess\M2ePro\Model\Account $account): string
    {
        return sprintf(
            '/ebay/synchronize_promoted_listing_campaigns/account/%s/last_synchronize',
            $account->getId()
        );
    }
}
