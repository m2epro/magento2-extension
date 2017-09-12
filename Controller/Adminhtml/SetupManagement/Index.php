<?php

namespace Ess\M2ePro\Controller\Adminhtml\SetupManagement;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Setup\Model\Cron;
use Magento\Framework\App\Filesystem\DirectoryList;
use Ess\M2ePro\Setup\LoggerFactory;

class Index extends \Magento\Backend\App\Action
{
    const AUTHORIZATION_COOKIE_NAME  = '_auth_';
    const AUTHORIZATION_COOKIE_VALUE = 'secure';

    /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    protected $moduleResource;

    /** @var \Magento\Framework\Module\ModuleListInterface $moduleList */
    protected $moduleList;

    /** @var \Magento\Framework\Module\PackageInfo $packageInfo */
    protected $packageInfo;

    /** @var \Magento\Framework\Locale\ResolverInterface $localeResolver */
    protected $localeResolver;

    /** @var \Magento\Framework\View\Design\Theme\ResolverInterface $themeResolver */
    protected $themeResolver;

    /** @var \Magento\Framework\App\Filesystem\DirectoryList $directoryList */
    protected $directoryList;

    /** @var \Magento\Framework\App\CacheInterface $appCache */
    protected $appCache;

    /** @var \Magento\Framework\App\Cache\State $cacheState */
    protected $cacheState;

    /** @var \Magento\Framework\App\State $appState */
    protected $appState;

    /** @var \Magento\Framework\Filesystem $fileSystem */
    protected $fileSystem;

    /** @var \Magento\Framework\App\DeploymentConfig $deploymentConfig */
    protected $deploymentConfig;

    /** @var \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager */
    protected $cookieManager;

    /** @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieManager */
    protected $cookieMetadataFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\View\Design\Theme\ResolverInterface $themeResolver,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\CacheInterface $appCache,
        \Magento\Framework\App\Cache\State $cacheState,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->resourceConnection = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->moduleResource     = new \Magento\Framework\Module\ModuleResource($dbContext);

        $this->moduleList            = $moduleList;
        $this->packageInfo           = $packageInfo;
        $this->localeResolver        = $context->getLocaleResolver();
        $this->themeResolver         = $themeResolver;
        $this->directoryList         = $directoryList;
        $this->appCache              = $appCache;
        $this->cacheState            = $cacheState;
        $this->appState              = $appState;
        $this->fileSystem            = $filesystem;
        $this->deploymentConfig      = $deploymentConfig;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    //########################################

    public function execute()
    {
        if (!$this->isAuthorized()) {
            return $this->authorizeAction();
        }

        $action     = $this->getRequest()->getParam('action', 'index');
        $methodName = $action.'Action';

        if (!method_exists($this, $methodName)) {
            return $this->_redirect($this->_url->getBaseUrl());
        }

        return $this->$methodName();
    }

    protected function _validateSecretKey()
    {
        return true;
    }

    //########################################

    public function indexAction()
    {
        $response = <<<HTML

{$this->getGeneralCss()}

<body style="padding: 15px;">

<h2>Setup Management</h2>

<div id="messages">
    {$this->getMessagesBlockHtml()}
</div>

<div>
    <div style="float: left; width: 45%; margin-right: 15px;">

        <div class="item-block">
            <h3>Core Magento Info</h3>
            {$this->getCoreMagentoInfoBlockHtml()}
        </div>

        <div class="item-block">
            <h3>Core Magento Config</h3>
            {$this->getCoreMagentoConfigBlockHtml()}
        </div>

        <div class="item-block">
            <h3>Core Magento Setup</h3>
            {$this->getCoreMagentoSetupBlockHtml()}
        </div>

        <div class="item-block">
            <h3>M2E Pro Physical Versions</h3>
            {$this->getM2eProPhysicalVersionsBlockHtml()}
        </div>

        <div class="item-block">
            <h3>Core Magento Upgrade Cron</h3>
            {$this->getCoreMagentoUpgradeBlockHtml()}
        </div>

        <div class="item-block">
            <h3>M2E Pro Setup Versions</h3>
            {$this->getM2eProSetupBlockHtml()}
        </div>

        <div class="item-block">
            <h3>M2E Pro Public Versions</h3>
            {$this->getM2eProFilesVersionsHistoryBlock()}
        </div>

        <div class="item-block">
            <h3>Additional Possibilities</h3>
            {$this->getAdditionalPossibilitiesBlockHtml()}
        </div>

    </div>

    <div style="float: left; width: 50%; margin-right: 15px;">

        <div class="item-block">
            <h3>M2E Pro Tables</h3>
            {$this->getM2eProTablesBlockHtml()}
        </div>

    </div>
</div>

</body>
HTML;
        $this->getResponse()->setContent($response);
    }

