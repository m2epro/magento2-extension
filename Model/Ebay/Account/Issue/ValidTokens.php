<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use Ess\M2ePro\Model\Issue\DataObject as Issue;

class ValidTokens implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    public const ACCOUNT_TOKENS_CACHE_KEY = 'ebay_account_tokens_validations';

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;
    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayComponentHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
    private $ebayDispatcher;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper,
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $ebayDispatcher,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Account\CollectionFactory $accountCollectionFactory
    ) {
        $this->ebayViewHelper = $ebayViewHelper;
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->issueFactory = $issueFactory;
        $this->translationHelper = $translationHelper;
        $this->ebayComponentHelper = $ebayComponentHelper;
        $this->ebayDispatcher = $ebayDispatcher;
        $this->accountCollectionFactory = $accountCollectionFactory;
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

        $accounts = $this->permanentCacheHelper->getValue(self::ACCOUNT_TOKENS_CACHE_KEY);
        if ($accounts !== null) {
            return $this->prepareIssues($accounts);
        }

        try {
            $accounts = $this->retrieveNotValidAccounts();
        } catch (\Ess\M2ePro\Model\Exception $e) {
            $accounts = [];
        }

        $this->permanentCacheHelper->setValue(
            self::ACCOUNT_TOKENS_CACHE_KEY,
            $accounts,
            ['ebay', 'account'],
            3600
        );

        return $this->prepareIssues($accounts);
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function retrieveNotValidAccounts(): array
    {
        $accountsHashes = $this->getPreparedAccountsData();
        if (!$accountsHashes) {
            return [];
        }
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Account\Get\AuthInfo $connectorObj */
        $connectorObj = $this->ebayDispatcher->getConnector('account', 'get', 'authInfo', [
            'accounts' => array_keys($accountsHashes),
        ]);
        $connectorObj->process();
        $accountsFromResponse = $connectorObj->getResponseData();
        $result = [];
        foreach ($accountsFromResponse as $accountHash => $isValid) {
            if (!$isValid) {
                $result[]['account_name'] = $accountsHashes[$accountHash];
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isNeedProcess(): bool
    {
        return $this->ebayComponentHelper->isEnabled() && $this->ebayViewHelper->isInstallationWizardFinished();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function prepareIssues(array $data): array
    {
        $issues = [];
        foreach ($data as $account) {
            $issues[] = $this->getIssue($account['account_name']);
        }

        return $issues;
    }

    /**
     * @param string $accountName
     *
     * @return \Ess\M2ePro\Model\Issue\DataObject
     */
    private function getIssue(string $accountName): Issue
    {
        $text = $this->translationHelper->__(
            "The token of eBay account \"%account_name%\" is no longer valid.
         Please edit your eBay account and get a new token.",
            $accountName
        );

        return $this->issueFactory->createErrorDataObject($accountName, $text, null);
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPreparedAccountsData(): array
    {
        $accountsCollection = $this->accountCollectionFactory->create();
        $accountsHashes = [];
        /** @var \Ess\M2ePro\Model\Ebay\Account $account */
        foreach ($accountsCollection->getItems() as $account) {
            $accountsHashes[$account->getServerHash()] = $account->getParentObject()->getTitle();
        }

        return $accountsHashes;
    }
}
