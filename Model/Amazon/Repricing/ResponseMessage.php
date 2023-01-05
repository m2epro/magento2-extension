<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

class ResponseMessage
{
    public const TYPE_ERROR = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_NOTICE = 'notice';

    public const DEFAULT_CODE = 0;
    public const NOT_FOUND_ACCOUNT_CODE = 100;

    /** @var int */
    private $code;
    /** @var string */
    private $text;
    /** @var string */
    private $type;

    /**
     * @param string $text
     * @param string $type
     * @param int $code
     */
    public function __construct(
        string $text,
        string $type = self::TYPE_WARNING,
        int $code = self::DEFAULT_CODE
    ) {
        $this->text = $text;
        $this->type = $type;
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return $this->type === self::TYPE_ERROR;
    }

    /**
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->type === self::TYPE_WARNING;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->type === self::TYPE_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isNotice(): bool
    {
        return $this->type === self::TYPE_NOTICE;
    }
}
