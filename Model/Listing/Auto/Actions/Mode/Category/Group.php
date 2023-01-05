<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category;

class Group
{
    /** @var int */
    private $listingId;
    /** @var array */
    private $categoryIds;
    /*** @var array */
    private $autoCategoryGroupIds;

    /**
     * @param int $listingId
     * @param array $categoryIds
     * @param array $autoCategoryGroupIds
     */
    public function __construct(int $listingId, array $categoryIds, array $autoCategoryGroupIds)
    {
        $this->listingId = $listingId;
        $this->categoryIds = $categoryIds;
        $this->autoCategoryGroupIds = $autoCategoryGroupIds;
    }

    /**
     * @param array $categoryIds
     *
     * @return bool
     */
    public function isContainsCategoryIds(array $categoryIds): bool
    {
        foreach ($categoryIds as $id) {
            if (in_array($id, $this->getCategoryIds())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getListingId(): int
    {
        return $this->listingId;
    }

    /**
     * @return array
     */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    /**
     * @return array
     */
    public function getAutoCategoryGroupIds(): array
    {
        return $this->autoCategoryGroupIds;
    }
}
