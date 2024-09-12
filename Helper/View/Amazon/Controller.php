<?php

namespace Ess\M2ePro\Helper\View\Amazon;

class Controller
{
    private \Ess\M2ePro\Model\Factory $modelFactory;

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
            'Amazon_Marketplace_Issue_ProductTypeOutOfDate',
            'Amazon_Repricing_Issue_InvalidToken',
        ];

        foreach ($issueLocators as $locator) {
            /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate|\Ess\M2ePro\Model\Amazon\Repricing\Issue\InvalidToken $locatorModel */
            $locatorModel = $this->modelFactory->getObject($locator);

            foreach ($locatorModel->getIssues() as $issue) {
                $notificationChannel->addMessage($issue);
            }
        }
    }
}
