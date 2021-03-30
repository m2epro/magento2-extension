<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

use Magento\Framework\Component\ComponentRegistrar;

/**
 * Class \Ess\M2ePro\Helper\Module
 */
class Module extends AbstractHelper
{
    const IDENTIFIER = 'Ess_M2ePro';

    const MESSAGE_TYPE_NOTICE  = 0;
    const MESSAGE_TYPE_ERROR   = 1;
    const MESSAGE_TYPE_WARNING = 2;
    const MESSAGE_TYPE_SUCCESS = 3;

    const ENVIRONMENT_PRODUCTION     = 'production';
    const ENVIRONMENT_DEVELOPMENT    = 'development';
    const ENVIRONMENT_TESTING_MANUAL = 'testing-manual';
    const ENVIRONMENT_TESTING_AUTO   = 'testing-auto';

    protected $activeRecordFactory;
    protected $config;
    protected $registry;
    protected $moduleList;
    protected $cookieMetadataFactory;
    protected $cookieManager;
    protected $packageInfo;
    protected $moduleResource;
    protected $resourceConnection;
    protected $componentRegistrar;

    /** @var \Magento\Backend\Model\UrlInterface $urlBuilder */
    protected $urlBuilder;

    protected $areImportantTablesExist;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->config = $config;
        $this->registry = $registry;
        $this->moduleList = $moduleList;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->packageInfo = $packageInfo;
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($dbContext);
        $this->resourceConnection = $resourceConnection;
        $this->componentRegistrar = $componentRegistrar;
        $this->urlBuilder = $urlBuilder;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Config\Manager
     */
    public function getConfig()
    {
        return $this->config;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Registry\Manager
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    //########################################

    public function getName()
    {
        return 'm2epro-m2';
    }

    //########################################

    public function getPublicVersion()
    {
        return $this->packageInfo->getVersion(self::IDENTIFIER);
    }

    public function getSetupVersion()
    {
        return $this->moduleList->getOne(self::IDENTIFIER)['setup_version'];
    }

    public function getSchemaVersion()
    {
        return $this->moduleResource->getDbVersion(self::IDENTIFIER);
    }

    public function getDataVersion()
    {
        return $this->moduleResource->getDataVersion(self::IDENTIFIER);
    }

    //########################################

    public function getInstallationKey()
    {
        return $this->config->getGroupValue('/', 'installation_key');
    }

    //########################################

    public function getSetupInstallationDate()
    {
        $setupCollection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $setupCollection->addFieldToFilter('version_from', ['null' => true])
            ->addFieldToFilter('version_to', ['notnull' => true])
            ->addFieldToFilter('is_completed', 1)
            ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);

        return $setupCollection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    public function getSetupLastUpgradeDate()
    {
        $setupCollection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $setupCollection->addFieldToFilter('version_from', ['notnull' => true])
            ->addFieldToFilter('version_to', ['notnull' => true])
            ->addFieldToFilter('is_completed', 1)
            ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC);

        return $setupCollection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    //########################################

    public function isDisabled()
    {
        return (bool)$this->getConfig()->getGroupValue('/', 'is_disabled');
    }

    //########################################

    public function isReadyToWork()
    {
        return $this->areImportantTablesExist() &&
            $this->getHelper('Component')->getEnabledComponents() &&
            ($this->getHelper('View\Ebay')->isInstallationWizardFinished() ||
                $this->getHelper('View\Amazon')->isInstallationWizardFinished() ||
                $this->getHelper('View\Walmart')->isInstallationWizardFinished());
    }

    public function areImportantTablesExist()
    {
        if ($this->areImportantTablesExist !== null) {
            return $this->areImportantTablesExist;
        }

        foreach (['m2epro_config', 'm2epro_setup'] as $table) {
            $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($table);
            if (!$this->resourceConnection->getConnection()->isTableExists($tableName)) {
                return $this->areImportantTablesExist = false;
            }
        }

        return $this->areImportantTablesExist = true;
    }

    // ---------------------------------------

    public function getEnvironment()
    {
        return $this->getConfig()->getGroupValue('/', 'environment');
    }

    public function isProductionEnvironment()
    {
        return $this->getEnvironment() === null || $this->getEnvironment() === self::ENVIRONMENT_PRODUCTION;
    }

    public function isDevelopmentEnvironment()
    {
        return $this->getEnvironment() === self::ENVIRONMENT_DEVELOPMENT;
    }

    public function isTestingManualEnvironment()
    {
        return $this->getEnvironment() === self::ENVIRONMENT_TESTING_MANUAL;
    }

    public function isTestingAutoEnvironment()
    {
        return $this->getEnvironment() === self::ENVIRONMENT_TESTING_AUTO;
    }

    public function setEnvironment($env)
    {
        $this->getConfig()->setGroupValue('/', 'environment', $env);
    }

    // ---------------------------------------

    public function isStaticContentDeployed()
    {
        $staticContentValidationResult = $this->getHelper('Data_Cache_Runtime')->getValue(__METHOD__);

        if ($staticContentValidationResult !== null) {
            return $staticContentValidationResult;
        }

        $result = true;

        /** @var \Ess\M2ePro\Helper\Magento $magentoHelper */
        $magentoHelper = $this->getHelper('Magento');
        $moduleDir = \Ess\M2ePro\Helper\Module::IDENTIFIER . DIRECTORY_SEPARATOR;

        if (!$magentoHelper->isStaticContentExists($moduleDir . 'css') ||
            !$magentoHelper->isStaticContentExists($moduleDir . 'fonts') ||
            !$magentoHelper->isStaticContentExists($moduleDir . 'images') ||
            !$magentoHelper->isStaticContentExists($moduleDir . 'js')) {
            $result = false;
        }

        $this->getHelper('Data_Cache_Runtime')->setValue(__METHOD__, $result);

        return $result;
    }

    //########################################

    public function getServerMessages()
    {
        $messages = $this->getRegistry()->getValueFromJson('/server/messages/');

        $messages = array_filter($messages, [$this, 'getMessagesFilterModuleMessages']);
        !is_array($messages) && $messages = [];

        return $messages;
    }

    public function getUpgradeMessages()
    {
        $messages = $this->getRegistry()->getValueFromJson('/upgrade/messages/');

        $messages = array_filter($messages, [$this, 'getMessagesFilterModuleMessages']);
        !is_array($messages) && $messages = [];

        foreach ($messages as &$message) {

            preg_match_all('/%[\w\d]+%/', $message['text'], $placeholders);
            $placeholders = array_unique($placeholders[0]);

            foreach ($placeholders as $placeholder) {
                $key = substr(substr($placeholder, 1), 0, -1);
                if (!isset($message[$key])) {
                    continue;
                }

                if (!strripos($placeholder, 'url')) {
                    $message['text'] = str_replace($placeholder, $message[$key], $message['text']);
                    continue;
                }

                $message[$key] = $this->urlBuilder->getUrl(
                    $message[$key],
                    isset($message[$key . '_args']) ? $message[$key . '_args'] : null
                );

                $message['text'] = str_replace($placeholder, $message[$key], $message['text']);
            }
        }
        unset($message);

        return $messages;
    }

    public function getMessagesFilterModuleMessages($message)
    {
        if (!isset($message['text']) || !isset($message['type'])) {
            return false;
        }

        return true;
    }

    //########################################

    public function getBaseRelativeDirectory()
    {
        return str_replace(
            $this->getHelper('Client')->getBaseDirectory(),
            '',
            $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, \Ess\M2ePro\Helper\Module::IDENTIFIER)
        );
    }

    //########################################

    public function clearCache()
    {
        $this->getHelper('Data_Cache_Permanent')->removeAllValues();
    }

    //########################################
}
