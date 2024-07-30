<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\Video as EbayVideoResource;

class Video extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_UPLOADING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED = 3;

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(EbayVideoResource::class);
    }

    public function init(
        int $accountId,
        string $url
    ): self {
        $this->setData(EbayVideoResource::COLUMN_ACCOUNT_ID, $accountId);
        $this->setData(EbayVideoResource::COLUMN_URL, $url);
        $this->setStatus(self::STATUS_PENDING);

        return $this;
    }

    // ----------------------------------------

    public function getAccountId(): int
    {
        return (int)$this->getDataByKey(EbayVideoResource::COLUMN_ACCOUNT_ID);
    }

    public function getUrl(): string
    {
        return (string)$this->getDataByKey(EbayVideoResource::COLUMN_URL);
    }

    // ----------------------------------------

    public function setStatusPending(): void
    {
        $this->setStatus(self::STATUS_PENDING);
    }

    public function setStatusUploading(): void
    {
        $this->setStatus(self::STATUS_UPLOADING);
    }

    public function setStatusSuccess(string $videoId): void
    {
        $this->setStatus(self::STATUS_SUCCESS);
        $this->setVideoId($videoId);
    }

    public function setStatusFailed(string $error): void
    {
        $this->setStatus(self::STATUS_FAILED);
        $this->setError($error);
    }

    public function isStatusPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    public function isStatusUploading(): bool
    {
        return $this->getStatus() === self::STATUS_UPLOADING;
    }

    public function isStatusSuccess(): bool
    {
        return $this->getStatus() === self::STATUS_SUCCESS;
    }

    public function isStatusFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAILED;
    }

    private function getStatus(): int
    {
        return (int)$this->getDataByKey(EbayVideoResource::COLUMN_STATUS);
    }

    private function setStatus(int $status): void
    {
        $this->setData(EbayVideoResource::COLUMN_STATUS, $status);
    }

    // ----------------------------------------

    public function getVideoId(): string
    {
        return (string)$this->getDataByKey(EbayVideoResource::COLUMN_VIDEO_ID);
    }

    private function setVideoId(string $videoId): void
    {
        $this->setData(EbayVideoResource::COLUMN_VIDEO_ID, $videoId);
    }

    // ----------------------------------------

    public function getError(): string
    {
        return (string)$this->getDataByKey(EbayVideoResource::COLUMN_ERROR);
    }

    private function setError(string $error): void
    {
        $this->setData(EbayVideoResource::COLUMN_ERROR, $error);
    }
}
