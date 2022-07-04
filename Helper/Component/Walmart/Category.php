<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

class Category
{
    public const RECENT_MAX_COUNT = 20;

    /** @var \Magento\Framework\App\ResourceConnection  */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseStructure;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure,
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseStructure = $databaseStructure;
        $this->registry = $registry;
    }

    // ----------------------------------------

    public function getRecent($marketplaceId, array $excludedCategory = [])
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());

        if (!isset($allRecentCategories[$marketplaceId])) {
            return [];
        }

        $recentCategories = $allRecentCategories[$marketplaceId];

        foreach ($recentCategories as $index => $recentCategoryValue) {
            $isRecentCategoryExists = isset($recentCategoryValue['browsenode_id'], $recentCategoryValue['path']);

            $isCategoryEqualExcludedCategory = !empty($excludedCategory) &&
                ($excludedCategory['browsenode_id'] == $recentCategoryValue['browsenode_id'] &&
                 $excludedCategory['path']          == $recentCategoryValue['path']);

            if (!$isRecentCategoryExists || $isCategoryEqualExcludedCategory) {
                unset($recentCategories[$index]);
            }
        }

        // some categories can be not accessible in the current marketplaces build
        $this->removeNotAccessibleCategories($marketplaceId, $recentCategories);

        return array_reverse($recentCategories);
    }

    public function addRecent($marketplaceId, $browseNodeId, $categoryPath)
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());

        !isset($allRecentCategories[$marketplaceId]) && $allRecentCategories[$marketplaceId] = [];

        $recentCategories = $allRecentCategories[$marketplaceId];
        foreach ($recentCategories as $recentCategoryValue) {
            if (!isset($recentCategoryValue['browsenode_id'], $recentCategoryValue['path'])) {
                continue;
            }

            if ($recentCategoryValue['browsenode_id'] == $browseNodeId &&
                $recentCategoryValue['path'] == $categoryPath) {
                return;
            }
        }

        if (count($recentCategories) >= self::RECENT_MAX_COUNT) {
            array_shift($recentCategories);
        }

        $categoryInfo = [
            'browsenode_id' => $browseNodeId,
            'path'          => $categoryPath
        ];

        $recentCategories[] = $categoryInfo;
        $allRecentCategories[$marketplaceId] = $recentCategories;

        $this->registry->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    private function removeNotAccessibleCategories($marketplaceId, array &$recentCategories)
    {
        if (empty($recentCategories)) {
            return;
        }

        $nodeIdsForCheck = [];
        foreach ($recentCategories as $categoryData) {
            $nodeIdsForCheck[] = $categoryData['browsenode_id'];
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $this->databaseStructure->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $marketplaceId)
            ->where('browsenode_id IN (?)', array_unique($nodeIdsForCheck));

        $queryStmt = $select->query();
        $tempCategories = [];

        while ($row = $queryStmt->fetch()) {
            $path = $row['path'] ? $row['path'] .'>'. $row['title'] : $row['title'];
            $key = $row['browsenode_id'] .'##'. $path;
            $tempCategories[$key] = $row;
        }

        foreach ($recentCategories as $categoryKey => &$categoryData) {
            $categoryPath = str_replace(' > ', '>', $categoryData['path']);
            $key = $categoryData['browsenode_id'] .'##'. $categoryPath;

            if (!array_key_exists($key, $tempCategories)) {
                $this->removeRecentCategory($categoryData, $marketplaceId);
                unset($recentCategories[$categoryKey]);
            }
        }
    }

    private function removeRecentCategory(array $category, $marketplaceId)
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());

        if (!isset($allRecentCategories[$marketplaceId])) {
            return;
        }

        $currentRecentCategories = $allRecentCategories[$marketplaceId];

        foreach ($currentRecentCategories as $index => $recentCategory) {
            if ($category['browsenode_id'] == $recentCategory['browsenode_id'] &&
                $category['path']          == $recentCategory['path']) {
                unset($allRecentCategories[$marketplaceId][$index]);
                break;
            }
        }

        $this->registry->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    //########################################

    private function getConfigGroup()
    {
        return "/walmart/category/recent/";
    }

    //########################################
}
