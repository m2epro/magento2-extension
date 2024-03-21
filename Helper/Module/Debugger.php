<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Debugger
{
    /** @link https://php.net/manual/en/datetime.format.php */
    private const DATE_POINT_FORMAT = 'H:i:s';

    /** @link https://php.net/manual/en/dateinterval.format.php */
    private const DATE_POINT_INTERVAL_FORMAT = '%s.%f';
    private const DATE_POINT_INTERVAL_FORMAT_UNIT_OF_MEASURE = 's';

    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private static $logger;
    /** @var bool  */
    private static $isEnables = false;
    /** @var array  */
    private static $collectData = [];
    /** @var \DateTime[]  */
    private static $datePointCollect = [];
    /** @var bool */
    private static $isEnablesDatePoint = false;

    /**
     * @return void
     */
    public static function enable(): void // @codingStandardsIgnoreLine
    {
        self::$isEnables = true;
    }

    /**
     * @return void
     * @see self::collectData()
     */
    public static function enableWithDatePoints(): void // @codingStandardsIgnoreLine
    {
        self::$isEnables = true;
        self::$isEnablesDatePoint = true;
    }

    /**
     * @return void
     */
    public static function disable(): void // @codingStandardsIgnoreLine
    {
        self::$isEnables = false;
        self::$isEnablesDatePoint = false;
    }

    /**
     * @param array $data
     * @param string $label
     *
     * @return void
     */
    public static function write(array $data, string $label = 'debug'): void // @codingStandardsIgnoreLine
    {
        if (!self::$isEnables) {
            return;
        }

        self::getLogger()->process(
            $data,
            $label
        );
    }

    // ----------------------------------------

    public static function collectData(array $data, string $label = 'general'): void // @codingStandardsIgnoreLine
    {
        if (!isset(self::$collectData[$label])) {
            self::$collectData[$label] = [];
        }

        if (self::$isEnablesDatePoint) {
            self::collectDatePoint($label);
        }

        self::$collectData[$label] = $data;
    }

    public static function flushCollectedData(string $label = 'debug'): void // @codingStandardsIgnoreLine
    {
        if (empty(self::$collectData)) {
            return;
        }

        $collectData = self::$isEnablesDatePoint
            ? self::mergeCollectDataWithDatePoints(self::$collectData)
            : self::$collectData;

        self::write($collectData, $label);
    }

    // ----------------------------------------

    private static function collectDatePoint(string $label): void // @codingStandardsIgnoreLine
    {
        if (!isset(self::$datePointCollect[$label])) {
            self::$datePointCollect[$label] = [];
        }

        self::$datePointCollect[$label] = \Ess\M2ePro\Helper\Date::createCurrentGmt();
    }

    private static function mergeCollectDataWithDatePoints(array $collectData): array // @codingStandardsIgnoreLine
    {
        /** @var \DateTime|null $previousDatePoint */
        $previousDatePoint = null;
        foreach ($collectData as $label => &$data) {
            if (!isset(self::$datePointCollect[$label])) {
                continue;
            }
            $datePoint = self::$datePointCollect[$label];

            if (!isset($data['date_interval'])) {
                $dateInterval = $previousDatePoint
                    ? $datePoint->diff($previousDatePoint)->format(self::DATE_POINT_INTERVAL_FORMAT)
                    : 0;
                $data['date_interval'] = $dateInterval . self::DATE_POINT_INTERVAL_FORMAT_UNIT_OF_MEASURE;
            }

            $previousDatePoint = $datePoint;

            if (!isset($data['recorded_at'])) {
                $data['recorded_at'] = $datePoint->format(self::DATE_POINT_FORMAT);
            }
        }

        return $collectData;
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Helper\Module\Logger
     */
    private static function getLogger(): \Ess\M2ePro\Helper\Module\Logger // @codingStandardsIgnoreLine
    {
        if (!isset(self::$logger)) {
            self::$logger = \Magento\Framework\App\ObjectManager::getInstance()
                                                                ->get(\Ess\M2ePro\Helper\Module\Logger::class);
        }

        return self::$logger;
    }

    // ----------------------------------------

    public static function writeInFile( // @codingStandardsIgnoreLine
        array $data,
        string $label = 'debug',
        bool $isAddTrace = false
    ): void {
        if (!self::$isEnables) {
            return;
        }

        $fileName = 'debug_m2e/debug.log';

        $log = '###################################################' . "\n";
        $log .= 'Date : ' . \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s') . "\n";
        $log .= sprintf("Label : %s\n", $label);
        $log .= 'Data JSON : ' . json_encode($data, JSON_FORCE_OBJECT) . "\n";
        if ($isAddTrace) {
            $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $log .= '--------------------------- STACK TRACE INFO ---------------------------' . "\n";
            $log .= self::getBacktraceLog($debugBacktrace);
        }
        $log .= '###################################################' . "\n";

        /** @var \Magento\Framework\Filesystem $fs */
        $fs = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Filesystem::class);
        $dir = $fs->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);

        try {
            $stream = $dir->openFile($fileName, 'a');
            $stream->lock();
            $stream->write($log);
            $stream->unlock();
            $stream->close();
        } catch (\Throwable $e) {
        }
    }

    private static function getBacktraceLog(array $debugBacktrace): string
    {
        $backtraceLog = '';
        foreach ($debugBacktrace as $backtrace) {
            $arrTrace = [
                'class' => $backtrace['class'] ?? '',
                'func' => $backtrace['function'] ?? '',
                'file' => $backtrace['file'] ?? '',
                'line' => $backtrace['line'] ?? '',
            ];
            foreach ($arrTrace as $key => $val) {
                $backtraceLog .= $key . ' => ' . $val . "\n";
            }
            $backtraceLog .= '---------------------------------------------' . "\n";
        }

        return $backtraceLog;
    }
}
