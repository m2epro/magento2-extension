<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video\Channel;

class Video
{
    private string $url;
    private bool $isUploaded;
    private ?string $error;
    private ?string $videoId;

    private function __construct(
        string $url,
        bool $isUploaded,
        ?string $error,
        ?string $videoId
    ) {
        $this->url = $url;
        $this->isUploaded = $isUploaded;
        $this->error = $error;
        $this->videoId = $videoId;
    }

    public static function createUploaded(string $url, string $videoId): Video
    {
        return new self($url, true, null, $videoId);
    }

    public static function createNotUploaded(string $url, string $error): Video
    {
        return new self($url, false, $error, null);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isUploaded(): bool
    {
        return $this->isUploaded;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getVideoId(): ?string
    {
        return $this->videoId;
    }
}
