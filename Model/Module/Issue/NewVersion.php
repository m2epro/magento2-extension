<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Module\Issue;

class NewVersion implements \Ess\M2ePro\Model\Issue\LocatorInterface
{
    private \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory;
    private \Ess\M2ePro\Helper\Module $module;

    public function __construct(
        \Ess\M2ePro\Model\Issue\DataObjectFactory $issueFactory,
        \Ess\M2ePro\Helper\Module $module
    ) {
        $this->issueFactory = $issueFactory;
        $this->module = $module;
    }

    public function getIssues(): array
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        return [$this->getIssue()];
    }

    private function isNeedProcess(): bool
    {
        if (!$this->module->hasLatestVersion()) {
            return false;
        }

        $publicVersion = $this->module->getPublicVersion();
        $latestVersion = $this->module->getLatestVersion();

        if (version_compare($latestVersion, $publicVersion, '>')) {
            return true;
        }

        return false;
    }

    private function getIssue(): \Ess\M2ePro\Model\Issue\DataObject
    {
        $title = $this->module->getName();

        $text = (string)__("A new version of M2E Pro extension is now available! " .
            "Upgrade now to access the latest features and improvements.");

        return $this->issueFactory->createNoticeDataObject($title, $text, null);
    }
}
