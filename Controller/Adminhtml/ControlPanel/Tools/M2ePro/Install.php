<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;
use Ess\M2ePro\Helper\Module;
use Magento\Framework\Component\ComponentRegistrar;

class Install extends Command
{
    protected $directoryReaderFactory;

    protected $componentRegistrar;

    protected $fileSystem;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory,
        ComponentRegistrar $componentRegistrar,
        \Magento\Framework\Filesystem $filesystem,
        Context $context
    ) {
        $this->directoryReaderFactory = $directoryReaderFactory;
        $this->fileSystem             = $filesystem;
        $this->componentRegistrar     = $componentRegistrar;

        parent::__construct($context);
    }

    //########################################

    //todo implement
    /**
     * @title "Check Files Validity"
     * @description "Check Files Validity"
     */
    public function checkFilesValidityAction()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('files','get','info');
        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (count($responseData) <= 0) {
            return $this->getEmptyResultsHtml('No files info for this M2E Pro version on server.');
        }

        $problems = array();

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);
        $directoryReader = $this->directoryReaderFactory->create($path);

        foreach ($responseData['files_info'] as $info) {

            if (!$directoryReader->isFile($info['path'])) {
                $problems[] = array(
                    'path' => $info['path'],
                    'reason' => 'File is missing'
                );
                continue;
            }

            $fileContent = trim($directoryReader->readFile($info['path']));
            $fileContent = str_replace(array("\r\n","\n\r",PHP_EOL), chr(10), $fileContent);

            if (md5($fileContent) != $info['hash']) {
                $problems[] = array(
                    'path' => $info['path'],
                    'reason' => 'Hash mismatch'
                );
                continue;
            }
        }

        if (count($problems) <= 0) {
            return '<h2 style="margin: 20px 0 0 10px">All files are valid.</span></h2>';
        }

        $html = $this->getStyleHtml();

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Files Validity
    <span style="color: #808080; font-size: 15px;">(%count% entries)</span>
</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0">
    <tr>
        <th style="width: 600px">Path</th>
        <th>Reason</th>
        <th>Action</th>
    </tr>
HTML;
        foreach ($problems as $item) {

            $url = $this->getUrl('*/*/*', ['action' => 'filesDiff', 'filePath' => base64_encode($item['path'])]);
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
        return str_replace('%count%',count($problems),$html);
    }

    //todo implement
    /**
     * @title "Check Tables Structure Validity"
     * @description "Check Tables Structure Validity"
     */
    public function checkTablesStructureValidityAction()
    {
        $tablesInfo = $this->getHelper('Module\Database\Structure')->getTablesInfo();

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('tables','get','diff',
            array('tables_info' => json_encode($tablesInfo)));

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['diff'])) {
            return $this->getEmptyResultsHtml('No Tables info for this M2E Pro version on Server.');
        }

        if (count($responseData['diff']) <= 0) {
            return $this->getEmptyResultsHtml('All Tables are valid.');
        }

        $html = $this->getStyleHtml();

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Tables Structure Validity
    <span style="color: #808080; font-size: 15px;">(%count% entries)</span>
</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0">
    <tr>
        <th style="width: 400px">Table</th>
        <th>Problem</th>
        <th style="width: 300px">Info</th>
        <th style="width: 100px">Actions</th>
    </tr>
