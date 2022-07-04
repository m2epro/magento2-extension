<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Walmart;

class Controller
{
    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    /**
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function addMessages(): void
    {
        /** @var \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationChannel */
        $notificationChannel = $this->modelFactory->getObject('Issue_Notification_Channel_Magento_Session');
        $issueLocators = [
            'Walmart_Marketplace_Issue_NotUpdated'
        ];

        foreach ($issueLocators as $locator) {
            /** @var \Ess\M2ePro\Model\Issue\Locator\AbstractModel $locatorModel */
            $locatorModel = $this->modelFactory->getObject($locator);

            foreach ($locatorModel->getIssues() as $issue) {
                $notificationChannel->addMessage($issue);
            }
        }
    }
}
