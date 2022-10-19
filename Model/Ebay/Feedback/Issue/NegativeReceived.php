<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Feedback\Issue;

use Ess\M2ePro\Helper\Component\Ebay\Configuration;
use Ess\M2ePro\Helper\View\Ebay;
use Ess\M2ePro\Model\Ebay\Feedback;
use Ess\M2ePro\Model\Issue\LocatorInterface;
use Magento\Backend\Model\UrlInterface;

class NegativeReceived implements LocatorInterface
{
    /** @var string */
    private const CACHE_KEY = __CLASS__;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Configuration */
    private $componentEbayConfiguration;

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $issueFactory;

    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayComponentHelper;

    /** @var \Ess\M2ePro\Model\Ebay\FeedbackFactory */
    private $ebayFeedbackFactory;

    /**
     * @param \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory
     * @param \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper
     * @param \Ess\M2ePro\Model\Ebay\FeedbackFactory $ebayFeedbackFactory
     */
    public function __construct(
        Configuration $componentEbayConfiguration,
        UrlInterface $urlBuilder,
        Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Component\Ebay $ebayComponentHelper,
        \Ess\M2ePro\Model\Ebay\FeedbackFactory $ebayFeedbackFactory
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->componentEbayConfiguration = $componentEbayConfiguration;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->helperData = $helperData;
        $this->translationHelper = $translationHelper;
        $this->issueFactory = $issueFactory;
        $this->ebayComponentHelper = $ebayComponentHelper;
        $this->ebayFeedbackFactory = $ebayFeedbackFactory;
    }

    /**
     * @inheritDoc
     */
    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $eBayConfigHelper = $this->componentEbayConfiguration;
        if (!$eBayConfigHelper->isEnableFeedbackNotificationMode()) {
            return [];
        }

        $lastCheckDate = $eBayConfigHelper->getFeedbackNotificationLastCheck();
        if ($lastCheckDate === null) {
            $eBayConfigHelper->setFeedbackNotificationLastCheck($this->helperData->getCurrentGmtDate());

            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\Collection $collection */
        $collection = $this->ebayFeedbackFactory->create()->getCollection();
        $collection->addFieldToFilter('buyer_feedback_date', ['gt' => $lastCheckDate]);
        $collection->addFieldToFilter('buyer_feedback_type', Feedback::TYPE_NEGATIVE);

        if ($collection->getSize() > 0) {
            $tempMessage = $this->translationHelper->__(
                'New Buyer negative Feedback was received. Go to the <a href="%url%" target="blank">Feedback Page</a>.',
                $this->urlBuilder->getUrl('m2epro/ebay_account/index')
            );

            $editHash = sha1(self::CACHE_KEY . $this->helperData->getCurrentGmtDate());
            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/index', [
                '_query' => ['hash' => $editHash],
            ]);

            $eBayConfigHelper->setFeedbackNotificationLastCheck($this->helperData->getCurrentGmtDate());

            return [
                $this->issueFactory->createNoticeDataObject(
                    $this->translationHelper->__(
                        'New Buyer negative Feedback was received.'
                    ),
                    $tempMessage,
                    $messageUrl
                ),
            ];
        }

        return [];
    }

    /**
     * @return bool
     */
    public function isNeedProcess(): bool
    {
        return $this->ebayViewHelper->isInstallationWizardFinished() &&
            $this->ebayComponentHelper->isEnabled();
    }
}
