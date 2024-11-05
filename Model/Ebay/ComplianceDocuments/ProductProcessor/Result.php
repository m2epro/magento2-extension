<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor;

class Result
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';
    private const STATUS_IN_PROGRESS = 'in_progress';

    private string $status;
    private string $type;
    private string $attributeCode;
    private ?string $url;
    private ?string $ebayDocumentId;
    private ?string $failMessage;

    private function __construct(
        string $status,
        string $type,
        string $attributeCode,
        ?string $url = null,
        ?string $ebayDocumentId = null,
        ?string $failMessage = null
    ) {
        $this->status = $status;
        $this->type = $type;
        $this->attributeCode = $attributeCode;
        $this->url = $url;
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
        string $type,
        string $attributeCode,
        string $url,
        string $ebayDocumentId
    ): self {
        return new self(self::STATUS_SUCCESS, $type, $attributeCode, $url, $ebayDocumentId);
    }

    public static function createInProgress(
        string $type,
        string $attributeCode,
        string $url,
        ?string $ebayDocumentId = null
    ): self {
        return new self(self::STATUS_IN_PROGRESS, $type, $attributeCode, $url, $ebayDocumentId);
    }

    public static function createFail(
        string $type,
        string $attributeCode,
        string $failMessage
    ): self {
        return new self(self::STATUS_FAIL, $type, $attributeCode, null, null, $failMessage);
    }
}
