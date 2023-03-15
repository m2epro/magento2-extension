<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class UserInfoFactory
{
    private const NOT_ALLOWED_CHARACTERS = [
        "'",
        ':',
        '"',
        ';',
        '/',
        '!',
        '"',
        '#',
        '&',
        '(',
        ')',
        '+',
        '?',
        '=',
        '§',
        '$',
        '%',
        '*',
        '~',
        '@',
        '€',
        '{',
        '[',
        ']',
        '}',
    ];

    /** @var \Magento\Customer\Model\Options */
    private $options;

    public function __construct(\Magento\Customer\Model\Options $options)
    {
        $this->options = $options;
    }

    public function create(
        string $rawFullName,
        \Magento\Store\Model\Store $store
    ): UserInfo {
        $fullName = str_replace(self::NOT_ALLOWED_CHARACTERS, ' ', $rawFullName);
        $fullName = preg_replace('/\s+/', ' ', $fullName);
        $fullName = trim($fullName);
        $parts = explode(' ', $fullName);
        $prefix = null;
        $middleName = null;
        $suffix = null;

        if (count($parts) > 2) {
            $prefixOptions = $this->options->getNamePrefixOptions($store);
            if (is_array($prefixOptions) && in_array($parts[0], $prefixOptions)) {
                $prefix = array_shift($parts);
            }
        }

        $partsCount = count($parts);
        if ($partsCount > 2) {
            $suffixOptions = $this->options->getNameSuffixOptions($store);
            if (is_array($suffixOptions) && in_array($parts[$partsCount - 1], $suffixOptions)) {
                $suffix = array_pop($parts);
            }
        }

        $partsCount = count($parts);
        if ($partsCount > 2) {
            $middleName = array_slice($parts, 1, $partsCount - 2);
            $middleName = implode(' ', $middleName);
            $parts = [$parts[0], $parts[$partsCount - 1]];
        }

        $firstName =  empty($parts[0]) ? 'NA' : $parts[0];
        $lastName = empty($parts[1]) ? $firstName : $parts[1];

        return new UserInfo($firstName, $middleName, $lastName, $prefix, $suffix);
    }
}
