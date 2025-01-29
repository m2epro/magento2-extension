<?php

declare(strict_types=1);

namespace Ess\M2ePro\Helper\View\Amazon;

class Controller
{
    private \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationSession;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationSession
    ) {
        $this->notificationSession = $notificationSession;
        $this->objectManager = $objectManager;
    }

    public function addMessages(): void
    {
        $issueLocators = [
            \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate::class,
            \Ess\M2ePro\Model\Amazon\Repricing\Issue\InvalidToken::class,
            \Ess\M2ePro\Model\Module\Issue\NewVersion::class,
        ];

        foreach ($issueLocators as $locator) {
            /** @var \Ess\M2ePro\Model\Issue\LocatorInterface $locatorModel */
            $locatorModel = $this->objectManager->create($locator);

            foreach ($locatorModel->getIssues() as $issue) {
                $this->notificationSession->addMessage($issue);
            }
        }
    }
}
