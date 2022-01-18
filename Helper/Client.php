<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\Client
 */
class Client extends AbstractHelper
{
    const API_APACHE_HANDLER = 'apache2handler';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;
    /** @var \Magento\Framework\Filesystem */
    protected $filesystem;
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resource;
    /** @var \Magento\Framework\HTTP\PhpEnvironment\Request */
    protected $phpEnvironmentRequest;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getDomain()
    {
        $domain = $this->getHelper('Module')->getConfig()->getGroupValue('/location/', 'domain');
        if (empty($domain)) {
            $domain = $this->getServerDomain();
        }

        if (empty($domain)) {
            throw new \Ess\M2ePro\Model\Exception('Server Domain is not defined');
        }

        return $domain;
    }

    public function getIp()
    {
        $ip = $this->getHelper('Module')->getConfig()->getGroupValue('/location/', 'ip');
        if (empty($ip)) {
            $ip = $this->getServerIp();
        }

        if (empty($ip)) {
            throw new \Ess\M2ePro\Model\Exception('Server IP is not defined');
        }

        return $ip;
    }

    public function getBaseDirectory()
    {
        return $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
              ->getAbsolutePath();
    }

    // ---------------------------------------

    public function updateLocationData($forceUpdate = false)
    {
        $dateLastCheck = $this->getHelper('Module')->getRegistry()->getValue('/location/date_last_check/');
        if ($dateLastCheck !== null) {
            $dateLastCheck = strtotime($dateLastCheck);

            if (!$forceUpdate && $this->getHelper('Data')->getCurrentGmtDate(true) < $dateLastCheck + 60*60*24) {
                return;
            }
        }

        $this->getHelper('Module')->getRegistry()->setValue(
            '/location/date_last_check/',
            $this->getHelper('Data')->getCurrentGmtDate()
        );

        $domain = $this->getServerDomain();
        if (null === $domain) {
            $domain = '127.0.0.1';
        }

        $ip = $this->getServerIp();
        if (null === $ip) {
            $ip = '127.0.0.1';
        }

        $this->getHelper('Module')->getConfig()->setGroupValue('/location/', 'domain', $domain);
        $this->getHelper('Module')->getConfig()->setGroupValue('/location/', 'ip', $ip);
    }

    protected function getServerDomain()
    {
        $domain = rtrim($this->phpEnvironmentRequest->getServer('HTTP_HOST'), '/');
        empty($domain) && $domain = '127.0.0.1';
        strpos($domain, 'www.') === 0 && $domain = substr($domain, 4);

        return strtolower(trim((string)$domain));
    }

    protected function getServerIp()
    {
        $ip = $this->phpEnvironmentRequest->getServer('SERVER_ADDR');
        !$this->isValidIp($ip) && $ip = $this->phpEnvironmentRequest->getServer('LOCAL_ADDR');
        !$this->isValidIp($ip) && $ip = gethostbyname(gethostname());

        return strtolower(trim((string)$ip));
    }

    protected function isValidIp($ip)
    {
        return !empty($ip) && (
                filter_var($ip, FILTER_VALIDATE_IP) ||
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            );
    }

    //########################################

    public function getPhpVersion($asArray = false)
    {
        $version = [
            PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION
        ];

        return $asArray ? $version : implode('.', $version);
    }

    public function getPhpApiName()
    {
        return PHP_SAPI;
    }

    public function getPhpIniFileLoaded()
    {
        return php_ini_loaded_file();
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
        return [
            'memory_limit'       => $this->getMemoryLimit(),
            'max_execution_time' => $this->getExecutionTime(),
            'phpinfo'            => $this->getPhpInfoArray()
        ];
    }