    //----------------------------------------

    private function getGeneralCss()
    {
        $srcPath = str_replace('index.php/', '', $this->_url->getBaseUrl()) .
                   $this->directoryList->getUrlPath(DirectoryList::STATIC_VIEW) .'/'.
                   $this->themeResolver->get()->getFullPath() .'/'.
                   $this->localeResolver->getDefaultLocale();

        return <<<HTML
<head>
    <link rel="stylesheet" type="text/css"  media="all" href="{$srcPath}/css/styles.css" />
</head>

<style>

    a { color: grey; }
    h3 { margin-bottom: 0.75rem; }

    .item-block {
        margin: 10px;
        margin-bottom: 17px;
    }
    .feature-disabled {
        color: green;
    }
    .feature-enabled {
        color: red;
        font-weight: bold;
    }
    .feature-enabled-word:after { content: "Enabled"; }
    .feature-disabled-word:after { content: "Disabled"; }

    .m2epro-setup-grid-container > div,
    .m2epro-files-versions-grid-container > div {
        display: inline-block;
        width: 50px;
    }
    .m2epro-setup-grid-container .editable:hover {
        cursor: pointer;
        color: orange;
        font-weight: bold;
        background-color: #d7d7d7;
        transition: background-color 0.5s;
    }

</style>

<script>

    function SetupManagementActionHandler() {

        this.confirmImportantAction = function(promptString, successUrlAction)
        {
            var operand1 = Math.floor(Math.random() * (9 - 1 + 1)) + 1,
                operand2 = Math.floor(Math.random() * (9 - 1 + 1)) + 1;

            var result = prompt(promptString + ' Confirm that: ' +operand1+ ' + ' +operand2+ ' = ');

            if (result != operand1 + operand2) {
                alert('Wrong!');
                return false;
            }

            document.location = successUrlAction;
        };

        this.askAdditionalParametersForAction = function(promptString, url, placeHolder)
        {
            var result = prompt(promptString);

            if (result == null) {
                return false;
            }

            url = url.replace(encodeURIComponent('#') +placeHolder+ encodeURIComponent('#'), result);
            document.location = url;
        }
    }

    var handlerObj = new SetupManagementActionHandler();

</script>
HTML;
    }

    private function getMessagesBlockHtml()
    {
        $html = '<div class="messages">';

        foreach ($this->getMessageManager()->getMessages(true)->getItems() as $message) {

            $classes = '';
            $message->getType() == MessageInterface::TYPE_SUCCESS && $classes = 'message-success success';
            $message->getType() == MessageInterface::TYPE_ERROR   && $classes = 'message-error error';
            $message->getType() == MessageInterface::TYPE_WARNING && $classes = 'message-warning warning';

            $subContainerClasses = explode(' ', $classes);

            $html .= <<<HTML
<div class="message {$classes}">
    <div data-ui-id="messages-{$subContainerClasses[0]}">
        {$message->getText()}
    </div>
</div>
HTML;
        }

        $html .= '</div>';
        return $html;
    }

