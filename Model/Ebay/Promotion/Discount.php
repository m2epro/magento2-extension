<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

use Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount as DiscountResource;

class Discount extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(DiscountResource::class);
    }

    public function init(
        int $promotionId,
        string $discountId,
        string $name
    ): self {
        $this
            ->setPromotionId($promotionId)
            ->setDiscountId($discountId)
            ->setName($name)
            ->setUpdateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt())
            ->setCreateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt());

        return $this;
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)$this->getDataByKey(DiscountResource::COLUMN_ID);
    }

    // ----------------------------------------

    public function getPromotionId(): int
    {
        return $this->getDataByKey(DiscountResource::COLUMN_PROMOTION_ID);
    }

    public function setPromotionId(int $promotionId): self
    {
        $this->setData(DiscountResource::COLUMN_PROMOTION_ID, $promotionId);

        return $this;
    }

    // ----------------------------------------

    public function getDiscountId(): string
    {
        return $this->getDataByKey(DiscountResource::COLUMN_DISCOUNT_ID);
    }

    public function setDiscountId(string $discountId): self
    {
        $this->setData(DiscountResource::COLUMN_DISCOUNT_ID, $discountId);

        return $this;
    }

    // ----------------------------------------

    public function getName(): string
    {
        return $this->getDataByKey(DiscountResource::COLUMN_NAME);
    }

    public function setName(string $name): self
    {
        $this->setData(DiscountResource::COLUMN_NAME, $name);

        return $this;
    }

    // ----------------------------------------

    public function getUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getDataByKey(DiscountResource::COLUMN_UPDATE_DATE)
        );
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->setData(DiscountResource::COLUMN_UPDATE_DATE, $updateDate->format('Y-m-d H:i:s'));

        return $this;
    }

    // ----------------------------------------

    public function getCreateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getDataByKey(DiscountResource::COLUMN_CREATE_DATE)
        );
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->setData(DiscountResource::COLUMN_CREATE_DATE, $createDate->format('Y-m-d H:i:s'));

        return $this;
    }
}
