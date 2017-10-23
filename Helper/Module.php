<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Module extends AbstractHelper
{
    const IDENTIFIER = 'Ess_M2ePro';

    const SERVER_MESSAGE_TYPE_NOTICE  = 0;
    const SERVER_MESSAGE_TYPE_ERROR   = 1;
    const SERVER_MESSAGE_TYPE_WARNING = 2;
    const SERVER_MESSAGE_TYPE_SUCCESS = 3;

    const ENVIRONMENT_PRODUCTION  = 'production';
    const ENVIRONMENT_DEVELOPMENT = 'development';
    const ENVIRONMENT_TESTING     = 'testing';

    const DEVELOPMENT_MODE_COOKIE_KEY = 'm2epro_development_mode';

    protected $activeRecordFactory;
    protected $moduleConfig;
    protected $cacheConfig;
    protected $primaryConfig;
    protected $moduleList;
    protected $cookieMetadataFactory;
    protected $cookieManager;
    protected $packageInfo;
    protected $moduleResource;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleConfig = $moduleConfig;
        $this->cacheConfig = $cacheConfig;
        $this->primaryConfig = $primaryConfig;
        $this->moduleList = $moduleList;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->packageInfo = $packageInfo;
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($dbContext);

        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Config\Manager\Module
     */
    public function getConfig()
    {
        return $this->moduleConfig;
    }

    /**
     * @return \Ess\M2ePro\Model\Config\Manager\Cache
     */
    public function getCacheConfig()
    {
        return $this->cacheConfig;
    }

    //########################################

    public function getName()
    {
        return 'm2epro-m2';
    }

    //########################################

    public function getPublicVersion()
    {
        return '1.3.2';
    }

    public function getSetupVersion()
    {
        return $this->getConfigSetupVersion();
    }

    public function getFilesVersion()
    {
        return $this->getComposerVersion();
    }

    // ---------------------------------------

    public function getConfigSetupVersion()
    {
        return $this->moduleList->getOne(self::IDENTIFIER)['setup_version'];
    }

    public function getMagentoSetupVersion()
    {
        // returns only data version because we do not manage schema upgrades separately
        return $this->moduleResource->getDataVersion(self::IDENTIFIER);
    }

    public function getComposerVersion()
    {
        return $this->packageInfo->getVersion(self::IDENTIFIER);
    }

    // ---------------------------------------

    public function getRevision()
    {
        return '2356';
    }

    //########################################

    public function getInstallationKey()
    {
        return $this->primaryConfig->getGroupValue('/server/', 'installation_key');
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

    public function getPublicVersionInstallationDate()
    {
        $collection = $this->activeRecordFactory->getObject('PublicVersions')->getCollection();
        $collection->addFieldToFilter('version_from', ['null' => true])
                   ->addFieldToFilter('version_to', ['notnull' => true])
                   ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
        return $collection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    public function getPublicVersionLastUpgradeDate()
    {
        $collection = $this->activeRecordFactory->getObject('PublicVersions')->getCollection();
        $collection->addFieldToFilter('version_from', ['notnull' => true])
                   ->addFieldToFilter('version_to', ['notnull' => true])
                   ->setOrder('id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
        return $collection->setPageSize(1)->getFirstItem()->getUpdateDate();
    }

    // ---------------------------------------

    public function getPublicVersionLastModificationDate()
    {
        $resource = $this->activeRecordFactory->getObject('VersionsHistory')->getResource();
        return $resource->getLastItem()->getCreateDate();
    }

    //########################################

    public function isDisabled()
    {
        return (bool)$this->getConfig()->getGroupValue(NULL, 'is_disabled');
    }

    //########################################

    public function isReadyToWork()
    {
        return $this->getHelper('Component')->getEnabledComponents() &&
                ($this->getHelper('View\Ebay')->isInstallationWizardFinished() ||
                 $this->getHelper('View\Amazon')->isInstallationWizardFinished());
    }

    // ---------------------------------------

    public function isDevelopmentEnvironment()
    {
        return (string)getenv('M2EPRO_ENV') == self::ENVIRONMENT_DEVELOPMENT;
    }

    public function isTestingEnvironment()
    {
        return (string)getenv('M2EPRO_ENV') == self::ENVIRONMENT_TESTING;
    }

    public function isProductionEnvironment()
    {
        return (string)getenv('M2EPRO_ENV') == self::ENVIRONMENT_PRODUCTION ||
               (!$this->isDevelopmentEnvironment() && !$this->isTestingEnvironment());
    }

    // ---------------------------------------

    public function isDevelopmentMode()
    {
        return $this->cookieManager->getCookie(self::DEVELOPMENT_MODE_COOKIE_KEY);
    }

    public function isProductionMode()
    {
        return !$this->isDevelopmentMode();
    }

    // ---------------------------------------

    public function setDevelopmentMode($value)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                                                      ->setPath('/')
                                                      ->setHttpOnly(true);
        if ($value) {
            $cookieMetadata->setDurationOneYear();
            $this->cookieManager->setPublicCookie(self::DEVELOPMENT_MODE_COOKIE_KEY, 'true', $cookieMetadata);
        } else {
            $this->cookieManager->deleteCookie(self::DEVELOPMENT_MODE_COOKIE_KEY, $cookieMetadata);
        }
    }

    // ---------------------------------------

    public function isStaticContentDeployed()
    {
        $staticContentValidationResult = $this->getHelper('Data\Cache\Runtime')->getValue(__METHOD__);

        if (!is_null($staticContentValidationResult)) {
            return $staticContentValidationResult;
        }

        $result = true;

        /** @var \Ess\M2ePro\Helper\Magento $magentoHelper */
        $magentoHelper = $this->getHelper('Magento');
        $moduleDir = \Ess\M2ePro\Helper\Module::IDENTIFIER . DIRECTORY_SEPARATOR;

        if (!$magentoHelper->isStaticContentExists($moduleDir.'css') ||
            !$magentoHelper->isStaticContentExists($moduleDir.'fonts') ||
            !$magentoHelper->isStaticContentExists($moduleDir.'images') ||
            !$magentoHelper->isStaticContentExists($moduleDir.'js')) {

            $result = false;
        }

        $this->getHelper('Data\Cache\Runtime')->setValue(__METHOD__, $result);
        return $result;
    }

    //########################################

    public function getRequirementsInfo()
    {
        $clientPhpData = $this->getHelper('Client')->getPhpSettings();

        $requirements = [

            'memory_limit' => [
                'title' => $this->getHelper('Module\Translation')->__('Memory Limit'),
                'condition' => [
                    'sign' => '>=',
                    'value' => '768 MB'
                ],
                'current' => [
                    'value' => (int)$clientPhpData['memory_limit'] . ' MB',
                    'status' => true
                ]
            ],

            'max_execution_time' => [
                'title' => $this->getHelper('Module\Translation')->__('Max Execution Time'),
                'condition' => [
                    'sign' => '>=',
                    'value' => '360 sec'
                ],
                'current' => [
                    'value' => is_null($clientPhpData['max_execution_time'])
                        ? 'unknown' : $clientPhpData['max_execution_time'] . ' sec',
                    'status' => true
                ]
            ]
        ];

        foreach ($requirements as $key => &$requirement) {

            // max execution time is unlimited or fcgi handler
            if ($key == 'max_execution_time' &&
                ($clientPhpData['max_execution_time'] == 0 || is_null($clientPhpData['max_execution_time']))) {
                continue;
            }

            $requirement['current']['status'] = version_compare(
                $requirement['current']['value'],
                $requirement['condition']['value'],
                $requirement['condition']['sign']
            );
        }

        return $requirements;
    }

    //########################################

    public function getServerMessages()
    {
        /** @var \Ess\M2ePro\Model\Registry $registryModel */
        $registryModel = $this->activeRecordFactory->getObjectLoaded('Registry', '/server/messages/', 'key', false);

        if (is_null($registryModel)) {
            return [];
        }

        $messages = $registryModel->getValueFromJson();

        $messages = array_filter($messages, [$this,'getServerMessagesFilterModuleMessages']);
        !is_array($messages) && $messages = [];

        return $messages;
    }

    public function getServerMessagesFilterModuleMessages($message)
    {
        if (!isset($message['text']) || !isset($message['type'])) {
            return false;
        }

        return true;
    }

    //########################################

    public function clearConfigCache()
    {
        $this->cacheConfig->clear();
    }

    public function clearCache()
    {
        $this->getHelper('Data\Cache\Permanent')->removeAllValues();
    }

    //########################################
}