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

    protected $moduleConfig;
    protected $cacheConfig;
    protected $primaryConfig;
    protected $moduleList;
    protected $cookieMetadataFactory;
    protected $cookieManager;
    
    //########################################
    
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->cacheConfig = $cacheConfig;
        $this->primaryConfig = $primaryConfig;
        $this->moduleList = $moduleList;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
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

    //########################################

    public function getName()
    {
        return 'm2epro';
    }

    public function getVersion()
    {
        // TODO
        $version = (string)$this->moduleList->getOne('Ess_M2ePro')['setup_version'];
        $version = strtolower($version);

        if ($this->getHelper('Data\Cache\Permanent')->getValue('MODULE_VERSION_UPDATER') === false) {
            $this->primaryConfig->setGroupValue(
                '/modules/', $this->getName(), $version.'.r'.$this->getRevision()
            );
            $this->getHelper('Data\Cache\Permanent')->setValue('MODULE_VERSION_UPDATER',array(),array(),60*60*24);
        }

        return $version;
    }

    public function getRevision()
    {
        return '#REVISION#';
    }

    // ---------------------------------------

    public function getInstallationKey()
    {
        return $this->primaryConfig->getGroupValue(
            '/'.$this->getName().'/server/', 'installation_key'
        );
    }

    public function getVersionWithRevision()
    {
        return $this->getVersion().'r'.$this->getRevision();
    }

    //########################################

    public function isReadyToWork()
    {
        return $this->getHelper('View\Ebay')->isInstallationWizardFinished() ||
               $this->getHelper('View\Amazon')->isInstallationWizardFinished();
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

    // todo rename -> setDevelopmentMode
    public function setDevelopmentModeMode($value)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                                                      ->setHttpOnly(true)
                                                      ->setDurationOneYear();
        if ($value) {
            $this->cookieManager->setPublicCookie(self::DEVELOPMENT_MODE_COOKIE_KEY, 'true', $cookieMetadata);
        } else {
            $this->cookieManager->deleteCookie(self::DEVELOPMENT_MODE_COOKIE_KEY);
        }
    }

    //########################################

    public function getRequirementsInfo()
    {
        $clientPhpData = $this->getHelper('Client')->getPhpSettings();

        $requirements = array (

            'php_version' => array(
                'title' => $this->getHelper('Module\Translation')->__('PHP Version'),
                'condition' => array(
                    'sign' => '>=',
                    'value' => '5.3.0'
                ),
                'current' => array(
                    'value' => $this->getHelper('Client')->getPhpVersion(),
                    'status' => true
                )
            ),

            'memory_limit' => array(
                'title' => $this->getHelper('Module\Translation')->__('Memory Limit'),
                'condition' => array(
                    'sign' => '>=',
                    'value' => '256 MB'
                ),
                'current' => array(
                    'value' => (int)$clientPhpData['memory_limit'] . ' MB',
                    'status' => true
                )
            ),

            'magento_version' => array(
                'title' => $this->getHelper('Module\Translation')->__('Magento Version'),
                'condition' => array(
                    'sign' => '>=',
                    'value' => $this->getHelper('Magento')->isEnterpriseEdition()   ? '1.7.0.0' :
                        ($this->getHelper('Magento')->isProfessionalEdition() ? '1.7.0.0' : '1.4.1.0')
                ),
                'current' => array(
                    'value' => $this->getHelper('Magento')->getVersion(false),
                    'status' => true
                )
            ),

            'max_execution_time' => array(
                'title' => $this->getHelper('Module\Translation')->__('Max Execution Time'),
                'condition' => array(
                    'sign' => '>=',
                    'value' => '360 sec'
                ),
                'current' => array(
                    'value' => is_null($clientPhpData['max_execution_time'])
                        ? 'unknown' : $clientPhpData['max_execution_time'] . ' sec',
                    'status' => true
                )
            )
        );

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

    // ---------------------------------------

    // TODO magento 2
    public function getFoldersAndFiles()
    {
        $paths = array(
            'app/code/community/Ess/',
            'app/code/community/Ess/M2ePro/*',

            'app/locale/*/Ess_M2ePro.csv',
            'app/etc/modules/Ess_M2ePro.xml',
            'app/design/adminhtml/default/default/layout/M2ePro.xml',

            'js/M2ePro/*',
            'skin/adminhtml/default/default/M2ePro/*',
            'skin/adminhtml/default/enterprise/M2ePro/*',
            'app/design/adminhtml/default/default/template/M2ePro/*'
        );

        return $paths;
    }

    // TODO magento 2
    public function getUnWritableDirectories()
    {
        $directoriesForCheck = array();
        foreach ($this->getFoldersAndFiles() as $item) {

            $fullDirPath = Mage::getBaseDir().DS.$item;

            if (preg_match('/\*.*$/',$item)) {
                $fullDirPath = preg_replace('/\*.*$/', '', $fullDirPath);
                $directoriesForCheck = array_merge($directoriesForCheck, $this->getDirectories($fullDirPath));
            }

            $directoriesForCheck[] = dirname($fullDirPath);
            is_dir($fullDirPath) && $directoriesForCheck[] = rtrim($fullDirPath, '/\\');
        }
        $directoriesForCheck = array_unique($directoriesForCheck);

        $unWritableDirs = array();
        foreach ($directoriesForCheck as $directory) {
            !is_dir_writeable($directory) && $unWritableDirs[] = $directory;
        }

        return $unWritableDirs;
    }

    // ---------------------------------------

    private function getDirectories($dirPath)
    {
        $directoryIterator = new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

        $directories = array();
        foreach ($iterator as $path) {
            $path->isDir() && $directories[] = rtrim($path->getPathname(),'/\\');
        }

        return $directories;
    }

    //########################################

    public function getServerMessages()
    {
        $messages = $this->primaryConfig->getGroupValue(
            '/'.$this->getName().'/server/', 'messages'
        );

        $messages = (!is_null($messages) && $messages != '') ?
            (array)json_decode((string)$messages,true) :
            array();

        $messages = array_filter($messages,array($this,'getServerMessagesFilterModuleMessages'));
        !is_array($messages) && $messages = array();

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

    // todo remove
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