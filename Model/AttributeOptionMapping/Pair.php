<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeOptionMapping;

use Ess\M2ePro\Model\ResourceModel\AttributeOptionMapping\Pair as PairResource;

class Pair extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(PairResource::class);
    }

    public function create(
        string $component,
        string $type,
        int $productTypeId,
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $channelOptionTitle,
        string $channelOptionCode,
        string $magentoAttributeCode,
        int $magentoOptionId,
        string $magentoOptionTitle
    ): self {
        $this->setData(PairResource::COLUMN_COMPONENT, $component)
             ->setData(PairResource::COLUMN_TYPE, $type)
             ->setData(PairResource::COLUMN_PRODUCT_TYPE_ID, $productTypeId)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE, $channelAttributeTitle)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE, $channelAttributeCode)
             ->setData(PairResource::COLUMN_CHANNEL_OPTION_TITLE, $channelOptionTitle)
             ->setData(PairResource::COLUMN_CHANNEL_OPTION_CODE, $channelOptionCode)
             ->setData(PairResource::COLUMN_MAGENTO_ATTRIBUTE_CODE, $magentoAttributeCode)
             ->setData(PairResource::COLUMN_MAGENTO_OPTION_ID, $magentoOptionId)
             ->setData(PairResource::COLUMN_MAGENTO_OPTION_TITLE, $magentoOptionTitle);

        return $this;
    }

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getComponent(): string
    {
        return (string)$this->getData(PairResource::COLUMN_COMPONENT);
    }

    public function getType(): string
    {
        return (string)$this->getData(PairResource::COLUMN_TYPE);
    }

    public function getProductTypeId(): int
    {
        return (int)$this->getData(PairResource::COLUMN_PRODUCT_TYPE_ID);
    }

    public function getChannelAttributeTitle(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE);
    }

    public function getChannelAttributeCode(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE);
    }

    public function getChannelOptionCode(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_OPTION_CODE);
    }

    public function getChannelOptionTitle(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_OPTION_TITLE);
    }

    public function setMagentoAttributeCode(string $magentoAttributeCode): self
    {
        $this->setData(PairResource::COLUMN_MAGENTO_ATTRIBUTE_CODE, $magentoAttributeCode);

        return $this;
    }

    public function getMagentoAttributeCode(): string
    {
        return (string)$this->getData(PairResource::COLUMN_MAGENTO_ATTRIBUTE_CODE);
    }

    public function setMagentoOptionId(int $magentoOptionId): self
    {
        $this->setData(PairResource::COLUMN_MAGENTO_OPTION_ID, $magentoOptionId);

        return $this;
    }

    public function getMagentoOptionId(): int
    {
        return (int)$this->getData(PairResource::COLUMN_MAGENTO_OPTION_ID);
    }

    public function setMagentoOptionTitle(string $magentoOptionTitle): self
    {
        $this->setData(PairResource::COLUMN_MAGENTO_OPTION_TITLE, $magentoOptionTitle);

        return $this;
    }

    public function getMagentoOptionTitle(): string
    {
        return (string)$this->getData(PairResource::COLUMN_MAGENTO_OPTION_TITLE);
    }
}
