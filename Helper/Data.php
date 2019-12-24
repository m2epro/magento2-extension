<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\Data
 */
class Data extends AbstractHelper
{
    const STATUS_ERROR      = 1;
    const STATUS_WARNING    = 2;
    const STATUS_SUCCESS    = 3;

    const INITIATOR_UNKNOWN   = 0;
    const INITIATOR_USER      = 1;
    const INITIATOR_EXTENSION = 2;
    const INITIATOR_DEVELOPER = 3;

    const CUSTOM_IDENTIFIER = 'm2epro_extension';

    protected $dir;
    protected $urlBuilder;
    protected $localeDate;
    protected $timezone;
    protected $objectManager;

    protected $serializerInterface;
    protected $phpSerialize;

    //########################################

    public function __construct(
        \Magento\Framework\Module\Dir $dir,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->dir           = $dir;
        $this->urlBuilder    = $urlBuilder;
        $this->localeDate    = $localeDate;
        $this->timezone      = $timezone;
        $this->objectManager = $objectManager;
        $this->phpSerialize  = \Zend\Serializer\Serializer::getDefaultAdapter();

        if (interface_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $this->serializerInterface = $this->objectManager->get(
                \Magento\Framework\Serialize\SerializerInterface::class
            );
        }

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getConfigTimezone()
    {
        return $this->timezone->getConfigTimezone();
    }

    public function getDefaultTimezone()
    {
        return $this->timezone->getDefaultTimezone();
    }

    // ---------------------------------------

    public function getCurrentGmtDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime('now', new \DateTimeZone($this->getDefaultTimezone()));

        if ($returnTimestamp) {
            return (int)$dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    public function getCurrentTimezoneDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime('now', new \DateTimeZone($this->getConfigTimezone()));

        if ($returnTimestamp) {
            return (int)$dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    // ---------------------------------------

    public function getDate($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        if (is_numeric($date)) {
            $result = (int)$date;
        } else {
            $result = strtotime($date);
        }

        $result = date($format, $result);

        if ($returnTimestamp) {
            return strtotime($result);
        }

        return $result;
    }

    // ---------------------------------------

    public function gmtDateToTimezone($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime($date, new \DateTimeZone($this->getDefaultTimezone()));
        $dateObject->setTimezone(new \DateTimeZone($this->getConfigTimezone()));

        if ($returnTimestamp) {
            return (int)$dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    public function timezoneDateToGmt($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime($date, new \DateTimeZone($this->getConfigTimezone()));
        $dateObject->setTimezone(new \DateTimeZone($this->getDefaultTimezone()));

        if ($returnTimestamp) {
            return (int)$dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    // ---------------------------------------

    public function parseTimestampFromLocalizedFormat(
        $localDate,
        $localIntlDateFormat = \IntlDateFormatter::SHORT,
        $localIntlTimeFormat = \IntlDateFormatter::SHORT,
        $localTimezone = null
    ) {
        $localTimezone === null && $localTimezone = $this->timezone->getConfigTimezone();

        $pattern = '';
        if ($localIntlDateFormat != \IntlDateFormatter::NONE) {
            $pattern = $this->timezone->getDateFormat($localIntlDateFormat);
        }
        if ($localIntlTimeFormat != \IntlDateFormatter::NONE) {
            $timeFormat = $this->timezone->getTimeFormat($localIntlTimeFormat);
            $pattern = empty($pattern) ? $timeFormat : $pattern .' '. $timeFormat;
        }

        $formatter = new \IntlDateFormatter(
            $localTimezone,
            $localIntlDateFormat,
            $localIntlTimeFormat,
            new \DateTimeZone($localTimezone),
            null,
            $pattern
        );

        return $formatter->parse($localDate);
    }

    //########################################

    public function escapeJs($string)
    {
        return str_replace(
            ["\\"  , "\n"  , "\r" , "\""  , "'"],
            ["\\\\", "\\n" , "\\r", "\\\"", "\\'"],
            $string
        );
    }

    public function escapeHtml($data, $allowedTags = null, $flags = ENT_COMPAT)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item, $allowedTags, $flags);
            }
        } else {
            // process single item
            if ($data !== '') {
                if (is_array($allowedTags) && !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);

                    $pattern = '/<([\/\s\r\n]*)(' . $allowed . ')'.
                        '((\s+\w+="[\w\s\%\?=&#\/\.,;:_\-\(\)]*")*[\/\s\r\n]*)>/si';
                    $result = preg_replace($pattern, '##$1$2$3##', $data);

                    $result = htmlspecialchars($result, $flags);

                    $pattern = '/##([\/\s\r\n]*)(' . $allowed . ')'.
                        '((\s+\w+="[\w\s\%\?=&#\/\.,;:_\-\(\)]*")*[\/\s\r\n]*)##/si';
                    $result = preg_replace($pattern, '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data, $flags);
                }
            } else {
                $result = $data;
            }
        }
        return $result;
    }

    //########################################

    public function deEscapeHtml($data, $flags = ENT_COMPAT)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->deEscapeHtml($item, $flags);
            }
        } else {
            // process single item
            if ($data !== '') {
                $result = htmlspecialchars_decode($data, $flags);
            } else {
                $result = $data;
            }
        }
        return $result;
    }

