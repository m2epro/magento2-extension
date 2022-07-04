<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Data
{
    public const STATUS_ERROR = 1;
    public const STATUS_WARNING = 2;
    public const STATUS_SUCCESS = 3;

    public const INITIATOR_UNKNOWN = 0;
    public const INITIATOR_USER = 1;
    public const INITIATOR_EXTENSION = 2;
    public const INITIATOR_DEVELOPER = 3;

    public const CUSTOM_IDENTIFIER = 'm2epro_extension';

    /** @var \Magento\Framework\Module\Dir */
    private $dir;
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializerInterface;
    /** @var \Magento\Framework\App\RequestInterface */
    private $httpRequest;
    private $phpSerialize;
    /** @var \Ess\M2ePro\Helper\Date */
    private $dateHelper;

    /**
     * @param \Magento\Framework\Module\Dir $dir
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Magento\Framework\App\RequestInterface $httpRequest
     */
    public function __construct(
        \Magento\Framework\Module\Dir $dir,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Date $dateHelper,
        \Magento\Framework\App\RequestInterface $httpRequest
    ) {
        $this->dir = $dir;
        $this->urlBuilder = $urlBuilder;
        $this->objectManager = $objectManager;
        $this->httpRequest = $httpRequest;
        $this->dateHelper = $dateHelper;

        if (version_compare($magentoHelper->getVersion(), '2.4.3', '<')) {
            $this->phpSerialize = version_compare($magentoHelper->getVersion(), '2.3.5', '>=')
                ? \Laminas\Serializer\Serializer::getDefaultAdapter()
                : \Zend\Serializer\Serializer::getDefaultAdapter();
        }

        if (interface_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $this->serializerInterface = $this->objectManager->get(
                \Magento\Framework\Serialize\SerializerInterface::class
            );
        }
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getConfigTimezone(): string
    {
        return $this->dateHelper->getConfigTimezone();
    }

    /**
     * @return string
     */
    public function getDefaultTimezone(): string
    {
        return $this->dateHelper->getDefaultTimezone();
    }

    // ---------------------------------------

    /**
     * @param string $timeString
     *
     * @return \DateTime
     */
    public function createGmtDateTime($timeString): \DateTime
    {
        return $this->dateHelper->createGmtDateTime($timeString);
    }

    /**
     * @return \DateTime
     */
    public function createCurrentGmtDateTime(): \DateTime
    {
        return $this->dateHelper->createGmtDateTime('now');
    }

    // ---------------------------------------

    /**
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentGmtDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->getCurrentGmtDate($returnTimestamp, $format);
    }

    /**
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentTimezoneDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->getCurrentTimezoneDate($returnTimestamp, $format);
    }

    // ---------------------------------------

    /**
     * @param string $date
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function gmtDateToTimezone($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->gmtDateToTimezone($date, $returnTimestamp, $format);
    }

    /**
     * @param string $date
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function timezoneDateToGmt($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->timezoneDateToGmt($date, $returnTimestamp, $format);
    }

    // ---------------------------------------

    /**
     * @param string $localDate
     * @param int $localIntlDateFormat
     * @param int $localIntlTimeFormat
     * @param string|null $localTimezone
     *
     * @return false|float|int
     */
    public function parseTimestampFromLocalizedFormat(
        $localDate,
        $localIntlDateFormat = \IntlDateFormatter::SHORT,
        $localIntlTimeFormat = \IntlDateFormatter::SHORT,
        $localTimezone = null
    ) {
        return $this->dateHelper->parseTimestampFromLocalizedFormat(
            $localDate,
            $localIntlDateFormat,
            $localIntlTimeFormat,
            $localTimezone
        );
    }

    // ----------------------------------------

    public function escapeJs($string)
    {
        if ($string === null) {
            return '';
        }

        return str_replace(
            ["\\", "\n", "\r", "\"", "'"],
            ["\\\\", "\\n", "\\r", "\\\"", "\\'"],
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

                    $pattern = '/<([\/\s\r\n]*)(' . $allowed . ')' .
                        '((\s+\w+=["\'][\w\s\%\?=\&#\/\.,;:_\-\(\)]*["\'])*[\/\s\r\n]*)>/si';
                    $result = preg_replace($pattern, '##$1$2$3##', $data);

                    $result = htmlspecialchars($result, $flags);

                    $pattern = '/##([\/\s\r\n]*)(' . $allowed . ')' .
                        '((\s+\w+=["\'][\w\s\%\?=\&#\/\.,;:_\-\(\)]*["\'])*[\/\s\r\n]*)##/si';
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

    // ----------------------------------------

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

    // ----------------------------------------

    public function convertStringToSku($title)
    {
        $skuVal = strtolower($title);
        $skuVal = str_replace([
            " ",
            ":",
            ",",
            ".",
            "?",
            "*",
            "+",
            "(",
            ")",
            "&",
            "%",
            "$",
            "#",
            "@",
            "!",
            '"',
            "'",
            ";",
            "\\",
            "|",
            "/",
            "<",
            ">",
        ], "-", $skuVal);

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
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                "\n\$0",
                "\n\$0",
                "\n\$0",
                "\n\$0",
                "\n\$0",
                "\n\$0",
                "\n\$0",
                "\n\$0",
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

    /**
     * @param string $string
     * @param int $neededLength
     * @param int $longWord
     * @param int $minWordLen
     * @param int $atEndOfWord
     *
     * @return string
     */
    public function reduceWordsInString(
        $string,
        $neededLength,
        $longWord = 6,
        $minWordLen = 2,
        $atEndOfWord = '.'
    ) {
        $oldEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        $string = (string)$string;
        if (mb_strlen($string) <= $neededLength) {
            mb_internal_encoding($oldEncoding);

            return $string;
        }

        $longWords = [];
        foreach (explode(' ', $string) as $word) {
            if (mb_strlen($word) >= $longWord && !preg_match('/\d/', $word)) {
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

    public function arrayReplaceRecursive($base, $replacements)
    {
        $args = func_get_args();
        foreach (array_slice($args, 1) as $replacements) {
            $bref_stack = [&$base];
            $head_stack = [$replacements];

            do {
                end($bref_stack);

                $bref = &$bref_stack[key($bref_stack)];
                $head = array_pop($head_stack);

                unset($bref_stack[key($bref_stack)]);

                foreach (array_keys($head) as $key) {
                    if (isset($key, $bref, $bref[$key]) && is_array($bref[$key]) && is_array($head[$key])) {
                        $bref_stack[] = &$bref[$key];
                        $head_stack[] = $head[$key];
                    } else {
                        $bref[$key] = $head[$key];
                    }
                }
            } while (count($head_stack));
        }

        return $base;
    }

    /**
     * @param array $data
     *
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

    // ----------------------------------------

    /**
     * @param $string
     * @param null $prefix
     * @param string $hashFunction (md5, sh1)
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function hashString($string, $hashFunction, $prefix = null)
    {
        if (!is_callable($hashFunction)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Hash function can not be called');
        }

        $hash = call_user_func($hashFunction, $string);

        return !empty($prefix) ? $prefix . $hash : $hash;
    }

    //########################################

    /**
     * It prevents situations when json_encode() returns FALSE due to some broken bytes sequence.
     * Normally normalizeToUtfEncoding() fixes that
     *
     * @param $data
     * @param bool $throwError
     *
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
     *
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

    // ----------------------------------------

    /**
     * @param array|string $data
     *
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
     *
     * @return array|string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * $data can be json (in version > 2.2.0) or serialized string
     */
    public function unserialize($data)
    {
        if (empty($data) || !is_string($data)) {
            return [];
        }

        try {
            return $this->phpSerialize !== null && preg_match('/^((s|i|d|b|a|O|C):|N;)/', $data)
                ? $this->phpSerialize->unserialize($data)
                : $this->serializerInterface->unserialize($data);
        } catch (\Exception $e) {
            // Circular dependency
            $this->objectManager->get(\Ess\M2ePro\Helper\Module\Exception::class)->process($e);

            return [];
        }
    }

    public function phpUnserialize($data)
    {
        return $this->phpSerialize->unserialize($data);
    }

    public function phpSerialize($data)
    {
        return $this->phpSerialize->serialize($data);
    }

    // ----------------------------------------

    /**
     * @param string $class
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \ReflectionException
     */
    public function getClassConstants($class): array
    {
        $class = '\\' . ltrim($class, '\\');

        if (stripos($class, '\Ess\M2ePro\\') === false) {
            throw new \Ess\M2ePro\Model\Exception('Class name must begin with "\Ess\M2ePro"');
        }

        $reflectionClass = new \ReflectionClass($class);
        $tempConstants = $reflectionClass->getConstants();

        $constants = [];
        foreach ($tempConstants as $key => $value) {
            $constants[$class . '::' . strtoupper($key)] = $value;
        }

        return $constants;
    }

    /**
     * @param $controllerClass
     * @param array $params
     * @param bool $skipEnvironmentCheck
     * m2epro_config table may be missing if migration is going on, so trying to check environment will cause SQL error
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getControllerActions($controllerClass, array $params = [], $skipEnvironmentCheck = false)
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $controllerClass = str_replace('_', '\\', $controllerClass);

        $classRoute = str_replace('\\', '_', $controllerClass);
        $classRoute = implode('_', array_map('lcfirst', explode('_', $classRoute)));

        $moduleHelper = $this->objectManager->get(\Ess\M2ePro\Helper\Module::class);
        if ($skipEnvironmentCheck || !$moduleHelper->isDevelopmentEnvironment()) {
            $cachedActions = $this->objectManager->get(\Ess\M2ePro\Helper\Data\Cache\Permanent::class)
                                                 ->getValue('controller_actions_' . $classRoute);

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
                $action[0] = strtolower($action[0]);

                $actions[] = $classRoute . '/' . $action;
            }
        }

        if ($skipEnvironmentCheck || !$moduleHelper->isDevelopmentEnvironment()) {
            $this->objectManager->get(\Ess\M2ePro\Helper\Data\Cache\Permanent::class)
                                ->setValue('controller_actions_' . $classRoute, $actions);
        }

        return $this->getActionsUrlsWithParameters($actions, $params);
    }

    /**
     * @param array $actions
     * @param array $parameters
     *
     * @return array
     */
    private function getActionsUrlsWithParameters(array $actions, array $parameters = []): array
    {
        $actionsUrls = [];
        foreach ($actions as $route) {
            $url = $this->urlBuilder->getUrl('m2epro/' . $route, $parameters);
            $actionsUrls[$route] = $url;
        }

        return $actionsUrls;
    }

    // ----------------------------------------

    /**
     * @param string|null $strParam
     * @param int|null $maxLength
     *
     * @return string
     */
    public function generateUniqueHash($strParam = null, $maxLength = null): string
    {
        $hash = sha1(rand(1, 1000000) . microtime(true) . (string)$strParam);
        (int)$maxLength > 0 && $hash = substr($hash, 0, (int)$maxLength);

        return $hash;
    }

    /**
     * @param array $data
     * @param array $keysToCheck
     *
     * @return bool
     */
    public function theSameItemsInData($data, $keysToCheck): bool
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

    /**
     * @param array $statuses
     *
     * @return int
     */
    public function getMainStatus($statuses): int
    {
        foreach ([self::STATUS_ERROR, self::STATUS_WARNING] as $status) {
            if (in_array($status, $statuses)) {
                return $status;
            }
        }

        return self::STATUS_SUCCESS;
    }

    // ----------------------------------------

    /**
     * @param string $backIdOrRoute
     * @param array $backParams
     *
     * @return string
     */
    public function makeBackUrlParam($backIdOrRoute, array $backParams = []): string
    {
        $paramsString = !empty($backParams) ? '|' . http_build_query($backParams, '', '&') : '';

        return base64_encode($backIdOrRoute . $paramsString);
    }

    /**
     * @param string $defaultBackIdOrRoute
     * @param array $defaultBackParams
     *
     * @return string
     */
    public function getBackUrlParam(
        $defaultBackIdOrRoute = 'index',
        array $defaultBackParams = []
    ) {
        $requestParams = $this->httpRequest->getParams();

        return $requestParams['back'] ?? $this->makeBackUrlParam($defaultBackIdOrRoute, $defaultBackParams);
    }

    // ---------------------------------------

    /**
     * @param string $defaultBackIdOrRoute
     * @param array $defaultBackParams
     * @param array $extendedRoutersParams
     *
     * @return string
     */
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
            parse_str(substr($back, strpos($back, '|') + 1), $params);
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

    // ----------------------------------------

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isISBN($string): bool
    {
        return $this->isISBN10($string) || $this->isISBN13($string);
    }

    // ---------------------------------------

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isISBN10($string): bool
    {
        $string = (string)$string;
        if (strlen($string) !== 10) {
            return false;
        }

        $a = 0;
        for ($i = 0; $i < 10; $i++) {
            if ($string[$i] === "X" || $string[$i] === "x") {
                $a += 10 * 10 - $i;
            } elseif (is_numeric($string[$i])) {
                $a += (int)$string[$i] * 10 - $i;
            } else {
                return false;
            }
        }

        return $a % 11 === 0;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isISBN13($string): bool
    {
        $string = (string)$string;
        if (strlen($string) !== 13) {
            return false;
        }

        if (strpos($string, '978') !== 0) {
            return false;
        }

        $check = 0;
        for ($i = 0; $i < 13; $i += 2) {
            $check += (int)$string[$i];
        }
        for ($i = 1; $i < 12; $i += 2) {
            $check += 3 * $string[$i];
        }

        return $check % 10 === 0;
    }

    // ----------------------------------------

    /**
     * @param string $gtin
     *
     * @return bool
     */
    public function isGTIN($gtin): bool
    {
        return $this->isWorldWideId($gtin, 'GTIN');
    }

    /**
     * @param string $upc
     *
     * @return bool
     */
    public function isUPC($upc): bool
    {
        return $this->isWorldWideId($upc, 'UPC');
    }

    /**
     * @param string $ean
     *
     * @return bool
     */
    public function isEAN($ean): bool
    {
        return $this->isWorldWideId($ean, 'EAN');
    }

    // ---------------------------------------

    /**
     * @param string $worldWideId
     * @param string $type
     *
     * @return bool
     */
    private function isWorldWideId($worldWideId, $type): bool
    {
        $adapters = [
            'UPC' => [
                12 => 'Upca',
            ],
            'EAN' => [
                13 => 'Ean13',
            ],
            'GTIN' => [
                12 => 'Gtin12',
                13 => 'Gtin13',
                14 => 'Gtin14',
            ]
        ];

        $length = strlen((string)$worldWideId);

        if (!isset($adapters[$type][$length])) {
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
}
