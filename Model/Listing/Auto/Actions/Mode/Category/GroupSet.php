<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category;

class GroupSet
{
    /** @var Group[] */
    private $groups = [];

    /**
     * @param Group[] $groups
     */
    public function __construct(array $groups = [])
    {
        foreach ($groups as $group) {
            $this->addGroup($group);
        }
    }

    /**
     * @param array $categoryIds
     *
     * @return $this
     */
    public function excludeGroupsThatContainsCategoryIds(array $categoryIds): self
    {
        return $this->filter(function (Group $group) use ($categoryIds) {
            return !$group->isContainsCategoryIds($categoryIds);
        });
    }

    /**
     * @param int $listingId
     * @param array $categoryIds
     * @param array $autoCategoryGroupIds
     *
     * @return void
     */
    public function fillGroupData(int $listingId, array $categoryIds, array $autoCategoryGroupIds): void
    {
        $categoryIds = array_unique($categoryIds);
        $autoCategoryGroupIds = array_unique($autoCategoryGroupIds);
        sort($categoryIds);
        sort($autoCategoryGroupIds);
        $this->addGroup(new Group($listingId, $categoryIds, $autoCategoryGroupIds));
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function filter(callable $callback): self
    {
        $groups = array_filter($this->groups, $callback);

        return new self($groups);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->getGroups());
    }

    /**
     * @param Group $group
     *
     * @return void
     */
    public function addGroup(Group $group): void
    {
        $this->groups[] = $group;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
