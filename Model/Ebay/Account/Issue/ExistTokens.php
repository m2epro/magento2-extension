<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use Ess\M2ePro\Model\Issue\DataObject as Issue;

class ExistTokens implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    private const ACCOUNT_TOKENS_CACHE_KEY = 'ebay_account_exist_token_issues';

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayComponentHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $collectionFactory;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $collectionFactory,
        \Magento\Rule\Model\Condition\Context $context,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper
    ) {
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->collectionFactory = $collectionFactory;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->helperData = $helperData;
        $this->issueFactory = $issueFactory;
        $this->ebayComponentHelper = $ebayComponentHelper;
    }

    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $accounts = $this->getAccountsWithoutToken();
        if (empty($accounts)) {
            return [];
        }

        $issues = $this->getCachedIssues();

        if ($issues !== null) {
            return $issues;
        }

        $issues = [];

        foreach ($this->getAccountsWithoutToken() as $account) {
            if ($issue = $this->getExistTokenMessages($account)) {
                $issues[] = $issue;
            }
        }

        $this->setIssuesToCache($issues);

        return $issues;
    }

    private function isNeedProcess(): bool
    {
        return $this->ebayViewHelper->isInstallationWizardFinished() &&
            $this->ebayComponentHelper->isEnabled();
    }

    private function getAccountsWithoutToken(): array
    {
        $accounts = $this->collectionFactory->createWithEbayChildMode();
        $accounts->addFieldToFilter('is_token_exist', ['eq' => 0]);

        return $accounts->getItems();
    }

    private function getExistTokenMessages(\Ess\M2ePro\Model\Account $account): ?Issue
    {
        $tempMessage = (string)__(
            'Authorization for "%1" eBay account failed. Please go to eBay > Configuration >
            Accounts > "%1" eBay Account > General and click Get Token to renew it.',
            $this->helperData->escapeHtml($account->getTitle())
        );
        $title = (string)__(
            'Authorization failed',
            $this->helperData->escapeHtml($account->getTitle())
        );
        $issue = $this->issueFactory->createErrorDataObject($title, $tempMessage, null);

        return $issue;
    }

    private function setIssuesToCache(array $issues): void
    {
        $data = [];
        foreach ($issues as $issue) {
            $data[] = [
                'type' => $issue->getType(),
                'text' => $issue->getText(),
                'title' => $issue->getTitle(),
                'url' => $issue->getUrl(),
            ];
        }

        $this->permanentCacheHelper->setValue(
            self::ACCOUNT_TOKENS_CACHE_KEY,
            $data,
            ['account', 'ebay'],
            60 * 60 * 24
        );
    }

    private function getCachedIssues(): ?array
    {
        $data = $this->permanentCacheHelper->getValue(self::ACCOUNT_TOKENS_CACHE_KEY);

        if ($data === null) {
            return null;
        }

        $issues = [];

        foreach ($data as $issueData) {
            $issues[] = $this->issueFactory->create(
                $issueData['type'],
                $issueData['title'],
                $issueData['text'],
                $issueData['url']
            );
        }

        return $issues;
    }
}
