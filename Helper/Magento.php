<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

use \Magento\Deploy\Package\Package;

/**
 * Class \Ess\M2ePro\Helper\Magento
 */
class Magento extends \Ess\M2ePro\Helper\AbstractHelper
{
    const CLOUD_COMPOSER_KEY        = 'magento/magento-cloud-metapackage';
    const CLOUD_SERVER_KEY          = 'MAGENTO_CLOUD_APPLICATION';
    const APPLICATION_CLOUD_NICK    = 'cloud';
    const APPLICATION_PERSONAL_NICK = 'personal';

    const ENTERPRISE_EDITION_NICK   = 'enterprise';
    const COMMUNITY_EDITION_NICK    = 'community';

    const MAGENTO_INVENTORY_MODULE_NICK = 'Magento_Inventory';

    protected $deploymentVersionStorageFile;
    protected $filesystem;
    protected $themeResolver;
    protected $productMetadata;
    protected $resource;
    protected $moduleList;
    protected $deploymentConfig;
    protected $cronScheduleFactory;
    protected $localeResolver;
    protected $appState;
    protected $translatedLists;
    protected $countryFactory;
    protected $notificationFactory;
    protected $entityStore;
    protected $objectManager;
    protected $appCache;
    protected $eventConfig;
    protected $sequenceManager;
    protected $composerInformation;

    //########################################