    private function getCoreMagentoInfoBlockHtml()
    {
        $html = "<div>";

        $applicationState = $this->appState->getMode();
        $memoryLimit      = trim(ini_get('memory_limit'));

        $phpVersion = explode(' ', shell_exec('php -v'))[1];

        $html .= <<<HTML
<span>Application mode: <span style="font-weight: bold;">{$applicationState}</span></span><br>
<span>Memory limit: <span>{$memoryLimit}</span></span><br>
<span>CLI PHP version: <span>{$phpVersion}</span></span><br>
HTML;

        if (!$this->cacheState->isEnabled(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER)) {
            $html .= <<<HTML
<span>Config cache: <span class="feature-enabled">Disabled</span><br>
HTML;
        }

        $html .= "</div>";
        return $html;
    }

    private function getCoreMagentoConfigBlockHtml()
    {
        $html = "<div>";

        $maintenanceMode = (bool)$this->getMagentoCoreConfigValue('m2epro/general/maintenance');
        $className = $maintenanceMode ? 'feature-enabled feature-enabled-word'
                                      : 'feature-disabled feature-disabled-word';
        $url = $this->_url->getUrl('*/*/*', ['action' => 'setMagentoCoreConfigValue',
                                             '_query' => [
                                                 'config_path'  => 'm2epro/general/maintenance',
                                                 'config_value' => (int)!$maintenanceMode
                                             ]]);
        $html .= <<<HTML
<span>Maintenance mode: <span class="{$className}"></span></span>&nbsp;
<a href="{$url}">[change]</a><br>
HTML;

        $isMaintenanceCanBeIgnored = (bool)$this->getMagentoCoreConfigValue('m2epro/setup/ignore_maintenace');
        $className = $isMaintenanceCanBeIgnored ? 'feature-enabled feature-enabled-word'
                                                : 'feature-disabled feature-disabled-word';
        $url = $this->_url->getUrl('*/*/*', ['action' => 'setMagentoCoreConfigValue',
                                             '_query' => [
                                                 'config_path'  => 'm2epro/setup/ignore_maintenace',
                                                 'config_value' => (int)!$isMaintenanceCanBeIgnored
                                             ]]);
        $html .= <<<HTML
<span>Maintenance mode can be ignored: <span class="{$className}"></span></span>&nbsp;
<a href="{$url}">[change]</a><br>
HTML;

        $isAllowedToRollback = (bool)$this->getMagentoCoreConfigValue('m2epro/setup/allow_rollback_from_backup');
        $className = $isAllowedToRollback ? 'feature-enabled feature-enabled-word'
                                          : 'feature-disabled feature-disabled-word';
        $url = $this->_url->getUrl('*/*/*', ['action' => 'setMagentoCoreConfigValue',
                                             '_query' => [
                                                 'config_path'  => 'm2epro/setup/allow_rollback_from_backup',
                                                 'config_value' => (int)!$isAllowedToRollback
                                             ]]);

        $html .= <<<HTML
<span>Allowed to rollback backup: <span class="{$className}"></span></span>&nbsp;
<a href="{$url}">[change]</a><br>
HTML;

        $migrationM1Status = $this->getMagentoCoreConfigValue('m2epro/migrationFromMagento1/status');
        if (!empty($migrationM1Status)) {
            $className = 'feature-enabled';
        } else {
            $migrationM1Status = 'none';
            $className = 'feature-disabled';
        }

        $url = $this->_url->getUrl('*/*/*', ['action' => 'setMagentoCoreConfigValue',
                                             '_query' => [
                                                 'config_path'  => 'm2epro/migrationFromMagento1/status',
                                                 'config_value' => 'prepared'
                                             ]]);

        $html .= <<<HTML
<br>
<span>Migration from M1 status: <span class="{$className}">{$migrationM1Status}</span></span>&nbsp;
<a href="{$url}">[set-prepared]</a><br>
HTML;

        $html .= "</div>";
        return $html;
    }

