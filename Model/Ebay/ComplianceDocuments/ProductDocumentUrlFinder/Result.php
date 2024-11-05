<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder;

class Result
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';

    private string $status;
    private string $type;
    private string $attributeCode;
    private string $url;
    private string $failMessage;

    private function __construct(
        string $status,
        string $type,
        string $attributeCode,
        string $url,
        string $failMessage
    ) {
        $this->status = $status;
        $this->type = $type;
        $this->attributeCode = $attributeCode;
        $this->url = $url;
        $this->failMessage = $failMessage;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    public function getFailMessage(): string
    {
        return $this->failMessage;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    // ----------------------------------------

    public static function createSuccess(string $type, $attributeCode, string $url): self
    {
        return new self(self::STATUS_SUCCESS, $type, $attributeCode, $url, '');
    }

    public static function createFail(string $type, string $attributeCode, string $failMessage): self
    {
        return new self(self::STATUS_FAIL, $type, $attributeCode, '', $failMessage);
    }
}
