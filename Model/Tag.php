<?php

namespace Ess\M2ePro\Model;

class Tag
{
    public const HAS_ERROR_ERROR_CODE = 'has_error';

    /** @var string */
    private $errorCode;
    /** @var string */
    private $text;

    public function __construct(string $errorCode, string $text)
    {
        $this->errorCode = $errorCode;
        $this->text = $text;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
