<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Client extends AbstractHelper
{
    const API_APACHE_HANDLER = 'apache2handler';

    protected $cacheConfig;
    protected $filesystem;
    protected $resource;
    protected $phpEnvironmentRequest;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest
    )
    {
        $this->cacheConfig = $cacheConfig;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getHost()
    {
        $domain = $this->getDomain();
        return empty($domain) ? $this->getIp() : $domain;
    }

    // ---------------------------------------

    public function getDomain()
    {
        $domain = $this->cacheConfig->getGroupValue('/location_info/', 'domain');

        if (!empty($domain)) {
            return strtolower(trim($domain));
        }

        $domain = rtrim($this->phpEnvironmentRequest->getServer('HTTP_HOST'), '/');

        if (!empty($domain)) {
            return strtolower(trim($domain));
        }

        throw new \Ess\M2ePro\Model\Exception('Server Domain is not defined');
    }

    public function getIp()
    {
        $ip = $this->cacheConfig->getGroupValue('/location_info/', 'ip');

        if (!empty($ip)) {
            return strtolower(trim($ip));
        }

        $ip = $this->phpEnvironmentRequest->getServer('SERVER_ADDR');
        empty($ip) && $ip = $this->phpEnvironmentRequest->getServer('LOCAL_ADDR');

        if (!empty($ip)) {
            return strtolower(trim($ip));
        }

        throw new \Ess\M2ePro\Model\Exception('Server IP is not defined');
    }

    public function getBaseDirectory()
    {
        $directory = $this->cacheConfig->getGroupValue('/location_info/', 'directory');

        if (!empty($directory)) {
            return strtolower(trim($directory));
        }

        $directory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                      ->getAbsolutePath();

        if (!empty($directory)) {
            return strtolower(trim($directory));
        }

        throw new \Ess\M2ePro\Model\Exception('Server Directory is not defined');
    }

    // ---------------------------------------

    public function updateBackupConnectionData($forceUpdate = false)
    {
        $dateLastCheck = $this->cacheConfig->getGroupValue('/location_info/', 'date_last_check');

        if (empty($dateLastCheck)) {
            $dateLastCheck = $this->getHelper('Data')->getCurrentGmtDate(true) - 60*60*365;
        } else {
            $dateLastCheck = strtotime($dateLastCheck);
        }

        if (!$forceUpdate && $this->getHelper('Data')->getCurrentGmtDate(true) < $dateLastCheck + 60*60*24) {
            return;
        }

        $domain = rtrim($this->phpEnvironmentRequest->getServer('HTTP_HOST'), '/');
        empty($domain) && $domain = '127.0.0.1';
        strpos($domain,'www.') === 0 && $domain = substr($domain,4);
        $this->cacheConfig->setGroupValue('/location_info/', 'domain', $domain);

        $ip = $this->phpEnvironmentRequest->getServer('SERVER_ADDR');
        empty($ip) && $ip = $this->phpEnvironmentRequest->getServer('LOCAL_ADDR');
        empty($ip) && $ip = '127.0.0.1';
        $this->cacheConfig->setGroupValue('/location_info/', 'ip', $ip);

        $directory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->cacheConfig->setGroupValue('/location_info/', 'directory', $directory->getAbsolutePath());

        $this->cacheConfig->setGroupValue(
            '/location_info/', 'date_last_check', $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

     //########################################

    public function getPhpVersion()
    {
        return phpversion();
    }

    public function getPhpApiName()
    {
        return php_sapi_name();
    }

    // ---------------------------------------

    public function isPhpApiApacheHandler()
    {
        return $this->getPhpApiName() == self::API_APACHE_HANDLER;
    }

    public function isPhpApiFastCgi()
    {
        return !$this->isPhpApiApacheHandler();
    }

    // ---------------------------------------

    public function getPhpSettings()
    {
        return array(
            'memory_limit' => $this->getMemoryLimit(),
            'max_execution_time' => $this->isPhpApiApacheHandler() ? ini_get('max_execution_time') : NULL,
            'phpinfo' => $this->getPhpInfoArray()
        );
    }

    public function getPhpInfoArray()
    {
        try {

            ob_start(); phpinfo(INFO_ALL);

            $pi = preg_replace(
            [
                '#^.*<body>(.*)</body>.*$#m', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#", '#<tr>#', '#</tr>#'],
            [
                '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'
            ], ob_get_clean()
            );

            $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
            unset($sections[0]);

            $pi = [];
            foreach ($sections as $section) {
                $n = substr($section, 0, strpos($section, '</h2>'));
                preg_match_all(
                    '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                    $section,
                    $askapache,
                    PREG_SET_ORDER
                );
                foreach ($askapache as $m) {
                    if (!isset($m[0]) || !isset($m[1]) || !isset($m[2])) {
                        continue;
                    }
                    $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
                }
            }

        } catch (\Exception $exception) {
            return [];
        }

        return $pi;
    }

    //########################################

    public function getMysqlVersion()
    {
        return $this->resource->getConnection()->getServerVersion();
    }

    public function getMysqlApiName()
    {
        $connection = $this->resource->getConnection();
        return $connection instanceof \PDO ? $connection->getAttribute(\PDO::ATTR_CLIENT_VERSION) : 'N/A';
    }

    // ---------------------------------------

    public function getMysqlSettings()
    {
        $sqlQuery = "SHOW VARIABLES
                     WHERE `Variable_name` IN ('connect_timeout','wait_timeout')";

        $settingsArray = $this->resource->getConnection()->fetchAll($sqlQuery);

        $settings = [];
        foreach ($settingsArray as $settingItem) {
            $settings[$settingItem['Variable_name']] = $settingItem['Value'];
        }

        $phpInfo = $this->getPhpInfoArray();
        $settings = array_merge($settings,isset($phpInfo['mysql'])?$phpInfo['mysql']:[]);

        return $settings;
    }

    public function getMysqlTotals()
    {
        $moduleTables = $this->getHelper('Module\Database\Structure')->getMySqlTables();
        $magentoTables = $this->getHelper('Magento')->getMySqlTables();

        $connRead = $this->resource->getConnection();

        $totalRecords = 0;
        foreach ($moduleTables as $moduleTable) {

            $moduleTable = $this->resource->getTableName($moduleTable);

            if (!in_array($moduleTable, $magentoTables)) {
                continue;
            };

            $dbSelect = $connRead->select()->from($moduleTable,new \Zend_Db_Expr('COUNT(*)'));
            $totalRecords += (int)$connRead->fetchOne($dbSelect);
        }

        return array(
            'magento_tables' => count($magentoTables),
            'module_tables' => count($moduleTables),
            'module_records' => $totalRecords
        );
    }

    // ---------------------------------------

    public function updateMySqlConnection()
    {
        $connection = $this->resource->getConnection();

        try {
            $connection->query('SELECT 1');
        } catch (\Exception $exception) {
            $connection->closeConnection();
        }
    }

    //########################################

    public function getSystem()
    {
        return php_uname();
    }

    public function isBrowserIE()
    {
        return strpos($this->phpEnvironmentRequest->getServer('HTTP_USER_AGENT'), 'MSIE') !== false;
    }

    // ---------------------------------------

    public function getMemoryLimit($inMegabytes = true)
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ($memoryLimit == '') {
            return 0;
        }

        $lastMemoryLimitLetter = strtolower(substr($memoryLimit, -1));
        $memoryLimit = (int)$memoryLimit;

        switch($lastMemoryLimitLetter) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }

        if ($inMegabytes) {
            $memoryLimit /= 1024 * 1024;
        }

        return $memoryLimit;
    }

    public function setMemoryLimit($maxSize = 512)
    {
        $minSize = 32;
        $currentMemoryLimit = $this->getMemoryLimit();

        if ($maxSize < $minSize || (int)$currentMemoryLimit >= $maxSize) {
            return false;
        }

        for ($i=$minSize; $i<=$maxSize; $i*=2) {

            if (@ini_set('memory_limit',"{$i}M") === false) {
                if ($i == $minSize) {
                    return false;
                } else {
                    return $i/2;
                }
            }
        }

        return true;
    }

    //########################################

    /**
     * Ability to fix ZF-5063: Segmentaion fault on preg_replace in Zend_Db_Statement
     * Error happen in Zend_DB module during executing large SQL query that contains " symbol.
     * Setting this ini config value to 1000 resolves problem, but it can cause issues in other places.
     * Not used in M2ePro code now.
     *
     * @param int $limit
     */
    public function setPcreRecursionLimit($limit = 1000)
    {
        ini_set('pcre.recursion_limit', $limit);
    }

    //########################################
}