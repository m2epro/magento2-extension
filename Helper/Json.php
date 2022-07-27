<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Json
{
    /**
     * @param mixed $data
     * @param bool $throwError
     *
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public static function encode($data, $throwError = true): ?string
    {
        if ($data === false) {
            return 'false';
        }

        $encoded = json_encode($data);
        if ($encoded !== false) {
            return $encoded;
        }

        $encoded = json_encode(\Ess\M2ePro\Helper\Data::normalizeToUtf($data));
        if ($encoded !== false) {
            return $encoded;
        }

        if (!$throwError) {
            return null;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic(
            'Unable to encode to JSON.',
            ['error' => json_last_error_msg()]
        );
    }

    /**
     * @param string $data
     * @param bool $throwError
     *
     * @return array|bool|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public static function decode($data, $throwError = false)
    {
        if ($data === null || $data === '' || strtolower($data) === 'null') {
            return null;
        }

        $decoded = json_decode($data, true);
        if ($decoded !== null) {
            return $decoded;
        }

        if ($throwError) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Unable to decode JSON.',
                ['source' => $data]
            );
        }

        return $decoded;
    }
}
