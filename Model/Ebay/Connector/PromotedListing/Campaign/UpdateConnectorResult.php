<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class UpdateConnectorResult
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';

    private string $status;
    private array $failMessages;

    private function __construct(string $status, array $failMessages = [])
    {
        $this->status = $status;
        $this->failMessages = $failMessages;
    }

    public static function createSuccess(): self
    {
        return new self(self::STATUS_SUCCESS, []);
    }

    public static function createFail(array $failMessages): self
    {
        return new self(self::STATUS_FAIL, $failMessages);
    }

    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    public function getFailMessages(): array
    {
        return $this->failMessages;
    }
}