HTML;

        foreach ($responseData['diff'] as $tableName => $checkResult) {
            foreach ($checkResult as $resultRow) {

                $additionalInfo = '';
                $actionsHtml    = '';

                if (!isset($resultRow['info'])) {
                    continue;
                }

                $resultInfo = $resultRow['info'];
                $diffData = isset($resultInfo['diff_data']) ? $resultInfo['diff_data'] : array();

                if (isset($resultInfo['diff_data'])) {
                    foreach ($resultInfo['diff_data'] as $diffCode => $diffValue) {

                        $additionalInfo .= "<b>{$diffCode}</b>: '{$diffValue}'. ";
                        $additionalInfo .= "<b>original:</b> '{$resultInfo['original_data'][$diffCode]}'.";
                        $additionalInfo .= "<br/>";
                    }
                }

                $urlParams = array(
                    'table_name'  => $tableName,
                    'column_info' => json_encode($resultInfo['original_data'])
                );

                if (empty($resultInfo['current_data']) ||
                    (isset($diffData['type']) || isset($diffData['default']) || isset($diffData['null']))) {

                    $urlParams['mode'] = 'properties';
                    $url = $this->getUrl('*/*/fixColumn', $urlParams);
                    $actionsHtml .= "<a href=\"{$url}\">Fix Properties</a>";
                }

                if (isset($diffData['key'])) {

                    $urlParams['mode'] = 'index';
                    $url = $this->getUrl('*/*/fixColumn', $urlParams);
                    $actionsHtml .= "<a href=\"{$url}\">Fix Index</a>";
                }

                if (empty($resultInfo['original_data']) && !empty($resultInfo['current_data'])) {

                    $urlParams['mode'] = 'drop';
                    $urlParams['column_info'] = json_encode($resultInfo['current_data']);
                    $url = $this->getUrl('*/*/fixColumn', $urlParams);
                    $actionsHtml .= "<a href=\"{$url}\">Drop</a>";
                }

                $html .= <<<HTML
<tr>
    <td>{$tableName}</td>
    <td>{$resultRow['message']}</td>
    <td>&nbsp;{$additionalInfo}&nbsp;</td>
    <td>&nbsp;{$actionsHtml}&nbsp;</td>
</tr>
HTML;
            }
        }

        $html .= '</table>';
        return str_replace('%count%',count($responseData['diff']),$html);
    }

    //todo implement
    /**
     * @title "Check Configs Validity"
     * @description "Check Configs Validity"
     */
    public function checkConfigsValidityAction()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('configs','get','info');
        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['configs_info'])) {
            return $this->getEmptyResultsHtml('No configs info for this M2E Pro version on server.');
        }

        $originalData = $responseData['configs_info'];
        $currentData = array();

        foreach ($originalData as $tableName => $configInfo) {

            $currentData[$tableName] = $this->getHelper('Module\Database\Structure')
                ->getConfigSnapshot($tableName);
        }

        $differenses = array();

        foreach ($originalData as $tableName => $configInfo) {
            foreach ($configInfo as $codeHash => $item) {

                if (array_key_exists($codeHash, $currentData[$tableName])) {
                    continue;
                }

                $differenses[] = array('table'    => $tableName,
                                       'item'     => $item,
                                       'solution' => 'insert');
            }
        }

        foreach ($currentData as $tableName => $configInfo) {
            foreach ($configInfo as $codeHash => $item) {

                if (array_key_exists($codeHash, $originalData[$tableName])) {
                    continue;
                }

                $differenses[] = array('table'    => $tableName,
                                       'item'     => $item,
                                       'solution' => 'drop');
            }
        }

        if (count($differenses) <= 0) {
            return $this->getEmptyResultsHtml('All Configs are valid.');
        }

        $html = $this->getStyleHtml();

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Configs Validity
    <span style="color: #808080; font-size: 15px;">(%count% entries)</span>
</h2>
<br/>

<table class="grid" cellpadding="0" cellspacing="0" style="width: 100%;">
    <tr>
        <th style="width: 400px">Table</th>
        <th style="width: 200px">Group</th>
        <th style="width: 200px">Key</th>
        <th style="width: 150px">Value</th>
        <th style="width: 50px">Action</th>
    </tr>
