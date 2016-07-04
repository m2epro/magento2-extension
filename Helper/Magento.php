<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Magento extends \Ess\M2ePro\Helper\AbstractHelper
{
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
    protected $eavConfig;
    protected $entityStore;
    protected $objectManager;
    protected $appCache;
    protected $eventConfig;

    //########################################

    public function __construct(
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
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\Entity\Store $entityStore,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\CacheInterface $appCache,
        \Magento\Framework\Event\Config $eventConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->productMetadata = $productMetadata;
        $this->resource = $resource;
        $this->moduleList = $moduleList;
        $this->deploymentConfig = $deploymentConfig;
        $this->cronScheduleFactory = $scheduleFactory;
        $this->localeResolver = $localeResolver;
        $this->appState = $appState;
        $this->translatedLists = $translatedLists;
        $this->countryFactory = $countryFactory;
        $this->notificationFactory = $notificationFactory;
        $this->eavConfig = $eavConfig;
        $this->entityStore = $entityStore;
        $this->objectManager = $objectManager;
        $this->appCache = $appCache;
        $this->eventConfig = $eventConfig;
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
        return $asArray ? explode('.',$versionString) : $versionString;
    }

    public function getRevision()
    {
        return 'undefined';
    }

    //########################################

    public function getEditionName()
    {
        if ($this->isProfessionalEdition()) {
            return 'professional';
        }
        if ($this->isEnterpriseEdition()) {
            return 'enterprise';
        }
        if ($this->isCommunityEdition()) {
            return 'community';
        }

        return 'undefined';
    }

    // ---------------------------------------

    public function isProfessionalEdition()
    {
        //TODO
//        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') &&
//               !Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') &&
//               !Mage::getConfig()->getModuleConfig('Enterprise_Checkout') &&
//               !Mage::getConfig()->getModuleConfig('Enterprise_Customer');
        return false;
    }

    public function isEnterpriseEdition()
    {
        //TODO
//        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') &&
//               Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') &&
//               Mage::getConfig()->getModuleConfig('Enterprise_Checkout') &&
//               Mage::getConfig()->getModuleConfig('Enterprise_Customer');
        return false;
    }

    public function isCommunityEdition()
    {
        return !$this->isProfessionalEdition() &&
               !$this->isEnterpriseEdition();
    }

    //########################################

    public function isDeveloper()
    {
        return $this->appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER;
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
        return array(
            \Magento\Framework\App\Area::AREA_GLOBAL,
            \Magento\Framework\App\Area::AREA_ADMIN,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            \Magento\Framework\App\Area::AREA_ADMINHTML,
            \Magento\Framework\App\Area::AREA_CRONTAB,
        );
    }

    public function getBaseUrl()
    {
        return str_replace('index.php/','',$this->_urlBuilder->getBaseUrl());
    }

    public function getLocale()
    {
        $localeComponents = explode('_' , $this->localeResolver->getLocale());
        return strtolower(array_shift($localeComponents));
    }

    public function getBaseCurrency()
    {
        return (string)$this->scopeConfig->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
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

    public function addGlobalNotification($title,
                                          $description,
                                          $type = \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL,
                                          $url = NULL)
    {
        $dataForAdd = [
            'title' => $title,
            'description' => $description,
            'url' => !is_null($url) ? $url : 'http://m2epro.com/?'.sha1($title),
            'severity' => $type,
            'date_added' => date('Y-m-d H:i:s')
        ];

        $this->notificationFactory->create()->parse([$dataForAdd]);
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
        $result = array();

        try {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
        } catch (\Exception $e) {
            return $result;
        }

        if (!$country->getId()) {
            return $result;
        }

        $result = array();
        foreach ($country->getRegions() as $region) {
            /** @var \Magento\Directory\Model\Region $region */
            $result[] = $region->toArray(array('region_id', 'code', 'name'));
        }

        if (empty($result) && $countryCode == 'AU') {
            $result = array(
                array('region_id' => '','code' => 'NSW','name' => 'New South Wales'),
                array('region_id' => '','code' => 'QLD','name' => 'Queensland'),
                array('region_id' => '','code' => 'SA','name' => 'South Australia'),
                array('region_id' => '','code' => 'TAS','name' => 'Tasmania'),
                array('region_id' => '','code' => 'VIC','name' => 'Victoria'),
                array('region_id' => '','code' => 'WA','name' => 'Western Australia'),
            );
        } else if (empty($result) && $countryCode == 'GB') {
            $result = array(
                array('region_id' => '','code' => 'UKH','name' => 'East of England'),
                array('region_id' => '','code' => 'UKF','name' => 'East Midlands'),
                array('region_id' => '','code' => 'UKI','name' => 'London'),
                array('region_id' => '','code' => 'UKC','name' => 'North East'),
                array('region_id' => '','code' => 'UKD','name' => 'North West'),
                array('region_id' => '','code' => 'UKJ','name' => 'South East'),
                array('region_id' => '','code' => 'UKK','name' => 'South West'),
                array('region_id' => '','code' => 'UKG','name' => 'West Midlands'),
                array('region_id' => '','code' => 'UKE','name' => 'Yorkshire and the Humber'),
            );
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

    public function getModules()
    {
        return array_keys((array)$this->deploymentConfig->get('modules'));
    }

    // ---------------------------------------

    public function getConflictedModules()
    {
        $modules = $this->moduleList->getAll();

        // TODO
        $conflictedModules = [];

        $result = [];
        foreach ($conflictedModules as $expression=>$description) {

            foreach ($modules as $module => $data) {
                if (preg_match($expression, $module)) {
                    $result[$module] = array_merge($data, ['description'=>$description]);
                }
            }
        }

        return $result;
    }

    public function getAllEventObservers()
    {
        $eventObservers = [];

        /** @var \Magento\Framework\Config\ScopeInterface $scope */
        $scope = $this->objectManager->get('\Magento\Framework\Config\ScopeInterface');

        foreach ($this->getAreas() as $area) {

            $scope->setCurrentScope($area);

            $eventsData = $this->objectManager->create('Magento\Framework\Event\Config\Data', [
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
        $orderEntityType = $this->eavConfig->getEntityType('order');
        $defaultStoreId = $this->getHelper('Magento\Store')->getDefaultStoreId();

        if (!$orderEntityType->getIncrementModel()) {
            return false;
        }

        $entityStoreConfig = $this->entityStore->loadByEntityStore(
            $orderEntityType->getId(), $defaultStoreId
        );

        if (!$entityStoreConfig->getId()) {
            $entityStoreConfig
                ->setEntityTypeId($orderEntityType->getId())
                ->setStoreId($defaultStoreId)
                ->setIncrementPrefix($defaultStoreId)
                ->save();
        }

        $incrementInstance = $this->objectManager->create($orderEntityType->getIncrementModel())
            ->setPrefix($entityStoreConfig->getIncrementPrefix())
            ->setPadLength($orderEntityType->getIncrementPadLength())
            ->setPadChar($orderEntityType->getIncrementPadChar())
            ->setLastId($entityStoreConfig->getIncrementLastId())
            ->setEntityTypeId($entityStoreConfig->getEntityTypeId())
            ->setStoreId($entityStoreConfig->getStoreId());

        return $incrementInstance->getNextId();
    }

    public function isMagentoOrderIdUsed($orderId)
    {
        $connRead = $this->resource->getConnection();
        $select    = $connRead->select();

        $table = $this->objectManager->create('Magento\Sales\Model\Order')->getResource()->getMainTable();
        $select->from($table, 'entity_id')->where('increment_id = :increment_id');

        return $connRead->fetchOne($select, array(':increment_id' => $orderId)) > 0;
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