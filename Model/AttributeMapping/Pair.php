<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeMapping;

use Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair as PairResource;

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
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $magentoAttributeCode
    ): self {
        $this->setData(PairResource::COLUMN_COMPONENT, $component)
             ->setData(PairResource::COLUMN_TYPE, $type)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE, $channelAttributeTitle)
             ->setData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE, $channelAttributeCode)
             ->setMagentoAttributeCode($magentoAttributeCode);

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

    public function getChannelAttributeTitle(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_TITLE);
    }

    public function getChannelAttributeCode(): string
    {
        return (string)$this->getData(PairResource::COLUMN_CHANNEL_ATTRIBUTE_CODE);
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
}