HTML;

        foreach ($differenses as $index => $row) {

            if ($row['solution'] == 'insert') {

                $url = $this->getUrl('*/controlPanel_database/addTableRow', array(
                    'table'  => $row['table'],
                    'model'  => $this->getHelper('Module\Database\Structure')->getTableModel($row['table']),
                ));

            } else {

                $url = $this->getUrl('*/controlPanel_database/deleteTableRows', array(
                    'table'  => $row['table'],
                    'model'  => $this->getHelper('Module\Database\Structure')->getTableModel($row['table']),
                    'ids'    => $row['item']['id']
                ));
            }

            $actionWord = $row['solution'] == 'insert' ? 'Insert' : 'Drop';
            $styles = $row['solution'] == 'insert' ? '' : 'color: red;';

            $onclickAction = <<<JS
var elem     = $(this.id),
    formData = Form.serialize(elem.up('tr').down('form'));

elem.up('tr').remove();

new Ajax.Request( '{$url}' , {
    method: 'get',
    asynchronous : true,
    parameters : formData
});
JS;
            $html .= <<<HTML
<tr>
    <td>{$row['table']}</td>
    <td>{$row['item']['group']}</td>
    <td>{$row['item']['key']}</td>
    <td>
        <form style="margin-bottom: 0;">
            <input type="checkbox" name="cells[]" value="group" style="display: none;" checked="checked">
            <input type="checkbox" name="cells[]" value="key" style="display: none;" checked="checked">
            <input type="checkbox" name="cells[]" value="value" style="display: none;" checked="checked">

            <input type="hidden" name="value_group" value="{$row['item']['group']}">
            <input type="hidden" name="value_key" value="{$row['item']['key']}">
            <input type="text" name="value_value" value="{$row['item']['value']}">
        </form>
    </td>
    <td align="center">
        <a id="insert_id_{$index}" style= "{$styles}"
           onclick="{$onclickAction}" href="javascript:void(0);">{$actionWord}</a>
    </td>
</tr>
HTML;
        }

        $html .= '</table>';
        return str_replace('%count%',count($differenses),$html);
    }

    // ---------------------------------------

    //todo implement
    /**
     * @hidden
     */
    public function fixColumnAction()
    {
        $tableName  = $this->getRequest()->getParam('table_name');
        $columnInfo = $this->getRequest()->getParam('column_info');
        $columnInfo = (array)json_decode($columnInfo, true);

        $repairMode = $this->getRequest()->getParam('mode');

        if (!$tableName || !$repairMode) {
            return $this->_redirect('*/*/checkTablesStructureValidity');
        }

        $helper = $this->getHelper('Module\Database\Repair');
        $repairMode == 'index' && $helper->fixColumnIndex($tableName, $columnInfo);
        $repairMode == 'properties' && $helper->fixColumnProperties($tableName, $columnInfo);
        $repairMode == 'drop' && $helper->dropColumn($tableName, $columnInfo);

        return $this->_redirect('*/*/checkTablesStructureValidity');
    }

    //todo implement
    /**
     * @title "Files Diff"
     * @description "Files Diff"
     * @hidden
     */
    public function filesDiffAction()
    {
        $filePath     = base64_decode($this->getRequest()->getParam('filePath'));
        $originalPath = base64_decode($this->getRequest()->getParam('originalPath'));

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);
        $directoryReader = $this->directoryReaderFactory->create($path);

        $params = array(
            'content' => $directoryReader->read($filePath),
            'path'    => $originalPath ? $originalPath : $filePath
        );

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('files','get','diff', $params);

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

        $output = shell_exec('php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:static-content:deploy');
        return '<pre>' . $output;
    }

    /**
     * @title "Run Magento Compilation"
     * @description "Run Magento Compilation"
     */
    public function runCompilationAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        $output = shell_exec('php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:di:compile');
        return '<pre>' . $output;
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        return <<<HTML
<h2 style="margin: 20px 0 0 10px">
    {$messageText} <span style="color: grey; font-size: 10px;">
    <a href="{$backUrl}">[back]</a>
</h2>
HTML;
    }

    //########################################
}