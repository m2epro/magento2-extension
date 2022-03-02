<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class ShowM2eProLoggers implements InspectorInterface
{
    /** @var array */
    private $loggers = [];

    /** @var HelperFactory */
    private $helperFactory;

    /** @var IssueFactory */
    private $issueFactory;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        IssueFactory $issueFactory
    ) {
        $this->helperFactory = $helperFactory;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];
        $this->searchLoggers();

        if (!empty($this->loggers)) {
            $issues[] = $this->issueFactory->create(
                'M2ePro loggers were found in magento files',
                $this->loggers
            );
        }

        return $issues;
    }

    private function searchLoggers()
    {
        $recursiveIteratorIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->helperFactory->getObject('Client')->getBaseDirectory().'vendor',
                \FilesystemIterator::FOLLOW_SYMLINKS
            )
        );

        foreach ($recursiveIteratorIterator as $splFileInfo) {
            /**@var \SplFileInfo $splFileInfo */

            if (!$splFileInfo->isFile() ||
                !in_array($splFileInfo->getExtension(), ['php', 'phtml'])) {
                continue;
            }

            if (strpos($splFileInfo->getRealPath(), 'Ess'.DIRECTORY_SEPARATOR.'M2ePro') !== false ||
                strpos($splFileInfo->getRealPath(), 'm2e'.DIRECTORY_SEPARATOR.'ebay-amazon-magento2') !== false) {
                continue;
            }

            $splFileObject = $splFileInfo->openFile();
            if (!$splFileObject->getSize()) {
                continue;
            }

            $content = $splFileObject->fread($splFileObject->getSize());
            if (strpos($content, 'Module\Logger') === false) {
                continue;
            }

            $content = explode("\n", $content);
            foreach ($content as $line => $contentRow) {
                if (strpos($contentRow, 'Module\Logger') === false) {
                    continue;
                }

                $this->loggers[] = $splFileObject->getRealPath() . ' in line ' . $line;
            }
        }
    }

    //########################################
}
