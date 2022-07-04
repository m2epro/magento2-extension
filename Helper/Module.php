<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

use Magento\Framework\Component\ComponentRegistrar;

class Module
{
    public const IDENTIFIER = 'Ess_M2ePro';

    public const MESSAGE_TYPE_NOTICE  = 0;
    public const MESSAGE_TYPE_ERROR   = 1;
    public const MESSAGE_TYPE_WARNING = 2;
    public const MESSAGE_TYPE_SUCCESS = 3;

    public const ENVIRONMENT_PRODUCTION     = 'production';
    public const ENVIRONMENT_DEVELOPMENT    = 'development';
    public const ENVIRONMENT_TESTING_MANUAL = 'testing-manual';
    public const ENVIRONMENT_TESTING_AUTO   = 'testing-auto';

    /**  @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    protected $config;

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    protected $registry;

    /** @var \Magento\Framework\Module\ModuleListInterface */
    protected $moduleList;

    /** @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory */
    protected $cookieMetadataFactory;

    /** @var \Magento\Framework\Stdlib\CookieManagerInterface  */
    protected $cookieManager;

    /** @var \Magento\Framework\Module\PackageInfo */
    protected $packageInfo;

    /** @var \Magento\Framework\Module\ModuleResource */
    protected $moduleResource;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Framework\Component\ComponentRegistrar  */
    protected $componentRegistrar;

    /** @var \Magento\Backend\Model\UrlInterface $urlBuilder */
    protected $urlBuilder;
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    protected $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\View\Amazon */
    protected $amazonViewHelper;
    /** @var \Ess\M2ePro\Helper\View\Walmart */
    protected $walmartViewHelper;

    /** @var bool|null */
    protected $areImportantTablesExist;

