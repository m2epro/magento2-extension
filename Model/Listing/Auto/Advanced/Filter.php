<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Auto\Advanced;

class Filter extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    public const RULE_MODEL_PREFIX = 'auto_action_advanced_filter';

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::class);
    }

    public function isAddingModeNone(): bool
    {
        return $this->getAddingMode() === \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE;
    }

    public function isAutoDeletingModeNone(): bool
    {
        return $this->getDeletingMode() === \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE;
    }

    public function isAutoAddingAddNotVisibleYes(): bool
    {
        return $this->getAddingAddNotVisible() === \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES;
    }

    public function setComponentMode(string $mode): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_COMPONENT_MODE,
            $mode
        );

        return $this;
    }

    public function setListingId(int $listingId): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_LISTING_ID,
            $listingId
        );

        return $this;
    }

    public function getListingId(): int
    {
        return (int)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_LISTING_ID);
    }

    public function setTitle(string $title): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_TITLE,
            $title
        );

        return $this;
    }

    public function getTitle(): string
    {
        return (string)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_TITLE);
    }

    public function setAddingMode(int $addingMode): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ADDING_MODE,
            $addingMode
        );

        return $this;
    }

    public function getAddingMode(): int
    {
        return (int)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ADDING_MODE);
    }

    public function setAddingAddNotVisible(int $addingAddNotVisible): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ADDING_ADD_NOT_VISIBLE,
            $addingAddNotVisible
        );

        return $this;
    }

    public function getAddingAddNotVisible(): int
    {
        return (int)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ADDING_ADD_NOT_VISIBLE);
    }

    public function setDeletingMode(int $deletingMode): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_DELETING_MODE,
            $deletingMode
        );

        return $this;
    }

    public function getDeletingMode(): int
    {
        return (int)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_DELETING_MODE);
    }

    public function setCondition(string $condition): self
    {
        $this->setData(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_CONDITION,
            $condition
        );

        return $this;
    }

    public function getCondition(): string
    {
        return (string)$this->getData(\Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_CONDITION);
    }

    public function getConditionAttributes(): array
    {
        $conditionData = json_decode($this->getCondition(), true);
        if (!is_array($conditionData)) {
            return [];
        }

        $attributes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($conditionData),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $key => $value) {
            if ($key === 'attribute') {
                $attributes[] = $value;
            }
        }

        return array_unique($attributes);
    }
}
