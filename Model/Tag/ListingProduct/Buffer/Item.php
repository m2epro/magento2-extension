<?php

namespace Ess\M2ePro\Model\Tag\ListingProduct\Buffer;

class Item
{
    /** @var int */
    private $productId;
    /** @var array<string, \Ess\M2ePro\Model\Tag> */
    private $addedTags = [];
    /** @var array<string, \Ess\M2ePro\Model\Tag> */
    private $removedTags = [];

    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    public function addTag(\Ess\M2ePro\Model\Tag $tag): void
    {
        unset($this->removedTags[$tag->getErrorCode()]);
        $this->addedTags[$tag->getErrorCode()] = $tag;
    }

    public function removeTag(\Ess\M2ePro\Model\Tag $tag): void
    {
        unset($this->addedTags[$tag->getErrorCode()]);
        $this->removedTags[$tag->getErrorCode()] = $tag;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return \Ess\M2ePro\Model\Tag[]
     */
    public function getAddedTags(): array
    {
        return array_values($this->addedTags);
    }

    /**
     * @return \Ess\M2ePro\Model\Tag[]
     */
    public function getRemovedTags(): array
    {
        return array_values($this->removedTags);
    }
}
