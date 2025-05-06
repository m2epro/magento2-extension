<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor;

class Result
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';
    private const STATUS_IN_PROGRESS = 'in_progress';

    private string $status;
    private ?string $ebayDocumentId;
    private ?string $failMessage;

    private function __construct(
        string $status,
        ?string $ebayDocumentId = null,
        ?string $failMessage = null
    ) {
        $this->status = $status;
        $this->ebayDocumentId = $ebayDocumentId;
        $this->failMessage = $failMessage;
    }

    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getFailMessage(): string
    {
        return $this->failMessage;
    }

    public function hasEbayDocumentId(): bool
    {
        return !empty($this->ebayDocumentId);
    }

    // ----------------------------------------

    public static function createSuccess(
        string $ebayDocumentId
    ): self {
        return new self(self::STATUS_SUCCESS, $ebayDocumentId);
    }

    public static function createInProgress(
        ?string $ebayDocumentId = null
    ): self {
        return new self(self::STATUS_IN_PROGRESS, $ebayDocumentId);
    }

    public static function createFail(
        string $failMessage
    ): self {
        return new self(self::STATUS_FAIL, null, $failMessage);
    }
}
