<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video\ProductProcessor;

class Result
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';
    private const STATUS_IN_PROGRESS = 'in_progress';

    private string $status;
    private string $failMessage;

    private function __construct(string $status, string $failMessage = '')
    {
        $this->status = $status;
        $this->failMessage = $failMessage;
    }

    //region Creators
    public static function createSuccess(): self
    {
        return new self(self::STATUS_SUCCESS);
    }

    public static function createInProgress(): self
    {
        return new self(self::STATUS_IN_PROGRESS);
    }

    public static function createFail(string $failMessage): self
    {
        return new self(self::STATUS_FAIL, $failMessage);
    }
    //endregion

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    public function getFailMessage(): string
    {
        return $this->failMessage;
    }
}
