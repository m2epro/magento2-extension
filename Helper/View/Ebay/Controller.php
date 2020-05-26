<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Ebay;

/**
 * Class \Ess\M2ePro\Helper\View\Ebay\Controller
 */
class Controller extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->modelFactory = $modelFactory;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function addMessages()
    {
        /** @var \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationChannel */
        $notificationChannel = $this->modelFactory->getObject('Issue_Notification_Channel_Magento_Session');
        $issueLocators = [
            'Ebay_Marketplace_Issue_NotUpdated',
            'Ebay_Feedback_Issue_NegativeReceived',
            'Ebay_Account_Issue_AccessTokens'
        ];

        foreach ($issueLocators as $locator) {
            /** @var \Ess\M2ePro\Model\Issue\Locator\AbstractModel $locatorModel */
            $locatorModel = $this->modelFactory->getObject($locator);

            foreach ($locatorModel->getIssues() as $issue) {
                $notificationChannel->addMessage($issue);
            }
        }
    }

    //########################################
}
