<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Backend\Model\UrlInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class FilesValidity implements InspectorInterface
{
    /** @var ModelFactory */
    private $modelFactory;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ComponentRegistrarInterface */
    private $componentRegistrar;

    /** @var File */
    private $fileDriver;

    /** @var ReadFactory */
    private $readFactory;

    /** @var IssueFactory  */
    private $issueFactory;

    public function __construct(
        ModelFactory $modelFactory,
        UrlInterface $urlBuilder,
        ReadFactory $readFactory,
        File $fileDriver,
        ComponentRegistrarInterface $componentRegistrar,
        IssueFactory $issueFactory
    ) {
        $this->modelFactory       = $modelFactory;
        $this->urlBuilder         = $urlBuilder;
        $this->readFactory        = $readFactory;
        $this->fileDriver         = $fileDriver;
        $this->componentRegistrar = $componentRegistrar;
        $this->issueFactory       = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];

        try {
            $diff = $this->getDiff();
        } catch (\Exception $exception) {
            $issues[] = $this->issueFactory->create($exception->getMessage());

            return $issues;
        }

        if (empty($diff)) {
            $issues[] = $this->issueFactory->create('No info for this M2e Pro version');

            return $issues;
        }

        $problems = [];
        $basePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);

        foreach ($diff['files_info'] as $info) {
            $filePath = $basePath . DIRECTORY_SEPARATOR . $info['path'];

            if (!$this->fileDriver->isExists($filePath)) {
                $problems[] = [
                    'path' => $info['path'],
                    'reason' => 'File is missing'
                ];
                continue;
            }

            /** @var \Magento\Framework\Filesystem\File\Read $fileReader */
            $fileReader = $this->readFactory->create($filePath, $this->fileDriver);

            $fileContent = trim($fileReader->readAll());
            $fileContent = str_replace(["\r\n", "\n\r", PHP_EOL], chr(10), $fileContent);

            if (call_user_func('md5', $fileContent) != $info['hash']) {
                $problems[] = [
                    'path' => $info['path'],
                    'reason' => 'Hash mismatch'
                ];
                continue;
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

    private function getDiff()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('files', 'get', 'info');
        $dispatcherObject->process($connectorObj);
        return $connectorObj->getResponseData();
    }

    private function renderMetadata($data)
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
            $html .= <<<HTML
<tr>
    <td>
        {$item['path']}
    </td>
    <td>
        {$item['reason']}
    </td>
    <td style="text-align: center;">
        <a href="{$url}" target="_blank">Diff</a>
    </td>
</tr>

HTML;
        }
        $html .= '</table>';

        return $html;
    }

    //########################################
}
