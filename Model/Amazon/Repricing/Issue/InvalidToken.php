<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Issue;

class InvalidToken implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    /** @var string */
    public const CACHE_KEY = 'repricing_invalid_token_errors';
    /** @var int */
    private const CACHE_LIFE = 60 * 60 * 24;

    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Repricing\Collection */
    private $repricingCollection;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Magento\Framework\UrlInterface */
    private $url;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;
    /** @var \Ess\M2ePro\Helper\View\Amazon */
    private $amazonViewHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonComponentHelper;

    /**
     * @param \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Repricing\Collection $repricingCollection
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param \Magento\Framework\UrlInterface $url
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
     * @param \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper
     */
    public function __construct(
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Repricing\Collection $repricingCollection,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Magento\Framework\UrlInterface $url,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonComponentHelper
    ) {
        $this->issueFactory = $issueFactory;
        $this->repricingCollection = $repricingCollection;
        $this->translationHelper = $translationHelper;
        $this->url = $url;
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->amazonComponentHelper = $amazonComponentHelper;
    }

    /**
     * @inheritDoc
     */
    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $cachedMessages = $this->permanentCacheHelper->getValue(self::CACHE_KEY);
        if ($cachedMessages !== null) {
            return $this->makeErrorObject($cachedMessages);
        }

        $invalidAccounts = $this->repricingCollection->getInvalidAccounts();

        $messages = [];
        foreach ($invalidAccounts as $invalidAccount) {
            $messages[] = [
                'title' => $invalidAccount->getAccountId(),
                'text' => $this->translationHelper->__(
                    "Your Repricer account was deleted.
                Unlink it from M2E Pro or <a href='%support_link%'>contact support</a>
                if your Repricer account is valid.",
                    ['support_link' => $this->url->getUrl('*/support/index')]
                ),
            ];
        }

        $this->permanentCacheHelper->setValue(
            self::CACHE_KEY,
            $messages,
            ['amazon', 'repricing'],
            self::CACHE_LIFE
        );

        return $this->makeErrorObject($messages);
    }

    /**
     * @param array $messages
     *
     * @return array
     */
    private function makeErrorObject(array $messages): array
    {
        $issues = [];
        foreach ($messages as $message) {
            $issues[] = $this->issueFactory
                ->createErrorDataObject(
                    $message['title'] ?? '',
                    $message['text'] ?? '',
                    null
                );
        }

        return $issues;
    }

    /**
     * @return bool
     */
    private function isNeedProcess(): bool
    {
        if (!$this->amazonComponentHelper->isEnabled()) {
            return false;
        }

        if (!$this->amazonViewHelper->isInstallationWizardFinished()) {
            return false;
        }

        return true;
    }
}
