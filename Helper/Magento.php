<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

use Magento\Deploy\Package\Package;

class Magento
{
    public const CLOUD_COMPOSER_KEY = 'magento/magento-cloud-metapackage';
    public const CLOUD_SERVER_KEY = 'MAGENTO_CLOUD_APPLICATION';
    public const APPLICATION_CLOUD_NICK = 'cloud';
    public const APPLICATION_PERSONAL_NICK = 'personal';

    public const ENTERPRISE_EDITION_NICK = 'enterprise';
    public const COMMUNITY_EDITION_NICK = 'community';

    public const MAGENTO_INVENTORY_MODULE_NICK = 'Magento_Inventory';

    /** @var \Magento\Framework\App\View\Deployment\Version\Storage\File */
    private $deploymentVersionStorageFile;
    /** @var \Magento\Framework\Filesystem */
    private $filesystem;
    /** @var \Magento\Framework\View\Design\Theme\ResolverInterface */
    private $themeResolver;
    /** @var \Magento\Framework\App\ProductMetadataInterface */
    private $productMetadata;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;
    /** @var \Magento\Framework\App\DeploymentConfig */
    private $deploymentConfig;
    /** @var \Magento\Cron\Model\ScheduleFactory */
    private $cronScheduleFactory;
    /** @var \Magento\Framework\Locale\ResolverInterface */
    private $localeResolver;
    /** @var \Magento\Framework\App\State */
    private $appState;
    /** @var \Magento\Framework\Locale\TranslatedLists */
    private $translatedLists;
    /** @var \Magento\Directory\Model\CountryFactory */
    private $countryFactory;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Framework\App\CacheInterface */
    private $appCache;
    /** @var \Magento\SalesSequence\Model\Manager */
    private $sequenceManager;
    /** @var \Magento\Framework\Composer\ComposerInformation */
    private $composerInformation;
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $magentoStoreHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;
    /** @var \Magento\Framework\UrlInterface */
    private $urlBuilder;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper
     * @param \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
     * @param \Magento\Framework\App\View\Deployment\Version\Storage\File $deploymentVersionStorageFile
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\View\Design\Theme\ResolverInterface $themeResolver
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Locale\TranslatedLists $translatedLists
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\CacheInterface $appCache
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param \Magento\Framework\Composer\ComposerInformation $composerInformation
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Magento\Framework\App\View\Deployment\Version\Storage\File $deploymentVersionStorageFile,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\Design\Theme\ResolverInterface $themeResolver,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Locale\TranslatedLists $translatedLists,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\CacheInterface $appCache,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \Magento\Framework\Composer\ComposerInformation $composerInformation
    ) {
        $this->deploymentVersionStorageFile = $deploymentVersionStorageFile;
        $this->filesystem = $filesystem;
        $this->themeResolver = $themeResolver;
        $this->productMetadata = $productMetadata;
        $this->resource = $resource;
        $this->moduleList = $moduleList;
        $this->deploymentConfig = $deploymentConfig;
        $this->cronScheduleFactory = $scheduleFactory;
        $this->localeResolver = $localeResolver;
        $this->appState = $appState;
        $this->translatedLists = $translatedLists;
        $this->countryFactory = $countryFactory;
        $this->objectManager = $objectManager;
        $this->appCache = $appCache;
        $this->sequenceManager = $sequenceManager;
        $this->composerInformation = $composerInformation;
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'magento';
    }

    /**
     * @param $asArray
     *
     * @return string|string[]
     */
    public function getVersion($asArray = false)
    {
        $versionString = $this->productMetadata->getVersion();

        return $asArray ? explode('.', $versionString) : $versionString;
    }

    /**
     * @return bool
     */
    public function isMSISupportingVersion(): bool
    {
        return $this->moduleList->getOne(self::MAGENTO_INVENTORY_MODULE_NICK) !== null;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getEditionName(): string
    {
        return strtolower($this->productMetadata->getEdition());
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isEnterpriseEdition(): bool
    {
        return $this->getEditionName() == self::ENTERPRISE_EDITION_NICK;
    }

    /**
     * @return bool
     */
    public function isCommunityEdition(): bool
    {
        return $this->getEditionName() == self::COMMUNITY_EDITION_NICK;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->isApplicationCloud() ?
            self::APPLICATION_CLOUD_NICK :
            self::APPLICATION_PERSONAL_NICK;
    }

    /**
     * @return bool
     */
    public function isApplicationCloud(): bool
    {
        return $this->hasComposerCloudSign() || $this->hasServerCloudSign();
    }

    /**
     * @return bool
     */
    private function hasComposerCloudSign(): bool
    {
        return $this->composerInformation->isPackageInComposerJson(self::CLOUD_COMPOSER_KEY);
    }

    /**
     * @return bool
     */
    private function hasServerCloudSign(): bool
    {
        if ($this->request instanceof \Magento\Framework\App\Request\Http) {
            return $this->request->getServer(self::CLOUD_SERVER_KEY) !== null;
        }

        return false;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isDeveloper(): bool
    {
        return $this->appState->getMode() === \Magento\Framework\App\State::MODE_DEVELOPER;
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->appState->getMode() === \Magento\Framework\App\State::MODE_PRODUCTION;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->appState->getMode() === \Magento\Framework\App\State::MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isCronWorking(): bool
    {
        $minDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $minDateTime->modify('-1 day');
        $minDateTime = $minDateTime->format('Y-m-d H:i:s');

        $collection = $this->cronScheduleFactory->create()->getCollection();
        $collection->addFieldToFilter('executed_at', ['gt' => $minDateTime]);

        return $collection->getSize() > 0;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getAreas(): array
    {
        return [
            \Magento\Framework\App\Area::AREA_GLOBAL,
            \Magento\Framework\App\Area::AREA_ADMIN,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            \Magento\Framework\App\Area::AREA_ADMINHTML,
            \Magento\Framework\App\Area::AREA_CRONTAB,
        ];
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return str_replace('index.php/', '', $this->urlBuilder->getBaseUrl());
    }

    public function getLocale()
    {
        return $this->localeResolver->getLocale();
    }

    public function getLocaleCode()
    {
        $localeComponents = explode('_', $this->getLocale());

        return strtolower(array_shift($localeComponents));
    }

    public function getDefaultLocale()
    {
        return $this->localeResolver->getDefaultLocale();
    }

    public function getBaseCurrency()
    {
        return (string)$this->scopeConfig->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    // ---------------------------------------

    public function getThemePath()
    {
        return $this->themeResolver->get()->getFullPath();
    }

    // ---------------------------------------

    public function isSecretKeyToUrl()
    {
        return (bool)$this->scopeConfig->getValue(
            \Magento\Config\Model\Config\Backend\Admin\Custom::XML_PATH_ADMIN_SECURITY_USEFORMKEY,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    public function getCurrentSecretKey()
    {
        if (!$this->isSecretKeyToUrl()) {
            return '';
        }

        return $this->urlBuilder->getSecretKey();
    }

    // ----------------------------------------

    public function isStaticContentExists($path = null)
    {
        $directoryReader = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::STATIC_VIEW
        );

        $basePath = $this->getThemePath() . DIRECTORY_SEPARATOR . $this->getLocale() . DIRECTORY_SEPARATOR . $path;
        $exist = $directoryReader->isExist($basePath);

        if (!$exist) {
            $basePath = $this->themeResolver->get()->getArea() . DIRECTORY_SEPARATOR .
                Package::BASE_THEME . DIRECTORY_SEPARATOR . Package::BASE_LOCALE . DIRECTORY_SEPARATOR . $path;

            $exist = $directoryReader->isExist($basePath);
        }

        return $exist;
    }

    public function getLastStaticContentDeployDate()
    {
        try {
            $deployedTimeStamp = $this->deploymentVersionStorageFile->load();
        } catch (\Exception $e) {
            return false;
        }

        return $deployedTimeStamp ? gmdate('Y-m-d H:i:s', $deployedTimeStamp) : false;
    }

    // ----------------------------------------

    public function getCountries(): array
    {
        return $this->countryFactory->create()->getCollection()->toOptionArray();
    }

    // ---------------------------------------

    public function getTranslatedCountryName($countryId, $localeCode = 'en_US')
    {
        if ($this->localeResolver->getLocale() != $localeCode) {
            $this->localeResolver->setLocale($localeCode);
        }

        return $this->translatedLists->getCountryTranslation($countryId);
    }

    /**
     * @param $countryCode
     *
     * @return array
     */
    public function getRegionsByCountryCode($countryCode)
    {
        try {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
        } catch (\Exception $e) {
            $this->exceptionHelper->process($e);

            return [];
        }

        if (!$country->getId()) {
            return [];
        }

        $result = [];
        foreach ($country->getRegions() as $region) {
            /** @var \Magento\Directory\Model\Region $region */
            $result[] = [
                'region_id' => $region->getRegionId(),
                'code'      => $region->getCode(),
                'name'      => $region->getName(),
            ];
        }

        if (empty($result) && $countryCode == 'AU') {
            $result = [
                ['region_id' => '', 'code' => 'NSW', 'name' => 'New South Wales'],
                ['region_id' => '', 'code' => 'QLD', 'name' => 'Queensland'],
                ['region_id' => '', 'code' => 'SA', 'name' => 'South Australia'],
                ['region_id' => '', 'code' => 'TAS', 'name' => 'Tasmania'],
                ['region_id' => '', 'code' => 'VIC', 'name' => 'Victoria'],
                ['region_id' => '', 'code' => 'WA', 'name' => 'Western Australia'],
            ];
        } elseif (empty($result) && $countryCode == 'GB') {
            $result = [
                ['region_id' => '', 'code' => 'UKH', 'name' => 'East of England'],
                ['region_id' => '', 'code' => 'UKF', 'name' => 'East Midlands'],
                ['region_id' => '', 'code' => 'UKI', 'name' => 'London'],
                ['region_id' => '', 'code' => 'UKC', 'name' => 'North East'],
                ['region_id' => '', 'code' => 'UKD', 'name' => 'North West'],
                ['region_id' => '', 'code' => 'UKJ', 'name' => 'South East'],
                ['region_id' => '', 'code' => 'UKK', 'name' => 'South West'],
                ['region_id' => '', 'code' => 'UKG', 'name' => 'West Midlands'],
                ['region_id' => '', 'code' => 'UKE', 'name' => 'Yorkshire and the Humber'],
            ];
        }

        return $result;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getMySqlTables(): array
    {
        return $this->resource->getConnection()->listTables();
    }

    // ---------------------------------------

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function getDatabaseName(): string
    {
        return (string)$this->deploymentConfig->get(
            \Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
            . '/dbname'
        );
    }

    public function getDatabaseTablesPrefix()
    {
        return (string)$this->deploymentConfig->get(
            \Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
        );
    }

    // ----------------------------------------

    public function isInstalled()
    {
        return $this->deploymentConfig->isAvailable();
    }

    public function getModules()
    {
        return array_keys((array)$this->deploymentConfig->get('modules'));
    }

    public function getAllEventObservers()
    {
        $eventObservers = [];

        /** @var \Magento\Framework\Config\ScopeInterface $scope */
        $scope = $this->objectManager->get(\Magento\Framework\Config\ScopeInterface::class);

        foreach ($this->getAreas() as $area) {
            $scope->setCurrentScope($area);

            $eventsData = $this->objectManager->create(\Magento\Framework\Event\Config\Data::class, [
                'configScope' => $scope,
            ]);

            foreach ($eventsData->get(null) as $eventName => $eventData) {
                foreach ($eventData as $observerName => $observerData) {
                    $observerName = '#class#::#method#';

                    if (!empty($observerData['instance'])) {
                        $observerName = str_replace('#class#', $observerData['instance'], $observerName);
                    }

                    $observerMethod = !empty($observerData['method']) ? $observerData['method'] : 'execute';
                    $observerName = str_replace('#method#', $observerMethod, $observerName);
                    $eventObservers[$area][$eventName][] = $observerName;
                }
            }
        }

        return $eventObservers;
    }

    // ----------------------------------------

    public function getNextMagentoOrderId()
    {
        $sequence = $this->sequenceManager->getSequence(
            \Magento\Sales\Model\Order::ENTITY,
            $this->magentoStoreHelper->getDefaultStoreId()
        );

        return $sequence->getNextValue();
    }

    // ----------------------------------------

    public function clearMenuCache()
    {
        return $this->appCache->clean([\Magento\Backend\Block\Menu::CACHE_TAGS]);
    }

    public function clearCache()
    {
        return $this->appCache->clean();
    }
}