    private function getCoreMagentoSetupBlockHtml()
    {
        $schemaVersion = $this->moduleResource->getDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER);
        $dataVersion   = $this->moduleResource->getDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER);
        $setupVersion  = $this->moduleList->getOne(\Ess\M2ePro\Helper\Module::IDENTIFIER)['setup_version'];

        $html = "<div>";

        $html .= <<<HTML
<span>Config setup version: <span></span>{$setupVersion}</span><br>
HTML;

        $className = '';
        if (version_compare($schemaVersion, $setupVersion) < 0 ||
            version_compare($dataVersion, $setupVersion)) {

            $className = 'feature-enabled';
        }

        $dropUrl = $this->_url->getUrl('*/*/*', ['action' => 'dropMagentoCoreSetupValue']);
        $editUrl = $this->_url->getUrl('*/*/*', ['action' => 'setMagentoCoreSetupValue',
                                                 '_query' => [
                                                     'version' => '#version#',
                                                 ]]);

        !$schemaVersion && $schemaVersion = 'none';
        !$dataVersion   && $dataVersion = 'none';

        $html .=  <<<HTML
<span>Schema \ Data Version: <span class="{$className}">{$schemaVersion} \ {$dataVersion}</span></span>&nbsp;
<a href="javascript:void(0);"
   onclick="return handlerObj.askAdditionalParametersForAction('Please specify version to set:',
                                                               '{$editUrl}', 'version'); ">
   <span>[change]</span>
</a>&nbsp;

<a href="javascript:void(0);"
   onclick="return handlerObj.confirmImportantAction('This row will be DROPPED.', '{$dropUrl}'); ">
    <span>[drop]</span>
</a>
<br>
HTML;

        $html .= "</div>";
        return $html;
    }

    private function getCoreMagentoUpgradeBlockHtml()
    {
        $html = '<div>';

        $queue = $this->getCronJobQueue();
        $isExistCronStatusFile = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)
                                      ->isExist(Cron\ReadinessCheck::SETUP_CRON_JOB_STATUS_FILE);

        if (!$isExistCronStatusFile) {
            $html .= <<<HTML
<span class="feature-enabled">Setup cron is not working.</span><br>
HTML;
        }

        if (empty($queue[Cron\Queue::KEY_JOBS])) {
            $html .= <<<HTML
<span class="feature-disabled">Cron queue is empty.</span><br>
HTML;
        } else {

            $html .= <<<HTML
<span class="feature-disabled">Cron queue:</span><br>
HTML;
            foreach ($queue[Cron\Queue::KEY_JOBS] as $job) {
                $html .= <<<HTML
<div style="padding-left: 10px;">
    <span>{$job[Cron\Queue::KEY_JOB_NAME]}</span>
</div>
HTML;
            }
        }

        $url = $this->_url->getUrl('*/*/*', ['action' => 'addMagentoCoreUpgradeTask']);
        $html .= <<<HTML
<br><a href="{$url}">[create cron upgrade task]</a><br>
HTML;

        $html .= "</div>";
        return $html;
    }

    private function getM2eProPhysicalVersionsBlockHtml()
    {
        $html = '<div>';

        $composerVersion = $this->packageInfo->getVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER);
        $html .= <<<HTML
