<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrar;

class Install extends Command
{
    /** @var \Magento\Framework\Filesystem\Driver\File  */
    protected $filesystemDriver;

    /** @var \Magento\Framework\Filesystem  */
    protected $fileSystem;

    /** @var \Magento\Framework\Filesystem\File\ReadFactory  */
    protected $fileReaderFactory;

    /** @var ComponentRegistrar */
    protected $componentRegistrar;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Repository */
    protected  $repository;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory */
    protected $handlerFactory;

    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReaderFactory,
        ComponentRegistrar $componentRegistrar,
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        Context $context,
        \Ess\M2ePro\Model\ControlPanel\Inspection\Repository $repository,
        \Ess\M2ePro\Model\ControlPanel\Inspection\HandlerFactory $handlerFactory
    ) {
        parent::__construct($controlPanelHelper, $context);

        $this->filesystemDriver  = $filesystemDriver;
        $this->fileSystem        = $filesystem;
        $this->fileReaderFactory = $fileReaderFactory;

        $this->componentRegistrar = $componentRegistrar;

        $this->repository = $repository;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @hidden
     */
    public function fixColumnAction()
    {
        $repairInfo = $this->getRequest()->getPost('repair_info');

        if (empty($repairInfo)) {
            return;
        }

        foreach ($repairInfo as $item) {
            $columnsInfo[] = (array)$this->getHelper('Data')->jsonDecode($item);
        }

        $definition = $this->repository->getDefinition('TablesStructureValidity');

        /** @var  \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\TablesStructureValidity $inspector */
        $inspector = $this->handlerFactory->create($definition);

        foreach ($columnsInfo as $columnInfo) {
            $inspector->fix($columnInfo);
        }
    }

    /**
     * @title "Files Diff"
     * @description "Files Diff"
     * @hidden
     */
    public function filesDiffAction()
    {
        $filePath     = base64_decode($this->getRequest()->getParam('filePath', ''));
        $originalPath = base64_decode($this->getRequest()->getParam('originalPath', ''));

        $basePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);
        $fullPath = $basePath .DIRECTORY_SEPARATOR. $filePath;

        $params = [
            'content' => '',
            'path'    => $originalPath ? $originalPath : $filePath
        ];

        if ($this->filesystemDriver->isExists($fullPath)) {

            /** @var \Magento\Framework\Filesystem\File\Read $fileReader */
            $fileReader = $this->fileReaderFactory->create($fullPath, $this->filesystemDriver);
            $params['content'] = $fileReader->readAll();
        }

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('files', 'get', 'diff', $params);

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $html = $this->getStyleHtml();

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Files Difference
    <span style="color: #808080; font-size: 15px;">({$filePath})</span>
</h2>
<br/>
HTML;

        if (isset($responseData['html'])) {
            $html .= $responseData['html'];
        } else {
            $html .= '<h1>&nbsp;&nbsp;No file on server</h1>';
        }

        return $html;
    }

    //########################################

    /**
     * @title "Static Content Deploy"
     * @description "Static Content Deploy"
     */
    public function staticContentDeployAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        return '<pre>' . call_user_func(
            'shell_exec',
            'php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:static-content:deploy'
        );
    }

    /**
     * @title "Run Magento Compilation"
     * @description "Run Magento Compilation"
     */
    public function runCompilationAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        return '<pre>' . call_user_func(
            'shell_exec',
            'php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:di:compile'
        );
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->controlPanelHelper->getPageOwerviewTabUrl();

        return <<<HTML
<h2 style="margin: 20px 0 0 10px">
    {$messageText} <span style="color: grey; font-size: 10px;">
    <a href="{$backUrl}">[back]</a>
</h2>
HTML;
    }

    //########################################
}
