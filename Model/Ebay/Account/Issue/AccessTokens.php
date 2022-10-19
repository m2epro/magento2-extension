<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use Ess\M2ePro\Helper\Date as DateHelper;
use Ess\M2ePro\Model\Ebay\Account;
use Ess\M2ePro\Model\Issue\DataObject as Issue;

class AccessTokens implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $_localeDate;
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;
    /** @var \Ess\M2ePro\Model\Ebay\AccountFactory */
    private $accountFactory;
    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayComponentHelper;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Rule\Model\Condition\Context $context,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\Ebay\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_localeDate = $context->getLocaleDate();
        $this->ebayViewHelper = $ebayViewHelper;
        $this->helperData = $helperData;
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->accountFactory = $accountFactory;
        $this->issueFactory = $issueFactory;
        $this->translationHelper = $translationHelper;
        $this->supportHelper = $supportHelper;
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
        /** @var \Ess\M2ePro\Model\Ebay\Account $account */
        foreach ($this->getAccountsWithActiveSession() as $account) {
            if ($issue = $this->getTradingApiTokenMessages($account)) {
                $issues[] = $issue;
            }
            if ($issue = $this->getSellApiTokenMessages($account)) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Account $account
     *
     * @return null|Issue
     * @throws \Exception
     */
    private function getTradingApiTokenMessages(Account $account): ?Issue
    {
        $issue = $this->getCachedIssue('trading_', $account);
        if ($issue !== null) {
            return $issue;
        }

        $currentTimeStamp = DateHelper::createCurrentGmt()->getTimestamp();
        $dateInFutureOn10days = DateHelper::createCurrentGmt()->modify('+ 10 days');

        $tokenExpirationTimeStamp = DateHelper::createDateGmt($account->getTokenExpiredDate())->getTimestamp();
        if ($tokenExpirationTimeStamp > $dateInFutureOn10days->getTimestamp()) {
            return null;
        } else {
            $tempMessage = $this->translationHelper->__(
                <<<TEXT
Attention! The Trading API token for <a href="%url%" target="_blank">"%name%"</a> eBay Account expires on %date%.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getParentObject()->getTitle()),
                $this->_localeDate->formatDate(
                    $account->getTokenExpiredDate(),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );
            $title = $this->translationHelper->__(
                'Attention! The Trading API token for "%name%" eBay account is to expire.
                    You need to generate a new access token to reauthorize M2E Pro.',
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                __METHOD__
            );
            $issue = $this->issueFactory->createNoticeDataObject($title, $tempMessage, $url);
        }

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $tempMessage = $this->translationHelper->__(
                <<<TEXT
Attention! The Trading API token for <a href="%url%" target="_blank">"%name%"</a> eBay account has expired.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $title = $this->translationHelper->__(
                'Attention! The Trading API token for "%name%" eBay account has expired.
                    You need to generate a new access token to reauthorize M2E Pro.',
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                __METHOD__
            );
            $issue = $this->issueFactory->createErrorDataObject($title, $tempMessage, $url);
        }

        $this->setIssueToCache('trading_', $account, $issue);

        return $issue;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Account $account
     *
     * @return null|Issue
     * @throws \Exception
     */
    private function getSellApiTokenMessages(Account $account): ?Issue
    {
        $issue = $this->getCachedIssue('sell_', $account);
        if ($issue !== null) {
            return $issue;
        }

        if (empty($account->getSellApiTokenExpiredDate())) {
            return null;
        }
        $currentTimeStamp = DateHelper::createCurrentGmt()->getTimestamp();
        $dateInFutureOn10days = DateHelper::createCurrentGmt()->modify('+ 10 days');
        $tokenExpirationTimeStamp = DateHelper::createDateGmt($account->getSellApiTokenExpiredDate())->getTimestamp();

        if ($tokenExpirationTimeStamp <= 0 || $tokenExpirationTimeStamp > $dateInFutureOn10days->getTimestamp()) {
            return null;
        } else {
            $tempMessage = $this->translationHelper->__(
                <<<TEXT
Attention! The Sell API token for <a href="%url%" target="_blank">"%name%"</a> eBay Account expires on %date%.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getParentObject()->getTitle()),
                $this->_localeDate->formatDate(
                    $account->getSellApiTokenExpiredDate(),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );
            $title = $this->translationHelper->__(
                'Attention! The Sell API token for "%name%" eBay account is to expire.
                    You need to generate a new access token to reauthorize M2E Pro.',
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                __METHOD__
            );
            $issue = $this->issueFactory->createNoticeDataObject($title, $tempMessage, $url);
        }

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $tempMessage = $this->translationHelper->__(
                <<<TEXT
Attention! The Sell API token for <a href="%url%" target="_blank">"%name%"</a> eBay account has expired.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $title = $this->translationHelper->__(
                'Attention! The Sell API token for "%name%" eBay account has expired.
                    You need to generate a new access token to reauthorize M2E Pro.',
                $this->helperData->escapeHtml($account->getParentObject()->getTitle())
            );
            $url = $this->getSupportUrl(
                (int)$account->getId(),
                $tokenExpirationTimeStamp,
                \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                __METHOD__
            );
            $issue = $this->issueFactory->createErrorDataObject($title, $tempMessage, $url);
        }

        $this->setIssueToCache('sell_', $account, $issue);

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
     * @param string $cacheKeyPrefix
     * @param \Ess\M2ePro\Model\Ebay\Account $account
     * @param \Ess\M2ePro\Model\Issue\DataObject $issue
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function setIssueToCache(string $cacheKeyPrefix, Account $account, Issue $issue): void
    {
        $data = [
            'type' => $issue->getType(),
            'text' => $issue->getText(),
            'title' => $issue->getTitle(),
            'url' => $issue->getUrl(),
        ];

        $this->permanentCacheHelper->setValue(
            $cacheKeyPrefix . $account->getId(),
            $data,
            ['account', 'ebay'],
            60 * 60 * 24
        );
    }

    /**
     * @param string $cacheKeyPrefix
     * @param \Ess\M2ePro\Model\Ebay\Account $account
     *
     * @return \Ess\M2ePro\Model\Issue\DataObject|null
     */
    private function getCachedIssue(string $cacheKeyPrefix, Account $account): ?Issue
    {
        $data = $this->permanentCacheHelper->getValue($cacheKeyPrefix . $account->getId());
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

        return $this->supportHelper->getSupportUrl(
                '/support/solutions/articles/9000218991'
            ) . '/?' . $editHash;
    }

    /**
     * @return null|array
     */
    private function getAccountsWithActiveSession(): ?array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accounts */
        $accounts = $this->accountFactory->create()->getCollection();
        $accounts->addFieldToFilter('token_session', ['notnull' => true]);

        return $accounts->getItems();
    }
}