<span>Composer version: <span></span>{$composerVersion}</span><br>
HTML;

        $html .= "</div>";
        return $html;
    }

    private function getM2eProTablesBlockHtml()
    {
        $html = "<div>";

        $tablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
        $tables = $this->resourceConnection->getConnection()->getTables($tablesPrefix.'m2epro_%');

        usort($tables, function($a, $b) {

            $aResult = strpos($a, '__b_');
            $bResult = strpos($b, '__b_');

            if ($aResult > 0 && $bResult === false) {
                return -1;
            }

            if ($bResult > 0 && $aResult === false) {
                return 1;
            }

            return $a == $b ? 0 : ($a < $b ? -1 : 1);
        });

        foreach ($tables as $table) {

            $count = $this->resourceConnection->getConnection()
                ->select()
                ->from($table, new \Zend_Db_Expr('COUNT(*)'))
                ->query()
                ->fetchColumn();

            $truncateUrl = $this->_url->getUrl('*/*/*', ['action' => 'truncateM2eProTable',
                                                         '_query' => [
                                                            'table_name' => $table,
                                                         ]]);

            $dropUrl = $this->_url->getUrl('*/*/*', ['action' => 'dropM2eProTable',
                                                     '_query' => [
                                                         'table_name' => $table,
                                                     ]]);

            $nameClasses = '';
            strpos($table, '_backup_') && $nameClasses = 'feature-enabled';

            $countStyles = 'color: green;';
            $count > 0 && $countStyles .= ' font-weight: bold;';

            $html .= <<<HTML
<span class="{$nameClasses}">{$table}&nbsp;
<span style="{$countStyles}">[{$count}]</span></span>&nbsp;

<a href="javascript:void(0);"
   onclick="return handlerObj.confirmImportantAction('Table will be TRUNCATED.', '{$truncateUrl}'); ">
   <span>[truncate]</span>
</a>&nbsp;

<a href="javascript:void(0);"
   onclick="return handlerObj.confirmImportantAction('Table will be DROPPED.', '{$dropUrl}'); ">
   <span>[drop]</span>
</a>&nbsp;
<br>
HTML;
        }

        $html .= "</div>";
        return $html;
    }

    private function getM2eProSetupBlockHtml()
    {
        $html = "<div>";

        $tableName = $this->resourceConnection->getTableName('m2epro_setup');
        if (!$this->resourceConnection->getConnection()->isTableExists($tableName)) {
            $html .= <<<HTML
<span class="feature-enabled">m2epro_setup table is not installed.</span>
HTML;
            $html .= "</div>";
            return $html;
        }

        $queryStmt = $this->resourceConnection->getConnection()
            ->select()
            ->from($tableName)
            ->order('id DESC')
            ->query();

        $html .= <<<HTML
<div class="m2epro-setup-grid-container">
    <div><span>id</span></div>
    <div><span>from</span></div>
    <div><span>to</span></div>
    <div><span>backup</span></div>
    <div><span>ok</span></div>
    <div style="width: 150px;"><span>create_date</span></div>
    <div style="width: 150px;"><span>update_date</span></div>
</div>
HTML;

        while ($row = $queryStmt->fetch()) {

            $dropUrl = $this->_url->getUrl('*/*/*', ['action' => 'dropM2eProSetupRow',
                                                    '_query' => [
                                                        'id' => $row['id'],
                                                    ]]);

            $editUrl = $this->_url->getUrl('*/*/*', ['action' => 'updateM2eProSetupRow',
                                                     '_query' => [
                                                         'id'    => $row['id'],
                                                         'value' => '#value#'
                                                     ]]);

            $html .= <<<HTML
<div class="m2epro-setup-grid-container">
    <div>
        <input type="hidden" name="id" value="{$row['id']}">
        <span>{$row['id']}</span>
    </div>

    <div><span>{$row['version_from']}</span></div>
    <div><span>{$row['version_to']}</span></div>

    <div class="editable" title="edit"
         onclick="return handlerObj.askAdditionalParametersForAction('Please specify a new value to set:',
                                                                     '{$editUrl}&column=is_backuped', 'value'); ">
        <span>{$row['is_backuped']}</span>
    </div>

    <div class="editable" title="edit"
         onclick="return handlerObj.askAdditionalParametersForAction('Please specify a new value to set:',
                                                                     '{$editUrl}&column=is_completed', 'value'); ">
        <span>{$row['is_completed']}</span>
    </div>

    <div style="width: 150px;"><span>{$row['create_date']}</span></div>
    <div style="width: 150px;"><span>{$row['update_date']}</span></div>

    <div>
        <a href="javascript:void(0);"
           onclick="return handlerObj.confirmImportantAction('This row will be DROPPED.', '{$dropUrl}');">[drop]</a>
    </div>
