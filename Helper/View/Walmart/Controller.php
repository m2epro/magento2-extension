<?php

declare(strict_types=1);

namespace Ess\M2ePro\Helper\View\Walmart;

class Controller
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;
    private \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationSession;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session $notificationSession
    ) {
        $this->objectManager = $objectManager;
        $this->notificationSession = $notificationSession;
    }

    public function addMessages(): void
    {
        $issueLocators = [
            \Ess\M2ePro\Model\Walmart\Marketplace\Issue\NotUpdated::class,
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
