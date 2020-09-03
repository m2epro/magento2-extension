<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class ShowM2eProLoggers extends AbstractInspection implements InspectorInterface
{
    /** @var array */
    protected $loggers = [];

    //########################################

    public function getTitle()
    {
        return 'Show M2ePro loggers';
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_SLOW;
    }

    //########################################

    public function process()
    {
        $issues = [];
        $this->searchLoggers();

        if (!empty($this->loggers)) {
            $issues[] = $this->resultFactory->createNotice(
                $this,
                'M2ePro loggers were found in magento files',
                $this->loggers
            );
        }

        return $issues;
    }

    protected function searchLoggers()
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