    public function getPhpInfoArray()
    {
        if (in_array('phpinfo', $this->getDisabledFunctions())) {
            return [];
        }

        try {
            ob_start();
            phpinfo(INFO_ALL);

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
                ],
                ob_get_clean()
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
                    $pi[$n][$m[1]]=(!isset($m[3]) || $m[2]==$m[3])?$m[2]:array_slice($m, 2);
                }
            }
        } catch (\Exception $exception) {
            ob_get_clean();
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
        $settings = array_merge($settings, isset($phpInfo['mysql'])?$phpInfo['mysql']:[]);

        return $settings;
    }

    public function getMysqlTotals()
    {
        $moduleTables = $this->getHelper('Module_Database_Structure')->getModuleTables();
        $magentoTables = $this->getHelper('Magento')->getMySqlTables();

        $connRead = $this->resource->getConnection();

        $totalRecords = 0;
        foreach ($moduleTables as $moduleTable) {
            $moduleTable = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($moduleTable);

            if (!in_array($moduleTable, $magentoTables)) {
                continue;
            };

            $dbSelect = $connRead->select()->from($moduleTable, new \Zend_Db_Expr('COUNT(*)'));
            $totalRecords += (int)$connRead->fetchOne($dbSelect);
        }

        return [
            'magento_tables' => count($magentoTables),
            'module_tables' => count($moduleTables),
            'module_records' => $totalRecords
        ];
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

    public function getDisabledFunctions()
    {
        return array_filter(explode(',', ini_get('disable_functions')));
    }

    //########################################

    public function getSystem()
    {
        return PHP_OS;
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

        switch ($lastMemoryLimitLetter) {
            case 'g':
                $memoryLimit *= 1024;
                // no break

            case 'm':
                $memoryLimit *= 1024;
                // no break

            case 'k':
                $memoryLimit *= 1024;
                // no break
        }

        if ($memoryLimit > 0 && $inMegabytes) {
            $memoryLimit /= 1024 * 1024;
        }

        return $memoryLimit;
    }

    public function setMemoryLimit($maxSize)
    {
        $minSize = 32;
        $currentMemoryLimit = $this->getMemoryLimit();

        if ($currentMemoryLimit <= 0 || $maxSize < $minSize || $currentMemoryLimit >= $maxSize) {
            return;
        }

        // @codingStandardsIgnoreStart
        $i = max($minSize, $currentMemoryLimit);
        do {
            $i *= 2;
            $k = min($i, $maxSize);

            if (ini_set('memory_limit', "{$k}M") === false) {
                return;
            }
        } while ($i < $maxSize);
        // @codingStandardsIgnoreEnd
    }

    public function testMemoryLimit($bytes = null)
    {
        $this->getHelper('Module')->getRegistry()->setValue('/tools/memory-limit/test/', null);

        $i = 0;
        $array = [];

        // @codingStandardsIgnoreStart
        while (($usage = memory_get_usage(true)) < $bytes || $bytes === null) {
            $array[] = $array;
            if (++$i % 100 === 0) {
                $this->getHelper('Module')->getRegistry()->setValue('/tools/memory-limit/test/', $usage);
            }
        }
        // @codingStandardsIgnoreEnd

        return $usage;
    }

    public function getTestedMemoryLimit()
    {
        return $this->getHelper('Module')->getRegistry()->getValue('/tools/memory-limit/test/');
    }

    // ---------------------------------------

    public function getExecutionTime()
    {
        if ($this->isPhpApiFastCgi()) {
            return null;
        }

        // @codingStandardsIgnoreLine
        return ini_get('max_execution_time');
    }

    public function testExecutionTime($seconds)
    {
        $this->getHelper('Module')->getRegistry()->setValue('/tools/execution-time/test/', null);

        $i = 0;

        // @codingStandardsIgnoreStart
        while ($i < $seconds) {
            sleep(1);
            if (++$i % 10 === 0) {
                $this->getHelper('Module')->getRegistry()->setValue('/tools/execution-time/test/', $i);
            }
        }
        // @codingStandardsIgnoreEnd

        $this->getHelper('Module')->getRegistry()->setValue('/tools/execution-time/test/', $seconds);

        return $i;
    }

    public function getTestedExecutionTime()
    {
        return $this->getHelper('Module')->getRegistry()->getValue('/tools/execution-time/test/');
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

    public function getClassName($object)
    {
        if ($object instanceof \Magento\Framework\Interception\InterceptorInterface) {
            return get_parent_class($object);
        } else {
            return get_class($object);
        }
    }

    //########################################
}