    public function __construct(
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
        \Magento\AdminNotification\Model\InboxFactory $notificationFactory,
        \Magento\Eav\Model\Entity\Store $entityStore,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\CacheInterface $appCache,
        \Magento\Framework\Event\Config $eventConfig,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Composer\ComposerInformation $composerInformation,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->deploymentVersionStorageFile = $deploymentVersionStorageFile;
        $this->filesystem                   = $filesystem;
        $this->themeResolver                = $themeResolver;
        $this->productMetadata              = $productMetadata;
        $this->resource                     = $resource;
        $this->moduleList                   = $moduleList;
        $this->deploymentConfig             = $deploymentConfig;
        $this->cronScheduleFactory          = $scheduleFactory;
        $this->localeResolver               = $localeResolver;
        $this->appState                     = $appState;
        $this->translatedLists              = $translatedLists;
        $this->countryFactory               = $countryFactory;
        $this->notificationFactory          = $notificationFactory;
        $this->entityStore                  = $entityStore;
        $this->objectManager                = $objectManager;
        $this->appCache                     = $appCache;
        $this->eventConfig                  = $eventConfig;
        $this->sequenceManager              = $sequenceManager;
        $this->composerInformation          = $composerInformation;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getName()
    {
        return 'magento';
    }

    public function getVersion($asArray = false)
    {
        $versionString = $this->productMetadata->getVersion();
        return $asArray ? explode('.', $versionString) : $versionString;
    }

    /**
     * @return bool
     */
    public function isMSISupportingVersion()
    {
        return $this->moduleList->getOne(self::MAGENTO_INVENTORY_MODULE_NICK) !== null;
    }

    public function getRevision()
    {
        return 'undefined';
    }

    //########################################

    public function getEditionName()
    {
        return strtolower($this->productMetadata->getEdition());
    }

    // ---------------------------------------

    public function isEnterpriseEdition()
    {
        return $this->getEditionName() == self::ENTERPRISE_EDITION_NICK;
    }

    public function isCommunityEdition()
    {
        return $this->getEditionName() == self::COMMUNITY_EDITION_NICK;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->isApplicationCloud() ?
                self::APPLICATION_CLOUD_NICK :
                self::APPLICATION_PERSONAL_NICK;
    }

    /**
     * @return bool
     */
    public function isApplicationCloud()
    {
        return $this->hasComposerCloudSign() || $this->hasServerCloudSign();
    }

    /**
     * @return bool
     */
    private function hasComposerCloudSign()
    {
        return $this->composerInformation->isPackageInComposerJson(self::CLOUD_COMPOSER_KEY);
    }

    /**
     * @return bool
     */
    private function hasServerCloudSign()
    {
        if ($this->_request instanceof \Magento\Framework\App\Request\Http) {
            return $this->_request->getServer(self::CLOUD_SERVER_KEY) !== null;
        }

        return false;
    }

    //########################################

    public function isDeveloper()
    {
        return $this->appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER;
    }

    public function isProduction()
    {
        return $this->appState->getMode() == \Magento\Framework\App\State::MODE_PRODUCTION;
    }

    public function isDefault()
    {
        return $this->appState->getMode() == \Magento\Framework\App\State::MODE_DEFAULT;
    }

    public function isCronWorking()
    {
        $minDateTime = new \DateTime(
            $this->getHelper('Data')->getCurrentGmtDate(),
            new \DateTimeZone('UTC')
        );
        $minDateTime->modify('-1 day');
        $minDateTime = $this->getHelper('Data')->getDate($minDateTime->format('U'));

        $collection = $this->cronScheduleFactory->create()->getCollection();
        $collection->addFieldToFilter('executed_at', ['gt' => $minDateTime]);

        return $collection->getSize() > 0;
    }

    // ---------------------------------------

    public function getAreas()
    {
        return [
            \Magento\Framework\App\Area::AREA_GLOBAL,
            \Magento\Framework\App\Area::AREA_ADMIN,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            \Magento\Framework\App\Area::AREA_ADMINHTML,
            \Magento\Framework\App\Area::AREA_CRONTAB,
        ];
    }

    public function getBaseUrl()
    {
        return str_replace('index.php/', '', $this->_urlBuilder->getBaseUrl());
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
        return $this->_urlBuilder->getSecretKey();
    }

    // ---------------------------------------

    public function addGlobalNotification(
        $title,
        $description,
        $type = \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL,
        $url = null
    ) {
        $dataForAdd = [
            'title' => $title !== null ? $title : $this->getHelper('Module\Translation')->__('M2E Pro Notification'),
            'description' => $description,
            'url' => $url !== null ? $url : 'http://m2epro.com/?' . sha1($title !== null ? $title : $description),
            'severity' => $type,
            'date_added' => date('Y-m-d H:i:s')
        ];

        $this->notificationFactory->create()->parse([$dataForAdd]);
    }

    //########################################

    public function isStaticContentExists($path = null)
    {
        $directoryReader = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::STATIC_VIEW
        );

        $basePath = $this->getThemePath() .DIRECTORY_SEPARATOR. $this->getLocale() .DIRECTORY_SEPARATOR . $path;
        $exist = $directoryReader->isExist($basePath);

        if (!$exist) {
            $basePath = $this->themeResolver->get()->getArea() .DIRECTORY_SEPARATOR.
                        Package::BASE_THEME .DIRECTORY_SEPARATOR. Package::BASE_LOCALE .DIRECTORY_SEPARATOR . $path;

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

        return $deployedTimeStamp ? $this->getHelper('Data')->getDate($deployedTimeStamp) : false;
    }

    //########################################

    public function getCountries()
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

    public function getRegionsByCountryCode($countryCode)
    {
        $result = [];

        try {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
        } catch (\Exception $e) {
            return $result;
        }

        if (!$country->getId()) {
            return $result;
        }

        $result = [];
        foreach ($country->getRegions() as $region) {
            /** @var \Magento\Directory\Model\Region $region */
            $result[] = $region->toArray(['region_id', 'code', 'name']);
        }

        if (empty($result) && $countryCode == 'AU') {
            $result = [
                ['region_id' => '','code' => 'NSW','name' => 'New South Wales'],
                ['region_id' => '','code' => 'QLD','name' => 'Queensland'],
                ['region_id' => '','code' => 'SA','name' => 'South Australia'],
                ['region_id' => '','code' => 'TAS','name' => 'Tasmania'],
                ['region_id' => '','code' => 'VIC','name' => 'Victoria'],
                ['region_id' => '','code' => 'WA','name' => 'Western Australia'],
            ];
        } elseif (empty($result) && $countryCode == 'GB') {
            $result = [
                ['region_id' => '','code' => 'UKH','name' => 'East of England'],
                ['region_id' => '','code' => 'UKF','name' => 'East Midlands'],
                ['region_id' => '','code' => 'UKI','name' => 'London'],
                ['region_id' => '','code' => 'UKC','name' => 'North East'],
                ['region_id' => '','code' => 'UKD','name' => 'North West'],
                ['region_id' => '','code' => 'UKJ','name' => 'South East'],
                ['region_id' => '','code' => 'UKK','name' => 'South West'],
                ['region_id' => '','code' => 'UKG','name' => 'West Midlands'],
                ['region_id' => '','code' => 'UKE','name' => 'Yorkshire and the Humber'],
            ];
        }

        return $result;
    }

    //########################################

    public function getMySqlTables()
    {
        return $this->resource->getConnection()->listTables();
    }

    // ---------------------------------------

    public function getDatabaseName()
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

    //########################################

    public function isInstalled()
    {
        return $this->deploymentConfig->isAvailable();
    }

    //########################################

    public function getModules()
    {
        return array_keys((array)$this->deploymentConfig->get('modules'));
    }

    // ---------------------------------------

    public function getConflictedModules()
    {
        $modules = $this->moduleList->getAll();

        $conflictedModules = [];

        $result = [];
        foreach ($conflictedModules as $expression => $description) {
            foreach ($modules as $module => $data) {
                if (preg_match($expression, $module)) {
                    $result[$module] = array_merge($data, ['description' => $description]);
                }
            }
        }

        return $result;
    }

    public function getAllEventObservers()
    {
        $eventObservers = [];

        /** @var \Magento\Framework\Config\ScopeInterface $scope */
        $scope = $this->objectManager->get(\Magento\Framework\Config\ScopeInterface::class);

        foreach ($this->getAreas() as $area) {
            $scope->setCurrentScope($area);

            $eventsData = $this->objectManager->create(\Magento\Framework\Event\Config\Data::class, [
                'configScope' => $scope
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

    //########################################

    public function getNextMagentoOrderId()
    {
        $sequence = $this->sequenceManager->getSequence(
            \Magento\Sales\Model\Order::ENTITY,
            $this->getHelper('Magento\Store')->getDefaultStoreId()
        );

        return $sequence->getNextValue();
    }

    //########################################

    public function clearMenuCache()
    {
        return $this->appCache->clean([\Magento\Backend\Block\Menu::CACHE_TAGS]);
    }

    public function clearCache()
    {
        return $this->appCache->clean();
    }

    //########################################
}
