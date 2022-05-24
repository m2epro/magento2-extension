<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

class Category extends \Ess\M2ePro\Helper\AbstractHelper
{
    const RECENT_MAX_COUNT = 20;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module */
    protected $helperModule;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    protected $databaseStructure;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseStructure
    ) {
        parent::__construct($helperFactory, $context);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        $this->helperModule = $helperModule;
        $this->databaseStructure = $databaseStructure;
    }

    //########################################

    public function getRecent($marketplaceId, array $excludedCategory = [])
    {
        $allRecentCategories = $this->helperModule->getRegistry()->getValueFromJson($this->getConfigGroup());

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
        $allRecentCategories = $this->helperModule->getRegistry()->getValueFromJson($this->getConfigGroup());

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

        $this->helperModule->getRegistry()->setValue($this->getConfigGroup(), $allRecentCategories);
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
        $allRecentCategories = $this->helperModule->getRegistry()->getValueFromJson($this->getConfigGroup());

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

        $this->helperModule->getRegistry()->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    //########################################

    private function getConfigGroup()
    {
        return "/walmart/category/recent/";
    }

    //########################################
}
