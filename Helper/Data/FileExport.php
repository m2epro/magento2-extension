<?php

namespace Ess\M2ePro\Helper\Data;

class FileExport
{
    public const ALL_ITEMS_GRID = 'All';
    public const UNMANAGED_GRID = 'Unmanaged';

    /** @var \Magento\Framework\Controller\ResultFactory */
    private $resultFactory;

    public function __construct(
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param string $gridName
     * @param string $content
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function createFile(string $gridName, string $content): \Magento\Framework\Controller\ResultInterface
    {
        $fileName = $this->generateExportFileName($gridName);

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);

        $result->setHeader('Content-Type', 'text/csv');
        $result->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $result->setContents($content);

        return $result;
    }

    /**
     * @param string $gridName
     *
     * @return string
     * @throws \Exception
     */
    private function generateExportFileName(string $gridName): string
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $dateString = $date->format('Ymd_His');

        return $gridName . '_' . $dateString . '.csv';
    }
}