    //########################################

    public function convertStringToSku($title)
    {
        $skuVal = strtolower($title);
        $skuVal = str_replace([" ", ":", ",", ".", "?", "*", "+", "(", ")", "&", "%", "$", "#", "@",
                                    "!", '"', "'", ";", "\\", "|", "/", "<", ">"], "-", $skuVal);

        return $skuVal;
    }

    public function stripInvisibleTags($text)
    {
        $text = preg_replace(
            [
                // Remove invisible content
                '/<head[^>]*?>.*?<\/head>/siu',
                '/<style[^>]*?>.*?<\/style>/siu',
                '/<script[^>]*?.*?<\/script>/siu',
                '/<object[^>]*?.*?<\/object>/siu',
                '/<embed[^>]*?.*?<\/embed>/siu',
                '/<applet[^>]*?.*?<\/applet>/siu',
                '/<noframes[^>]*?.*?<\/noframes>/siu',
                '/<noscript[^>]*?.*?<\/noscript>/siu',
                '/<noembed[^>]*?.*?<\/noembed>/siu',

                // Add line breaks before & after blocks
                '/<((br)|(hr))/iu',
                '/<\/?((address)|(blockquote)|(center)|(del))/iu',
                '/<\/?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))/iu',
                '/<\/?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))/iu',
                '/<\/?((table)|(th)|(td)|(caption))/iu',
                '/<\/?((form)|(button)|(fieldset)|(legend)|(input))/iu',
                '/<\/?((label)|(select)|(optgroup)|(option)|(textarea))/iu',
                '/<\/?((frameset)|(frame)|(iframe))/iu',
            ],
            [
                ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
                "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
                "\n\$0", "\n\$0",
            ],
            $text
        );

        return $text;
    }

