<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Feedback\Issue;

use \Ess\M2ePro\Model\Issue\DataObject as Issue;
use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Ebay\Feedback\Issue\NegativeReceived
 */
class NegativeReceived extends \Ess\M2ePro\Model\Issue\Locator\AbstractModel
{
    const CACHE_KEY = __CLASS__;

    protected $activeRecordFactory;
    protected $urlBuilder;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->urlBuilder          = $urlBuilder;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getIssues()
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $eBayConfigHelper = $this->getHelper('Component_Ebay_Configuration');
        if (!$eBayConfigHelper->isEnableFeedbackNotificationMode()) {
            return [];
        }

        $lastCheckDate = $eBayConfigHelper->getFeedbackNotificationLastCheck();
        if ($lastCheckDate === null) {
            $eBayConfigHelper->setFeedbackNotificationLastCheck($this->getHelper('Data')->getCurrentGmtDate());

            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection();
        $collection->addFieldToFilter('buyer_feedback_date', ['gt' => $lastCheckDate]);
        $collection->addFieldToFilter('buyer_feedback_type', \Ess\M2ePro\Model\Ebay\Feedback::TYPE_NEGATIVE);

        if ($collection->getSize() > 0) {
            $tempMessage = $this->getHelper('Module\Translation')->__(
                'New Buyer negative Feedback was received. Go to the <a href="%url%" target="blank">Feedback Page</a>.',
                $this->urlBuilder->getUrl('m2epro/ebay_account/index')
            );

            $editHash = sha1(self::CACHE_KEY . $this->getHelper('Data')->getCurrentGmtDate());
            $messageUrl = $this->urlBuilder->getUrl('m2epro/ebay_account/index', [
                '_query' => ['hash' => $editHash]
            ]);

            $eBayConfigHelper->setFeedbackNotificationLastCheck($this->getHelper('Data')->getCurrentGmtDate());

            return [
                $this->modelFactory->getObject('Issue_DataObject', [
                    Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                    Issue::KEY_TITLE => $this->getHelper('Module\Translation')->__(
                        'New Buyer negative Feedback was received.'
                    ),
                    Issue::KEY_TEXT  => $tempMessage,
                    Issue::KEY_URL   => $messageUrl
                ])
            ];
        }

        return [];
    }

    //########################################

    public function isNeedProcess()
    {
        return $this->getHelper('View\Ebay')->isInstallationWizardFinished() &&
            $this->getHelper('Component\Ebay')->isEnabled();
    }

    //########################################
}
