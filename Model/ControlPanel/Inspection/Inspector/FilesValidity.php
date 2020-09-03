<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;
use Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Component\ComponentRegistrar;
use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Backend\Model\UrlInterface;

class FilesValidity extends AbstractInspection implements InspectorInterface
{
    /** @var ComponentRegistrarInterface */
    protected $componentRegistrar;

    /** @var File */
    protected $fileDriver;

    /** @var ReadFactory */
    protected $readFactory;

    public function __construct(
        Factory $resultFactory,
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        FormKey $formKey,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        ReadFactory $readFactory,
        File $fileDriver,
        ComponentRegistrarInterface $componentRegistrar,
        array $_params = []
    ) {
        $this->readFactory        = $readFactory;
        $this->fileDriver         = $fileDriver;
        $this->componentRegistrar = $componentRegistrar;

        parent::__construct(
            $resultFactory,
            $helperFactory,
            $modelFactory,
            $urlBuilder,
            $resourceConnection,
            $formKey,
            $parentFactory,
            $activeRecordFactory,
            $_params
        );
    }

    //########################################

    public function getTitle()
    {
        return 'Files validity';
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    //########################################

    public function process()
    {
        $issues = [];

        try {
            $diff = $this->getDiff();
        } catch (\Exception $exception) {
            $issues[] = $this->resultFactory->createError($this, $exception->getMessage());

            return $issues;
        }

        if (empty($diff)) {
            $issues[] = $this->resultFactory->createNotice($this, 'No info for this M2e Pro version');

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
            $issues[] = $this->resultFactory->createError(
                $this,
                'Wrong files validity',
                $this->renderMetadata($problems)
            );
        }

        return $issues;
    }

    protected function getDiff()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('files', 'get', 'info');
        $dispatcherObject->process($connectorObj);
        return $connectorObj->getResponseData();
    }

    protected function renderMetadata($data)
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