    public function normalizeToUtfEncoding($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeToUtfEncoding($value);
            }
        } elseif (is_string($data)) {
            return utf8_encode($data);
        }

        return $data;
    }

    public function reduceWordsInString($string, $neededLength, $longWord = 6, $minWordLen = 2, $atEndOfWord = '.')
    {
        $oldEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        if (mb_strlen($string) <= $neededLength) {
            mb_internal_encoding($oldEncoding);
            return $string;
        }

        $longWords = [];
        foreach (explode(' ', $string) as $word) {
            if (mb_strlen($word) >= $longWord && !preg_match('/[0-9]/', $word)) {
                $longWords[$word] = mb_strlen($word) - $minWordLen;
            }
        }

        $canBeReduced = 0;
        foreach ($longWords as $canBeReducedForWord) {
            $canBeReduced += $canBeReducedForWord;
        }

        $needToBeReduced = mb_strlen($string) - $neededLength + (count($longWords) * mb_strlen($atEndOfWord));

        if ($canBeReduced < $needToBeReduced) {
            mb_internal_encoding($oldEncoding);
            return $string;
        }

        $weightOfOneLetter = $needToBeReduced / $canBeReduced;
        foreach ($longWords as $word => $canBeReducedForWord) {
            $willReduced = ceil($weightOfOneLetter * $canBeReducedForWord);
            $reducedWord = mb_substr($word, 0, mb_strlen($word) - $willReduced) . $atEndOfWord;

            $string = str_replace($word, $reducedWord, $string);

            if (strlen($string) <= $neededLength) {
                break;
            }
        }

        mb_internal_encoding($oldEncoding);
        return $string;
    }

    /**
     * @param array $data
     * @return array
     */
    public function toLowerCaseRecursive(array $data = [])
    {
        if (empty($data)) {
            return $data;
        }

        $lowerCasedData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->toLowerCaseRecursive($value);
            } else {
                $value = trim(strtolower($value));
            }
            $lowerCasedData[trim(strtolower($key))] = $value;
        }

        return $lowerCasedData;
    }

    //########################################

    /**
     * It prevents situations when json_encode() returns FALSE due to some broken bytes sequence.
     * Normally normalizeToUtfEncoding() fixes that
     *
     * @param $data
     * @param bool $throwError
     * @return null|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function jsonEncode($data, $throwError = true)
    {
        if ($data === false) {
            return 'false';
        }

        $encoded = json_encode($data);
        if ($encoded !== false) {
            return $encoded;
        }

        $this->helperFactory->getObject('Module\Logger')->process(
            ['source' => $this->serialize($data)],
            'json_encode() failed',
            false
        );

        $encoded = json_encode($this->normalizeToUtfEncoding($data));
        if ($encoded !== false) {
            return $encoded;
        }

        $previousValue = \Zend_Json::$useBuiltinEncoderDecoder;
        \Zend_Json::$useBuiltinEncoderDecoder = true;
        $encoded = \Zend_Json::encode($data);
        \Zend_Json::$useBuiltinEncoderDecoder = $previousValue;

        if ($encoded !== false) {
            return $encoded;
        }

        if (!$throwError) {
            return null;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic(
            'Unable to encode to JSON.',
            ['source' => $this->serialize($data)]
        );
    }

    /**
     * It prevents situations when json_decode() returns NULL due to unknown issue.
     * Despite the fact that given JSON is having correct format
     *
     * @param $data
     * @param bool $throwError
     * @return null|array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function jsonDecode($data, $throwError = false)
    {
        if ($data === null || $data === '' || strtolower($data) === 'null') {
            return null;
        }

        $decoded = json_decode($data, true);
        if ($decoded !== null) {
            return $decoded;
        }

        $this->helperFactory->getObject('Module\Logger')->process(
            ['source' => $this->serialize($data)],
            'json_decode() failed',
            false
        );

        try {
            $previousValue = \Zend_Json::$useBuiltinEncoderDecoder;
            \Zend_Json::$useBuiltinEncoderDecoder = true;
            $decoded = \Zend_Json::decode($data);
            \Zend_Json::$useBuiltinEncoderDecoder = $previousValue;
        } catch (\Exception $e) {
            $decoded = null;
        }

        if ($decoded === null && $throwError) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Unable to decode JSON.',
                ['source' => $data]
            );
        }

        return $decoded;
    }

    //########################################

    /**
     * @param array|string $data
     * @return string
     * The return value can be json (in version > 2.2.0) or serialized string
     */
    public function serialize($data)
    {
        if ($this->serializerInterface !== null) {
            return $this->serializerInterface->serialize($data);
        }

        return $this->phpSerialize->serialize($data);
    }

    /**
     * @param string $data
     * @return array|string|null
     * $data can be json (in version > 2.2.0) or serialized string
     */
    public function unserialize($data)
    {
        if (empty($data) || !is_string($data)) {
            return [];
        }

        if (preg_match('/^((s|i|d|b|a|O|C):|N;)/', $data)) {
            return $this->phpSerialize->unserialize($data);
        }

        return $this->serializerInterface->unserialize($data);
    }

    //########################################

    public function getClassConstants($class)
    {
        $class = '\\' . ltrim($class, '\\');

        if (stripos($class, '\Ess\M2ePro\\') === false) {
            throw new \Ess\M2ePro\Model\Exception('Class name must begin with "\Ess\M2ePro"');
        }

        $reflectionClass = new \ReflectionClass($class);
        $tempConstants = $reflectionClass->getConstants();

        $constants = [];
        foreach ($tempConstants as $key => $value) {
            $constants[$class.'::'.strtoupper($key)] = $value;
        }

        return $constants;
    }

    public function getControllerActions($controllerClass, array $params = [])
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $controllerClass = str_replace('_', '\\', $controllerClass);

        $classRoute = str_replace('\\', '_', $controllerClass);
        $classRoute = implode('_', array_map('lcfirst', explode('_', $classRoute)));

        if (!$this->getHelper('Module')->isDevelopmentEnvironment()) {
            $cachedActions = $this->getHelper('Data_Cache_Permanent')->getValue('controller_actions_' . $classRoute);

            if ($cachedActions !== null) {
                return $this->getActionsUrlsWithParameters($cachedActions, $params);
            }
        }

        $controllersDir = $this->dir->getDir(
            \Ess\M2ePro\Helper\Module::IDENTIFIER,
            \Magento\Framework\Module\Dir::MODULE_CONTROLLER_DIR
        );
        $controllerDir = $controllersDir . '/Adminhtml/' . str_replace('\\', '/', $controllerClass);

        $actions = [];
        $controllerActions = array_diff(scandir($controllerDir), ['..', '.']);

        foreach ($controllerActions as $controllerAction) {
            $temp = explode('.php', $controllerAction);

            if (!empty($temp)) {
                $action = $temp[0];
                $action{0} = strtolower($action{0});

                $actions[] = $classRoute . '/' . $action;
            }
        }

        if (!$this->getHelper('Module')->isDevelopmentEnvironment()) {
            $this->getHelper('Data_Cache_Permanent')->setValue('controller_actions_' . $classRoute, $actions);
        }

        return $this->getActionsUrlsWithParameters($actions, $params);
    }

    private function getActionsUrlsWithParameters(array $actions, array $parameters = [])
    {
        $actionsUrls = [];
        foreach ($actions as $route) {
            $url = $this->urlBuilder->getUrl('m2epro/' . $route, $parameters);
            $actionsUrls[$route] = $url;
        }

        return $actionsUrls;
    }

    //########################################

    public function generateUniqueHash($strParam = null, $maxLength = null)
    {
        $hash = sha1(rand(1, 1000000).microtime(true).(string)$strParam);
        (int)$maxLength > 0 && $hash = substr($hash, 0, (int)$maxLength);
        return $hash;
    }

    public function theSameItemsInData($data, $keysToCheck)
    {
        if (count($data) > 200) {
            return false;
        }

        $preparedData = [];

        foreach ($keysToCheck as $key) {
            $preparedData[$key] = [];
        }

        foreach ($data as $item) {
            foreach ($keysToCheck as $key) {
                $preparedData[$key][] = $item[$key];
            }
        }

        foreach ($keysToCheck as $key) {
            $preparedData[$key] = array_unique($preparedData[$key]);
            if (count($preparedData[$key]) > 1) {
                return false;
            }
        }

        return true;
    }

    public function getMainStatus($statuses)
    {
        foreach ([self::STATUS_ERROR, self::STATUS_WARNING] as $status) {
            if (in_array($status, $statuses)) {
                return $status;
            }
        }

        return self::STATUS_SUCCESS;
    }

    //########################################

    public function makeBackUrlParam($backIdOrRoute, array $backParams = [])
    {
        $paramsString = !empty($backParams) ? '|'.http_build_query($backParams, '', '&') : '';
        return base64_encode($backIdOrRoute.$paramsString);
    }

    public function getBackUrlParam(
        $defaultBackIdOrRoute = 'index',
        array $defaultBackParams = []
    ) {
        $requestParams = $this->_getRequest()->getParams();
        return isset($requestParams['back']) ? $requestParams['back']
                                             : $this->makeBackUrlParam($defaultBackIdOrRoute, $defaultBackParams);
    }

    // ---------------------------------------

    public function getBackUrl(
        $defaultBackIdOrRoute = 'index',
        array $defaultBackParams = [],
        array $extendedRoutersParams = []
    ) {
        $back = $this->getBackUrlParam($defaultBackIdOrRoute, $defaultBackParams);
        $back = base64_decode($back);

        $params = [];

        if (strpos($back, '|') !== false) {
            $route = substr($back, 0, strpos($back, '|'));
            parse_str(substr($back, strpos($back, '|')+1), $params);
        } else {
            $route = $back;
        }

        $extendedRoutersParamsTemp = [];
        foreach ($extendedRoutersParams as $extRouteName => $extParams) {
            if ($route == $extRouteName) {
                $params = array_merge($params, $extParams);
            } else {
                $extendedRoutersParamsTemp[$route] = $params;
            }
        }
        $extendedRoutersParams = $extendedRoutersParamsTemp;

        $route == 'index' && $route = '*/*/index';
        $route == 'list' && $route = '*/*/index';
        $route == 'edit' && $route = '*/*/edit';
        $route == 'view' && $route = '*/*/view';

        foreach ($extendedRoutersParams as $extRouteName => $extParams) {
            if ($route == $extRouteName) {
                $params = array_merge($params, $extParams);
            }
        }

        $params['_escape_params'] = false;
        return $this->urlBuilder->getUrl($route, $params);
    }

    //########################################

    public function isISBN($string)
    {
        return $this->isISBN10($string) || $this->isISBN13($string);
    }

    // ---------------------------------------

    public function isISBN10($string)
    {
        if (strlen($string) != 10) {
            return false;
        }

        $a = 0;
        $string = (string)$string;

        for ($i = 0; $i < 10; $i++) {
            if ($string[$i] == "X" || $string[$i] == "x") {
                $a += 10 * intval(10 - $i);
            } elseif (is_numeric($string[$i])) {
                $a += intval($string[$i]) * intval(10 - $i);
            } else {
                return false;
            }
        }
        return ($a % 11 == 0);
    }

    public function isISBN13($string)
    {
        if (strlen($string) != 13) {
            return false;
        }

        if (substr($string, 0, 3) != '978') {
            return false;
        }

        $check = 0;
        for ($i = 0; $i < 13; $i += 2) {
            $check += (int)substr($string, $i, 1);
        }
        for ($i = 1; $i < 12; $i += 2) {
            $check += 3 * substr($string, $i, 1);
        }

        return $check % 10 == 0;
    }

    //########################################

    public function isGTIN($gtin)
    {
        return $this->isWorldWideId($gtin, 'GTIN');
    }

    public function isUPC($upc)
    {
        return $this->isWorldWideId($upc, 'UPC');
    }

    public function isEAN($ean)
    {
        return $this->isWorldWideId($ean, 'EAN');
    }

    // ---------------------------------------

    private function isWorldWideId($worldWideId, $type)
    {
        $adapters = [
            'UPC' => [
                '12' => 'Upca'
            ],
            'EAN' => [
                '13' => 'Ean13'
            ],
            'GTIN' => [
                '12' => 'Gtin12',
                '13' => 'Gtin13',
                '14' => 'Gtin14'
            ]
        ];

        $length = strlen($worldWideId);

        if (!isset($adapters[$type], $adapters[$type][$length])) {
            return false;
        }

        try {
            $validator = new \Zend_Validate_Barcode($adapters[$type][$length]);
            $result = $validator->isValid($worldWideId);
        } catch (\Zend_Validate_Exception $e) {
            return false;
        }

        return $result;
    }

    //########################################
}
