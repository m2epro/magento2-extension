<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Client
{
    private const API_APACHE_HANDLER = 'apache2handler';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;
    /** @var \Magento\Framework\Filesystem */
    protected $filesystem;
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resource;
    /** @var \Magento\Framework\HTTP\PhpEnvironment\Request */
    protected $phpEnvironmentRequest;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDbStructure;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $helperMagento;

    /**
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $moduleDbStructure
     * @param \Ess\M2ePro\Helper\Magento $helperMagento
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest
     * @param \Ess\M2ePro\Helper\Data $helperData
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDbStructure,
        \Ess\M2ePro\Helper\Magento $helperMagento,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Ess\M2ePro\Helper\Data $helperData
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->helperData = $helperData;
        $this->config = $config;
        $this->registry = $registry;
        $this->moduleDbStructure = $moduleDbStructure;
        $this->helperMagento = $helperMagento;
    }

    // ----------------------------------------

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getDomain()
    {
        $domain = $this->config->getGroupValue('/location/', 'domain');
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
        $ip = $this->config->getGroupValue('/location/', 'ip');
        if (empty($ip)) {
            $ip = $this->getServerIp();
        }

        if (empty($ip)) {
            throw new \Ess\M2ePro\Model\Exception('Server IP is not defined');
        }

        return $ip;
    }

    /**
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
                                ->getAbsolutePath();
    }

    // ---------------------------------------

    /**
     * @param bool $forceUpdate
     *
     * @return void
     */
    public function updateLocationData($forceUpdate = false): void
    {
        $dateLastCheck = $this->registry->getValue('/location/date_last_check/');
        if ($dateLastCheck !== null) {
            $dateLastCheck = (int)$this->helperData
                ->createGmtDateTime($dateLastCheck)
                ->format('U');

            if (!$forceUpdate && $this->helperData->getCurrentGmtDate(true) < $dateLastCheck + 60 * 60 * 24) {
                return;
            }
        }

        $this->registry->setValue(
            '/location/date_last_check/',
            $this->helperData->getCurrentGmtDate()
        );

        $domain = $this->getServerDomain();
        if (null === $domain) {
            $domain = '127.0.0.1';
        }

        $ip = $this->getServerIp();
        if (null === $ip) {
            $ip = '127.0.0.1';
        }

        $this->config->setGroupValue('/location/', 'domain', $domain);
        $this->config->setGroupValue('/location/', 'ip', $ip);
    }

    /**
     * @return string
     */
    protected function getServerDomain(): string
    {
        $domain = rtrim($this->phpEnvironmentRequest->getServer('HTTP_HOST'), '/');
        empty($domain) && $domain = '127.0.0.1';
        strpos($domain, 'www.') === 0 && $domain = substr($domain, 4);

        return strtolower(trim((string)$domain));
    }

    /**
     * @return string
     */
    protected function getServerIp(): string
    {
        $ip = $this->phpEnvironmentRequest->getServer('SERVER_ADDR');
        !$this->isValidIp($ip) && $ip = $this->phpEnvironmentRequest->getServer('LOCAL_ADDR');
        !$this->isValidIp($ip) && $ip = gethostbyname(gethostname());

        return strtolower(trim((string)$ip));
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    protected function isValidIp($ip): bool
    {
        return !empty($ip) && (
                filter_var($ip, FILTER_VALIDATE_IP) ||
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            );
    }

    // ----------------------------------------

    /**
     * @param bool $asArray
     *
     * @return array|string
     */
    public function getPhpVersion($asArray = false)
    {
        $version = [
            PHP_MAJOR_VERSION,
            PHP_MINOR_VERSION,
            PHP_RELEASE_VERSION,
        ];

        return $asArray ? $version : implode('.', $version);
    }

    /**
     * @return string
     */
    public function getPhpApiName(): string
    {
        return PHP_SAPI;
    }

    /**
     * @return false|string
     */
    public function getPhpIniFileLoaded()
    {
        return php_ini_loaded_file();
    }

    // ---------------------------------------

    public function isPhpApiApacheHandler(): bool
    {
        return $this->getPhpApiName() === self::API_APACHE_HANDLER;
    }

    public function isPhpApiFastCgi(): bool
    {
        return !$this->isPhpApiApacheHandler();
    }

    // ---------------------------------------

    public function getPhpSettings(): array
    {
        return [
            'memory_limit'       => $this->getMemoryLimit(),
            'max_execution_time' => $this->getExecutionTime(),
            'phpinfo'            => $this->getPhpInfoArray(),
        ];
    }

    public function getPhpInfoArray(): array
    {
        if (in_array('phpinfo', $this->getDisabledFunctions())) {
            return [];
        }

        try {
            ob_start();
            phpinfo(INFO_ALL);

            $pi = preg_replace(
                [
                    '#^.*<body>(.*)</body>.*$#m',
                    '#<h2>PHP License</h2>.*$#ms',
                    '#<h1>Configuration</h1>#',
                    "#\r?\n#",
                    "#</(h1|h2|h3|tr)>#",
                    '# +<#',
                    "#[ \t]+#",
                    '#&nbsp;#',
                    '#  +#',
                    '# class=".*?"#',
                    '%&#039;%',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                    '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                    '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                    "# +#",
                    '#<tr>#',
                    '#</tr>#',
                ],
                [
                    '$1',
                    '',
                    '',
                    '',
                    '</$1>' . "\n",
                    '<',
                    ' ',
                    ' ',
                    ' ',
                    '',
                    ' ',
                    '<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' .
                    "\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>',
                    '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                    '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                    '<tr><td>Zend Egg</td><td>$1</td></tr>',
                    ' ',
                    '%S%',
                    '%E%',
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
                    $pi[$n][$m[1]] = (!isset($m[3]) || $m[2] == $m[3]) ? $m[2] : array_slice($m, 2);
                }
            }
        } catch (\Exception $exception) {
            ob_get_clean();

            return [];
        }

        return $pi;
    }

    // ----------------------------------------

    /**
     * @return string|null
     */
    public function getMysqlVersion(): ?string
    {
        return $this->resource->getConnection()->getServerVersion();
    }

    /**
     * @return string
     */
    public function getMysqlApiName(): string
    {
        $connection = $this->resource->getConnection();

        return $connection instanceof \PDO ? $connection->getAttribute(\PDO::ATTR_CLIENT_VERSION) : 'N/A';
    }

    // ---------------------------------------

    public function getMysqlSettings(): array
    {
        $sqlQuery = "SHOW VARIABLES
                     WHERE `Variable_name` IN ('connect_timeout','wait_timeout')";

        $settingsArray = $this->resource->getConnection()->fetchAll($sqlQuery);

        $settings = [];
        foreach ($settingsArray as $settingItem) {
            $settings[$settingItem['Variable_name']] = $settingItem['Value'];
        }

        $phpInfo = $this->getPhpInfoArray();

        return array_merge($settings, $phpInfo['mysql'] ?? []);
    }

    /**
     * @return array
     */
    public function getMysqlTotals(): array
    {
        $moduleTables = $this->moduleDbStructure->getModuleTables();
        $magentoTables = $this->helperMagento->getMySqlTables();

        $connRead = $this->resource->getConnection();

        $totalRecords = 0;
        foreach ($moduleTables as $moduleTable) {
            $moduleTable = $this->moduleDbStructure->getTableNameWithPrefix($moduleTable);

            if (!in_array($moduleTable, $magentoTables)) {
                continue;
            };

            $dbSelect = $connRead->select()->from($moduleTable, new \Zend_Db_Expr('COUNT(*)'));
            $totalRecords += (int)$connRead->fetchOne($dbSelect);
        }

        return [
            'magento_tables' => count($magentoTables),
            'module_tables'  => count($moduleTables),
            'module_records' => $totalRecords,
        ];
    }

    // ---------------------------------------

    public function updateMySqlConnection(): void
    {
        $connection = $this->resource->getConnection();

        try {
            $connection->query('SELECT 1');
        } catch (\Exception $exception) {
            $connection->closeConnection();
        }
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getDisabledFunctions(): array
    {
        return array_filter(explode(',', ini_get('disable_functions')));
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getSystem(): string
    {
        return PHP_OS;
    }

    // ----------------------------------------

    /**
     * @param bool $inMegabytes
     *
     * @return int
     */
    public function getMemoryLimit($inMegabytes = true)
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ($memoryLimit === '') {
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

    /**
     * @param int $maxSize
     *
     * @return void
     */
    public function setMemoryLimit($maxSize): void
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

    /**
     * @param int|null $bytes
     *
     * @return int
     */
    public function testMemoryLimit($bytes = null)
    {
        $this->registry->setValue('/tools/memory-limit/test/', null);

        $i = 0;
        $array = [];

        // @codingStandardsIgnoreStart
        while (($usage = memory_get_usage(true)) < $bytes || $bytes === null) {
            $array[] = $array;
            if (++$i % 100 === 0) {
                $this->registry->setValue('/tools/memory-limit/test/', $usage);
            }
        }

        // @codingStandardsIgnoreEnd

        return $usage;
    }

    /**
     * @return int|null
     */
    public function getTestedMemoryLimit()
    {
        return $this->registry->getValue('/tools/memory-limit/test/');
    }

    // ----------------------------------------

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
        $this->registry->setValue('/tools/execution-time/test/', null);

        $i = 0;

        // @codingStandardsIgnoreStart
        while ($i < $seconds) {
            sleep(1);
            if (++$i % 10 === 0) {
                $this->registry->setValue('/tools/execution-time/test/', $i);
            }
        }
        // @codingStandardsIgnoreEnd

        $this->registry->setValue('/tools/execution-time/test/', $seconds);

        return $i;
    }

    public function getTestedExecutionTime()
    {
        return $this->registry->getValue('/tools/execution-time/test/');
    }

    // ----------------------------------------

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

    /**
     * @param object $object
     *
     * @return string
     */
    public function getClassName($object): string
    {
        if ($object instanceof \Magento\Framework\Interception\InterceptorInterface) {
            return get_parent_class($object);
        }

        return get_class($object);
    }
}
