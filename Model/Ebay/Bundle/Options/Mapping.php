<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options;

use Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping as MappingResource;

class Mapping extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(MappingResource::class);
    }

    public function init(string $title, string $attributeCode): self
    {
        return $this
            ->setTitle($title)
            ->setAttributeCode($attributeCode);
    }

    public function setTitle(string $title): self
    {
        return $this->setData(MappingResource::COLUMN_OPTION_TITLE, $title);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(MappingResource::COLUMN_OPTION_TITLE);
    }

    public function setAttributeCode(string $attributeCode): self
    {
        return $this->setData(MappingResource::COLUMN_ATTRIBUTE_CODE, $attributeCode);
    }

    public function getAttributeCode(): string
    {
        return (string)$this->getData(MappingResource::COLUMN_ATTRIBUTE_CODE);
    }
}
