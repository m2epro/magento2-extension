<?php

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use Ess\M2ePro\Helper\Date as DateHelper;
use Ess\M2ePro\Model\Ebay\Account;
use Ess\M2ePro\Model\Issue\DataObject as Issue;

class ExpiredTokens implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $_localeDate;
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;
    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayComponentHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $collectionFactory,
        \Magento\Rule\Model\Condition\Context $context,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_localeDate = $context->getLocaleDate();
        $this->ebayViewHelper = $ebayViewHelper;
        $this->helperData = $helperData;
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->issueFactory = $issueFactory;
        $this->translationHelper = $translationHelper;
        $this->ebayComponentHelper = $ebayComponentHelper;
    }

    /**
     * @inheritDoc
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Exception
     */
    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $issues = [];
        foreach ($this->getAccounts() as $account) {
            if ($issue = $this->getSellApiTokenMessages($account)) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return null|Issue
     * @throws \Exception
     */
    private function getSellApiTokenMessages(\Ess\M2ePro\Model\Account $account): ?Issue
    {
        $issue = $this->getCachedIssue($account);
        if ($issue !== null) {
            return $issue;
        }

        if (
            empty($account->getChildObject()->getSellApiTokenExpiredDate())
            || !$account->getChildObject()->isTokenExist()
        ) {
            return null;
        }
        $currentTimeStamp = DateHelper::createCurrentGmt()->getTimestamp();
        $dateInFutureOn10days = DateHelper::createCurrentGmt()->modify('+ 10 days');
        $tokenExpirationTimeStamp = DateHelper::createDateGmt(
            $account->getChildObject()->getSellApiTokenExpiredDate()
        )->getTimestamp();

        if ($tokenExpirationTimeStamp <= 0 || $tokenExpirationTimeStamp > $dateInFutureOn10days->getTimestamp()) {
            return null;
        } else {
            $tempMessage = __(
                'Please go to eBay > Configuration > Accounts > "%1" eBay Account >
 General and click Get Token to generate a new one.',
                $this->helperData->escapeHtml($account->getTitle())
            );
            $title = __(
                'Attention! The Sell API token for "%1" eBay Account expires on %2.',
                $this->helperData->escapeHtml($account->getTitle()),
                $this->_localeDate->formatDate(
                    $account->getChildObject()->getSellApiTokenExpiredDate(),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                __METHOD__
            );
            $issue = $this->issueFactory->createWarningDataObject($title, $tempMessage, $url);
        }

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $tempMessage = __(
                'Please go to eBay > Configuration > Accounts > "%1" eBay Account >
 General and click Get Token to generate a new one.',
                $this->helperData->escapeHtml($account->getTitle())
            );
            $title = __(
                'Attention! The Sell API token for "%1" eBay Account has expired.',
                $this->helperData->escapeHtml($account->getTitle())
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                __METHOD__
            );
            $issue = $this->issueFactory->createErrorDataObject($title, $tempMessage, $url);
        }

        $this->setIssueToCache($account, $issue);

        return $issue;
    }

    /**
     * @return bool
     */
    private function isNeedProcess(): bool
    {
        return $this->ebayViewHelper->isInstallationWizardFinished() &&
            $this->ebayComponentHelper->isEnabled();
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param \Ess\M2ePro\Model\Issue\DataObject $issue
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function setIssueToCache(\Ess\M2ePro\Model\Account $account, Issue $issue): void
    {
        $data = [
            'type' => $issue->getType(),
            'text' => $issue->getText(),
            'title' => $issue->getTitle(),
            'url' => $issue->getUrl(),
        ];

        $this->permanentCacheHelper->setValue(
            $account->getId(),
            $data,
            ['account', 'ebay'],
            60 * 60 * 24
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return \Ess\M2ePro\Model\Issue\DataObject|null
     */
    private function getCachedIssue(\Ess\M2ePro\Model\Account $account): ?Issue
    {
        $data = $this->permanentCacheHelper->getValue($account->getId());
        if ($data === null) {
            return null;
        }

        return $this->issueFactory->create($data['type'], $data['title'], $data['text'], $data['url']);
    }

    /**
     * @param int $accountId
     * @param int $tokenExpirationTimeStamp
     * @param string $messageType
     * @param string $method
     *
     * @return string
     */
    private function getSupportUrl(
        int $accountId,
        int $tokenExpirationTimeStamp,
        string $messageType,
        string $method
    ): string {
        $editHash = sha1(__CLASS__ . $accountId . $tokenExpirationTimeStamp . $messageType . $method);

        return 'https://help.m2epro.com/support/solutions/articles/9000219023/?' . $editHash;
    }

    private function getAccounts(): array
    {
        $accounts = $this->collectionFactory->createWithEbayChildMode();

        return $accounts->getItems();
    }
}
