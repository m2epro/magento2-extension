<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

class FilesValidity implements \Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
    private $connectorDispatcher;
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
    /** @var \Magento\Framework\Component\ComponentRegistrarInterface */
    private $componentRegistrar;
    /** @var \Magento\Framework\Filesystem\Driver\File */
    private $fileDriver;
    /** @var \Magento\Framework\Filesystem\File\ReadFactory */
    private $readFactory;
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory */
    private $issueFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $connectorDispatcher,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar,
        \Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory $issueFactory
    ) {
        $this->helperData = $helperData;
        $this->connectorDispatcher = $connectorDispatcher;
        $this->urlBuilder = $urlBuilder;
        $this->readFactory = $readFactory;
        $this->fileDriver = $fileDriver;
        $this->componentRegistrar = $componentRegistrar;
        $this->issueFactory = $issueFactory;
    }

    /**
     * @return array|\Ess\M2ePro\Model\ControlPanel\Inspection\Issue[]
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function process(): array
    {
        $issues = [];

        try {
            $serverFiles = $this->receiveFilesFromServer();
        } catch (\Throwable $exception) {
            $issues[] = $this->issueFactory->create($exception->getMessage());

            return $issues;
        }

        if (empty($serverFiles)) {
            $issues[] = $this->issueFactory->create('No info for this M2e Pro version');

            return $issues;
        }

        $problems = [];
        $basePath = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            \Ess\M2ePro\Helper\Module::IDENTIFIER
        );

        $clientFiles = $this->getClientFiles($basePath);

        foreach ($clientFiles as $path => $hash) {
            if (!isset($serverFiles[$path])) {
                $problems[] = [
                    'path' => $path,
                    'reason' => 'New file detected',
                ];
            }
        }

        foreach ($serverFiles as $path => $hash) {
            if (!isset($clientFiles[$path])) {
                $problems[] = [
                    'path' => $path,
                    'reason' => 'File is missing',
                ];
                continue;
            }

            if ($clientFiles[$path] != $hash) {
                $problems[] = [
                    'path' => $path,
                    'reason' => 'Hash mismatch',
                ];
            }
        }

        if (!empty($problems)) {
            $issues[] = $this->issueFactory->create(
                'Wrong files validity',
                $this->renderMetadata($problems)
            );
        }

        return $issues;
    }

    private function receiveFilesFromServer(): array
    {
        /** @var \Ess\M2ePro\Model\M2ePro\Connector\Files\Get\Info $connectorObj */
        $connectorObj = $this->connectorDispatcher->getConnector(
            'files',
            'get',
            'info'
        );

        $this->connectorDispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    private function getClientFiles(string $basePath): array
    {
        $clientFiles = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = str_replace($basePath, '', $file->getPathname());

                /** @var \Magento\Framework\Filesystem\File\Read $fileReader */
                $fileReader = $this->readFactory->create($basePath . $path, $this->fileDriver);

                $fileContent = trim($fileReader->readAll());
                $fileContent = str_replace(["\r\n", "\n\r", PHP_EOL], chr(10), $fileContent);

                $clientFiles[$path] = $this->helperData->md5String($fileContent);
            }
        }

        return $clientFiles;
    }

    private function renderMetadata(array $data): string
    {
        $html = <<<HTML
<table>
    <tr>
        <th>Path</th>
        <th>Reason</th>
        <th>Action</th>
    </tr>
HTML;
        foreach ($data as $item) {
            $url = $this->urlBuilder->getUrl(
                'm2epro/controlPanel_tools_m2ePro/install',
                ['action' => 'filesDiff', 'filePath' => base64_encode($item['path'])]
            );

            $link = ($item['reason'] === 'New file detected') ? '' : "<a href='$url' target='_blank'>Diff</a>";

            $html .= <<<HTML
<tr>
    <td>
        {$item['path']}
    </td>
    <td>
        {$item['reason']}
    </td>
    <td style="text-align: center;">
        {$link}
    </td>
</tr>

HTML;
        }
        $html .= '</table>';

        return $html;
    }
}