    /**  @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /**  @var \Ess\M2ePro\Helper\Data\Cache\Runtime */
    private $runtimeCache;

    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCache;
    /**
     * @var \Ess\M2ePro\Helper\Magento
     */
    private $magentoHelper;
    /**
     * @var \Ess\M2ePro\Helper\Client
     */
    private $clientHelper;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory                 $activeRecordFactory
     * @param \Ess\M2ePro\Model\Config\Manager                       $config
     * @param \Ess\M2ePro\Model\Registry\Manager                     $registry
     * @param \Magento\Framework\Module\ModuleListInterface          $moduleList
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface       $cookieManager
     * @param \Magento\Framework\Module\PackageInfo                  $packageInfo
     * @param \Magento\Framework\Model\ResourceModel\Db\Context      $dbContext
     * @param \Magento\Framework\App\ResourceConnection              $resourceConnection
     * @param \Magento\Framework\Component\ComponentRegistrar        $componentRegistrar
     * @param \Magento\Backend\Model\UrlInterface                    $urlBuilder
     * @param \Ess\M2ePro\Helper\View\Ebay                           $ebayViewHelper
     * @param \Ess\M2ePro\Helper\View\Amazon                         $amazonViewHelper
     * @param \Ess\M2ePro\Helper\View\Walmart                        $walmartViewHelper
     * @param \Ess\M2ePro\Helper\Component                           $componentHelper
     * @param \Ess\M2ePro\Helper\Module\Database\Structure           $databaseHelper
     * @param \Ess\M2ePro\Helper\Data\Cache\Runtime                  $runtimeCache
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent                $permanentCache
     * @param \Ess\M2ePro\Helper\Magento                             $magentoHelper
     * @param \Ess\M2ePro\Helper\Client                              $clientHelper
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data\Cache\Runtime $runtimeCache,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Client $clientHelper
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
        $this->ebayViewHelper = $ebayViewHelper;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->walmartViewHelper = $walmartViewHelper;
        $this->componentHelper = $componentHelper;
        $this->databaseHelper = $databaseHelper;
        $this->runtimeCache = $runtimeCache;
        $this->permanentCache = $permanentCache;
        $this->magentoHelper = $magentoHelper;
        $this->clientHelper = $clientHelper;
    }

    // ----------------------------------------

    /**
     * @deprecated use explicitly
     * @return \Ess\M2ePro\Model\Config\Manager
     */
    public function getConfig(): \Ess\M2ePro\Model\Config\Manager
    {
        return $this->config;
    }

    /**
     * * @deprecated use explicitly
     * @return \Ess\M2ePro\Model\Registry\Manager
     */
    public function getRegistry(): \Ess\M2ePro\Model\Registry\Manager
    {
        return $this->registry;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'm2epro-m2';
    }

    /**
     * @return string
     */
    public function getPublicVersion(): string
    {
        return $this->packageInfo->getVersion(self::IDENTIFIER);
    }

    /**
     * @return mixed
     */
    public function getSetupVersion()
    {
        return $this->moduleList->getOne(self::IDENTIFIER)['setup_version'];
    }

    /**
     * @return false|mixed|string
     */
    public function getSchemaVersion()
    {
        return $this->moduleResource->getDbVersion(self::IDENTIFIER);
    }

    /**
     * @return false|mixed|string
     */
    public function getDataVersion()
    {
        return $this->moduleResource->getDataVersion(self::IDENTIFIER);
    }

    /**
     * @return mixed|null
     */
    public function getInstallationKey()
    {
        return $this->config->getGroupValue('/', 'installation_key');
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSetupInstallationDate()
    {
        $setupCollection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $setupCollection->addFieldToFilter('version_from', ['null' => true])
            ->addFieldToFilter('version_to', ['notnull' => true])
            ->addFieldToFilter('is_completed', 1)
            ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);

        return $setupCollection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSetupLastUpgradeDate()
    {
        $setupCollection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $setupCollection->addFieldToFilter('version_from', ['notnull' => true])
            ->addFieldToFilter('version_to', ['notnull' => true])
            ->addFieldToFilter('is_completed', 1)
            ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_DESC);

        return $setupCollection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return (bool)$this->config->getGroupValue('/', 'is_disabled');
    }

    /**
     * @return bool
     */
    public function isReadyToWork(): bool
    {
        return $this->areImportantTablesExist() &&
            $this->componentHelper->getEnabledComponents() &&
            ($this->ebayViewHelper->isInstallationWizardFinished() ||
                $this->amazonViewHelper->isInstallationWizardFinished() ||
                $this->walmartViewHelper->isInstallationWizardFinished());
    }

    /**
     * @return bool
     */
    public function areImportantTablesExist(): bool
    {
        if ($this->areImportantTablesExist !== null) {
            return $this->areImportantTablesExist;
        }

        foreach (['m2epro_config', 'm2epro_setup'] as $table) {
            $tableName = $this->databaseHelper->getTableNameWithPrefix($table);
            if (!$this->resourceConnection->getConnection()->isTableExists($tableName)) {
                return $this->areImportantTablesExist = false;
            }
        }

        return $this->areImportantTablesExist = true;
    }

    /**
     * @return mixed|null
     */
    public function getEnvironment()
    {
        return $this->config->getGroupValue('/', 'environment');
    }

    /**
     * @return bool
     */
    public function isProductionEnvironment(): bool
    {
        return $this->getEnvironment() === null
            || $this->getEnvironment() === self::ENVIRONMENT_PRODUCTION;
    }

    /**
     * @return bool
     */
    public function isDevelopmentEnvironment(): bool
    {
        return $this->getEnvironment() === self::ENVIRONMENT_DEVELOPMENT;
    }

    /**
     * @return bool
     */
    public function isTestingManualEnvironment(): bool
    {
        return $this->getEnvironment() === self::ENVIRONMENT_TESTING_MANUAL;
    }

    /**
     * @return bool
     */
    public function isTestingAutoEnvironment(): bool
    {
        return $this->getEnvironment() === self::ENVIRONMENT_TESTING_AUTO;
    }

    /**
     * @param string $env
     *
     * @return void
     */
    public function setEnvironment(string $env): void
    {
        $this->config->setGroupValue('/', 'environment', $env);
    }

    /**
     * @return bool|mixed
     */
    public function isStaticContentDeployed()
    {
        $staticContentValidationResult = $this->runtimeCache->getValue(__METHOD__);

        if ($staticContentValidationResult !== null) {
            return $staticContentValidationResult;
        }

        $result = true;

        $moduleDir = \Ess\M2ePro\Helper\Module::IDENTIFIER . DIRECTORY_SEPARATOR;

        if (!$this->magentoHelper->isStaticContentExists($moduleDir . 'css') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'fonts') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'images') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'js')) {
            $result = false;
        }

        $this->runtimeCache->setValue(__METHOD__, $result);

        return $result;
    }

    /**
     * @return array
     */
    public function getServerMessages(): array
    {
        $messages = $this->getRegistry()->getValueFromJson('/server/messages/');

        $messages = array_filter($messages, [$this, 'getMessagesFilterModuleMessages']);
        !is_array($messages) && $messages = [];

        return $messages;
    }

    /**
     * @return array
     */
    public function getUpgradeMessages(): array
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

    /**
     * @param array $message
     *
     * @return bool
     */
    public function getMessagesFilterModuleMessages($message): bool
    {
        return isset($message['text'], $message['type']);
    }

    /**
     * @return array|mixed|string|string[]|null
     */
    public function getBaseRelativeDirectory()
    {
        return str_replace(
            $this->clientHelper->getBaseDirectory(),
            '',
            $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, \Ess\M2ePro\Helper\Module::IDENTIFIER)
        );
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->permanentCache->removeAllValues();
    }
}