</div>
HTML;
        }

        $html .= "</div>";
        return $html;
    }

    private function getM2eProFilesVersionsHistoryBlock()
    {
        $html = "<div>";

        $tableName = $this->resourceConnection->getTableName('m2epro_versions_history');
        if (!$this->resourceConnection->getConnection()->isTableExists($tableName)) {
            $html .= <<<HTML
<span class="feature-enabled">m2epro_versions_history table is not installed.</span>
HTML;
            $html .= "</div>";
            return $html;
        }

        $queryStmt = $this->resourceConnection->getConnection()
            ->select()
            ->from($tableName)
            ->order('id DESC')
            ->query();

        $html .= <<<HTML
<div class="m2epro-files-versions-grid-container">
    <div><span>id</span></div>
    <div style="width: 100px;"><span>from</span></div>
    <div style="width: 100px;"><span>to</span></div>
    <div style="width: 300px;"><span>create_date</span></div>
</div>
HTML;

        while ($row = $queryStmt->fetch()) {

            $html .= <<<HTML
<div class="m2epro-files-versions-grid-container">
    <div>
        <input type="hidden" name="id" value="{$row['id']}">
        <span>{$row['id']}</span>
    </div>

    <div style="width: 100px;"><span>{$row['version_from']}</span></div>
    <div style="width: 100px;"><span>{$row['version_to']}</span></div>

    <div style="width: 300px;"><span>{$row['create_date']}</span></div>
</div>
HTML;
        }

        $html .= "</div>";
        return $html;
    }

    private function getAdditionalPossibilitiesBlockHtml()
    {
        $html = "<div>";

        $clearCacheUrl   = $this->_url->getUrl('*/*/*', ['action' => 'clearCache']);
        $controlPanelUrl = $this->_url->getUrl('*/controlPanel/index');

        $html .= <<<HTML
<a href="{$clearCacheUrl}">[clear magento cache]</a><br>
<a href="{$controlPanelUrl}">[M2E Pro Control Panel]</a><br>
<br>
HTML;

        $url = $this->_url->getUrl('*/*/*', ['action' => 'runUpdateTask']);
        $html .= <<<HTML
<a href="{$url}">[shell: setup:upgrade]</a><br>
HTML;

        $url = $this->_url->getUrl('*/*/*', ['action' => 'runCompilation']);
        $html .= <<<HTML
<a href="{$url}">[shell: setup:di:compile]</a><br>
HTML;

        $url = $this->_url->getUrl('*/*/*', ['action' => 'runStaticContentDeploy']);
        $html .= <<<HTML
<a href="{$url}">[shell: setup:static-content:deploy]</a><br>
HTML;

        $isExistSetupLogFile = $this->fileSystem->getDirectoryWrite(DirectoryList::LOG)
            ->isExist('m2epro' .DIRECTORY_SEPARATOR. LoggerFactory::LOGFILE_NAME);

        if ($isExistSetupLogFile) {

            $download = $this->_url->getUrl('*/*/*', ['action' => 'downloadSetupLogFile']);
            $remove   = $this->_url->getUrl('*/*/*', ['action' => 'removeSetupLogFile']);
            $html .= <<<HTML
<br>
<a href="{$download}">[download setup-log-file]</a><br>
<a href="{$remove}">[remove setup-log-file]</a><br>
HTML;
        }

        $html .= "</div>";
        return $html;
    }

    //########################################

    private function getMagentoCoreConfigValue($path)
    {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', $path);

        return $this->resourceConnection->getConnection()->fetchOne($select);
    }

    public function setMagentoCoreConfigValueAction()
    {
        $path  = $this->getRequest()->getParam('config_path');
        $value = $this->getRequest()->getParam('config_value');

        if (!in_array($path, [
            'm2epro/general/maintenance',
            'm2epro/setup/ignore_maintenace',
            'm2epro/setup/allow_rollback_from_backup',

            'm2epro/migrationFromMagento1/status'
        ])) {

            $this->messageManager->addErrorMessage("This config path is not supported [{$path}].");
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        if ($this->getMagentoCoreConfigValue($path) === false) {

            $this->resourceConnection->getConnection()->insert(
                $this->resourceConnection->getTableName('core_config_data'),
                [
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => $path,
                    'value'    => $value
                ]
            );

        } else {

            $this->resourceConnection->getConnection()->update(
                $this->resourceConnection->getTableName('core_config_data'),
                ['value' => $value],
                [
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => $path,
                ]
            );
        }

        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //----------------------------------------

    public function setMagentoCoreSetupValueAction()
    {
        $version = $this->getRequest()->getParam('version');

        if (!$version) {

            $this->messageManager->addErrorMessage('Version is not provided.');
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);

        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    public function dropMagentoCoreSetupValueAction()
    {
        $this->resourceConnection->getConnection()->delete(
            $this->moduleResource->getMainTable(),
            ['module = ?' => \Ess\M2ePro\Helper\Module::IDENTIFIER]
        );

        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //----------------------------------------

    public function addMagentoCoreUpgradeTaskAction()
    {
        // Static Content will regenerated automatically
        $this->addCronJobToQueue(Cron\JobFactory::JOB_UPGRADE);

        empty($errorMessage) ? $this->getMessageManager()->addSuccessMessage('Task has been successfully created.')
                             : $this->getMessageManager()->addErrorMessage($errorMessage);

        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    private function getCronJobQueue()
    {
        $fileName = '.update_queue.json';
        $directory = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

        if (!$directory->isExist($fileName)) {
            return [];
        }

        $jobs = (array)json_decode($directory->readFile($fileName), true);
        return $jobs;
    }

    private function addCronJobToQueue($jobName, array $jobParams = [])
    {
        $jobs = $this->getCronJobQueue();
        $jobs[Cron\Queue::KEY_JOBS][] = ['name' => $jobName, 'params' => $jobParams];

        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->writeFile('.update_queue.json', json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    //----------------------------------------

    public function truncateM2eProTableAction()
    {
        $tableName    = $this->getRequest()->getParam('table_name');
        $tablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (strpos($tableName, $tablesPrefix.'m2epro_') !== 0) {

            $this->getMessageManager()->addErrorMessage("Only M2E Pro tables are supported. Table: [{$tableName}].");
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        $this->resourceConnection->getConnection()
             ->truncateTable($tableName);

        $this->getMessageManager()->addSuccessMessage("Successfully truncated [{$tableName}].");
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    public function dropM2eProTableAction()
    {
        $tableName    = $this->getRequest()->getParam('table_name');
        $tablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (strpos($tableName, $tablesPrefix.'m2epro_') !== 0) {

            $this->getMessageManager()->addErrorMessage("Only M2E Pro tables are supported. Table: [{$tableName}].");
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        $this->resourceConnection->getConnection()
             ->dropTable($tableName);

        $this->getMessageManager()->addSuccessMessage("Successfully dropped [{$tableName}].");
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //----------------------------------------

    public function dropM2eProSetupRowAction()
    {
        if (!$id = $this->getRequest()->getParam('id')) {

            $this->getMessageManager()->addErrorMessage('Row id is not specified.');
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        $this->resourceConnection->getConnection()
             ->delete($this->resourceConnection->getTableName('m2epro_setup'),
                      ['id = ?' => (int)$id]);

        $this->getMessageManager()->addSuccessMessage('Successfully removed.');
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    public function updateM2eProSetupRowAction()
    {
        $id     = $this->getRequest()->getParam('id');
        $column = $this->getRequest()->getParam('column');
        $value  = $this->getRequest()->getParam('value');

        if (!$id || is_null($column) || is_null($value)) {

            $this->getMessageManager()->addErrorMessage('Some required data is not specified.');
            return $this->_redirect($this->_url->getUrl('*/*/*'));
        }

        $this->resourceConnection->getConnection()
            ->update($this->resourceConnection->getTableName('m2epro_setup'),
                     [$column => $value],
                     ['id = ?' => (int)$id]);

        $this->getMessageManager()->addSuccessMessage('Successfully updated.');
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //----------------------------------------

    public function runUpdateTaskAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        $output = shell_exec('php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:upgrade');
        $this->getResponse()->setContent('<pre>' . $output);
    }

    public function runCompilationAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        $output = shell_exec('php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:di:compile');
        $this->getResponse()->setContent('<pre>' . $output);
    }

    public function runStaticContentDeployAction()
    {
        $magentoRoot = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                        ->getAbsolutePath();

        $output = shell_exec('php ' . $magentoRoot . DIRECTORY_SEPARATOR . 'bin/magento setup:static-content:deploy');
        $this->getResponse()->setContent('<pre>' . $output);
    }

    //----------------------------------------

    public function downloadSetupLogFileAction()
    {
        $filePath = $this->fileSystem->getDirectoryWrite(DirectoryList::LOG)->getAbsolutePath() .
                   'm2epro' .DIRECTORY_SEPARATOR. LoggerFactory::LOGFILE_NAME;

        $this->getResponse()->setHeader('Content-type', 'text/plain; charset=UTF-8');
        $this->getResponse()->setHeader('Content-length', filesize($filePath));
        $this->getResponse()->setHeader('Content-Disposition', 'attachment' . '; filename=' .basename($filePath));

        $this->getResponse()->setContent(file_get_contents($filePath));
    }

    public function removeSetupLogFileAction()
    {
        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::LOG);
        $directory->delete('m2epro' .DIRECTORY_SEPARATOR. LoggerFactory::LOGFILE_NAME);

        $this->getMessageManager()->addSuccessMessage('Successfully removed.');
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //----------------------------------------

    public function clearCacheAction()
    {
        $this->appCache->clean();

        $this->messageManager->addSuccessMessage('Cache has been successfully cleared.');
        return $this->_redirect($this->_url->getUrl('*/*/*'));
    }

    //########################################

    private function isAuthorized()
    {
        $cookie = $this->cookieManager->getCookie(self::AUTHORIZATION_COOKIE_NAME);
        return $cookie == self::AUTHORIZATION_COOKIE_VALUE;
    }

    private function authorizeAction()
    {
        if ($this->getRequest()->getParam(self::AUTHORIZATION_COOKIE_NAME) == self::AUTHORIZATION_COOKIE_VALUE) {

            $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                                   ->setPath('/')
                                   ->setSecure($this->getRequest()->isSecure())
                                   ->setDuration(3600);

            $this->cookieManager->setPublicCookie(self::AUTHORIZATION_COOKIE_NAME,
                                                  self::AUTHORIZATION_COOKIE_VALUE,
                                                  $cookieMetadata);

            return $this->_redirect($this->getUrl('*/*/*'));
        }

        $cookieName     = self::AUTHORIZATION_COOKIE_NAME;
        $expectedResult = self::AUTHORIZATION_COOKIE_VALUE;

        $this->getResponse()->setContent(<<<HTML
<script>
    var result = prompt('You are not authorized. Please provide an access key:');

    if (result == '{$expectedResult}') {

        var expiresDate = new Date();
        expiresDate.setMinutes(expiresDate.getMinutes() + 120);

        document.cookie = '{$cookieName}={$expectedResult}; path=/; expires=' + expiresDate.toUTCString();
        location.reload();

    } else if (result != null) {

        alert('Wrong access key. Please try again.');
        location.reload();
    }
</script>
HTML
        );
    }

    //########################################
}