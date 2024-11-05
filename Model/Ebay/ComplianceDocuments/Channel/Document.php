<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\Channel;

class Document
{
    public string $hash;
    public bool $isUploaded;
    public ?string $error;
    public ?string $ebayDocumentId;

    private function __construct(
        string $hash,
        bool $isUploaded,
        ?string $error,
        ?string $ebayDocumentId
    ) {
        $this->hash = $hash;
        $this->isUploaded = $isUploaded;
        $this->error = $error;
        $this->ebayDocumentId = $ebayDocumentId;
    }

    public static function createUploaded(string $hash, string $videoId): self
    {
        return new self($hash, true, null, $videoId);
    }

    public static function createNotUploaded(string $hash, string $error): self
    {
        return new self($hash, false, $error, null);
    }
}
