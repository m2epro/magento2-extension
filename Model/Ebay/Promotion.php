<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\Promotion as EbayPromotionResource;

class Promotion extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const TYPE_CODED_COUPON = 'CODED_COUPON';
    public const TYPE_MARKDOWN_SALE = 'MARKDOWN_SALE';
    public const TYPE_ORDER_DISCOUNT = 'ORDER_DISCOUNT';
    public const TYPE_VOLUME_DISCOUNT = 'VOLUME_DISCOUNT';

    public const STATUS_SCHEDULED = 'SCHEDULED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ENDED = 'ENDED';
    public const STATUS_INVALID = 'INVALID';

    public const PRIORITY_1 = 'PRIORITY_1';
    public const PRIORITY_2 = 'PRIORITY_2';
    public const PRIORITY_3 = 'PRIORITY_3';
    public const PRIORITY_4 = 'PRIORITY_4';
    public const PRIORITY_INVALID = 'INVALID';

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(EbayPromotionResource::class);
    }

    public function init(
        int $accountId,
        int $marketplaceId,
        string $promotionId,
        string $name,
        string $type,
        string $status,
        string $priority,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate
    ): self {
        $this
            ->setData(EbayPromotionResource::COLUMN_ACCOUNT_ID, $accountId)
            ->setData(EbayPromotionResource::COLUMN_MARKETPLACE_ID, $marketplaceId)
            ->setData(EbayPromotionResource::COLUMN_PROMOTION_ID, $promotionId)
            ->setName($name)
            ->setData(EbayPromotionResource::COLUMN_TYPE, $type)
            ->setStatus($status)
            ->setPriority($priority)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        return $this;
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)$this->getDataByKey(EbayPromotionResource::COLUMN_ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getDataByKey(EbayPromotionResource::COLUMN_ACCOUNT_ID);
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getDataByKey(EbayPromotionResource::COLUMN_MARKETPLACE_ID);
    }

    public function getPromotionId(): string
    {
        return $this->getDataByKey(EbayPromotionResource::COLUMN_PROMOTION_ID);
    }

    // ----------------------------------------

    public function getName(): string
    {
        return $this->getDataByKey(EbayPromotionResource::COLUMN_NAME);
    }

    public function setName(string $name): self
    {
        $this->setData(EbayPromotionResource::COLUMN_NAME, $name);

        return $this;
    }

    public function isTypeWithDiscounts(): bool
    {
        return $this->isTypeMarkdownSale();
    }

    public function isTypeMarkdownSale(): bool
    {
        return $this->getType() === self::TYPE_MARKDOWN_SALE;
    }

    public function getType(): string
    {
        return $this->getDataByKey(EbayPromotionResource::COLUMN_TYPE);
    }

    public function isStatusScheduled(): bool
    {
        return $this->getStatus() === self::STATUS_SCHEDULED;
    }

    public function isStatusRunning(): bool
    {
        return $this->getStatus() === self::STATUS_RUNNING;
    }

    public function isStatusPaused(): bool
    {
        return $this->getStatus() === self::STATUS_PAUSED;
    }

    public function getStatus(): string
    {
        return $this->getDataByKey(EbayPromotionResource::COLUMN_STATUS);
    }

    public function setStatus(string $status): self
    {
        $this->validateStatus($status);

        $this->setData(EbayPromotionResource::COLUMN_STATUS, $status);

        return $this;
    }

    // ----------------------------------------

    public function getPriority(): string
    {
        return $this->getDataByKey(EbayPromotionResource::COLUMN_PRIORITY);
    }

    public function setPriority(string $priority): self
    {
        $this->setData(EbayPromotionResource::COLUMN_PRIORITY, $priority);

        return $this;
    }

    // ----------------------------------------

    public function getStartDate(): ?\DateTime
    {
        $startDate = $this->getDataByKey(EbayPromotionResource::COLUMN_START_DATE);

        if ($startDate !== null) {
            $startDate = \Ess\M2ePro\Helper\Date::createDateGmt($startDate);
        }

        return $startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $value = null;
        if ($startDate !== null) {
            $value = $startDate->format('Y-m-d H:i:s');
        }

        $this->setData(EbayPromotionResource::COLUMN_START_DATE, $value);

        return $this;
    }

    // ----------------------------------------

    public function getEndDate(): ?\DateTime
    {
        $endDate = $this->getDataByKey(EbayPromotionResource::COLUMN_END_DATE);

        if ($endDate !== null) {
            $endDate = \Ess\M2ePro\Helper\Date::createDateGmt($endDate);
        }

        return $endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $value = null;
        if ($endDate !== null) {
            $value = $endDate->format('Y-m-d H:i:s');
        }

        $this->setData(EbayPromotionResource::COLUMN_END_DATE, $value);

        return $this;
    }

    // ----------------------------------------

    public static function isAllowed(int $marketplaceId): bool
    {
        $supportedMarketplaces = [
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_AU,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_FR,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_DE,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_IT,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_ES,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_UK,
            \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_US,
        ];

        return in_array($marketplaceId, $supportedMarketplaces);
    }

    private function validateStatus(string $status): void
    {
        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_ENDED,
            self::STATUS_INVALID,
            self::STATUS_SCHEDULED,
            self::STATUS_RUNNING,
            self::STATUS_PAUSED,
        ];

        if (!in_array($status, $allowed)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Promotion status %s not valid', $status),
            );
        }
    }
}
