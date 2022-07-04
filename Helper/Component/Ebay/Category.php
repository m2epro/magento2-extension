<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay;

class Category
{
    public const TYPE_EBAY_MAIN = 0;
    public const TYPE_EBAY_SECONDARY = 1;
    public const TYPE_STORE_MAIN = 2;
    public const TYPE_STORE_SECONDARY = 3;

    public const RECENT_MAX_COUNT = 20;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Store */
    private $componentEbayCategoryStore;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;

    /**
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay
     * @param \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     * @param \Ess\M2ePro\Helper\Module\Translation $translation
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Helper\Module\Translation $translation
    ) {
        $this->componentEbayCategoryStore = $componentEbayCategoryStore;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->registry = $registry;
        $this->translation = $translation;
    }

    // ----------------------------------------

    public function isEbayCategoryType($type)
    {
        /*
         * dirty hack because of integer constants: in_array('any-string', []) returns true
         * in_array($type, [], true) CAN NOT be used!
         */
        if ((strlen($type) !== 1)) {
            return false;
        }

        return in_array((int)$type, $this->getEbayCategoryTypes());
    }

    public function isStoreCategoryType($type)
    {
        /*
         * dirty hack because of integer constants: in_array('any-string', []) returns true
         * in_array($type, [], true) CAN NOT be used!
         */
        if ((strlen($type) !== 1)) {
            return false;
        }

        return in_array((int)$type, $this->getStoreCategoryTypes());
    }

    public function getCategoriesTypes()
    {
        return array_merge(
            $this->getEbayCategoryTypes(),
            $this->getStoreCategoryTypes()
        );
    }

    public function getEbayCategoryTypes()
    {
        return [
            self::TYPE_EBAY_MAIN,
            self::TYPE_EBAY_SECONDARY,
        ];
    }

    public function getStoreCategoryTypes()
    {
        return [
            self::TYPE_STORE_MAIN,
            self::TYPE_STORE_SECONDARY,
        ];
    }

    // ----------------------------------------

    public function getRecent($marketplaceOrAccountId, $categoryType, $excludeCategory = null)
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());
        $configPath = $this->getRecentConfigPath($categoryType);

        if (
            !isset($allRecentCategories[$configPath]) ||
            !isset($allRecentCategories[$configPath][$marketplaceOrAccountId])
        ) {
            return [];
        }

        $recentCategories = $allRecentCategories[$configPath][$marketplaceOrAccountId];

        if (in_array($categoryType, $this->getEbayCategoryTypes())) {
            $categoryHelper = $this->componentEbayCategoryEbay;
        } else {
            $categoryHelper = $this->componentEbayCategoryStore;
        }

        $categoryIds = (array)explode(',', $recentCategories);
        $result = [];
        foreach ($categoryIds as $categoryId) {
            if ($categoryId === $excludeCategory) {
                continue;
            }

            $path = $categoryHelper->getPath($categoryId, $marketplaceOrAccountId);
            if (empty($path)) {
                continue;
            }

            $result[] = [
                'id'   => $categoryId,
                'path' => $path . ' (' . $categoryId . ')',
            ];
        }

        return $result;
    }

    public function addRecent($categoryId, $marketplaceOrAccountId, $categoryType)
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());
        $configPath = $this->getRecentConfigPath($categoryType);

        $categories = [];
        if (isset($allRecentCategories[$configPath][$marketplaceOrAccountId])) {
            $categories = (array)explode(',', $allRecentCategories[$configPath][$marketplaceOrAccountId]);
        }

        if (count($categories) >= self::RECENT_MAX_COUNT) {
            array_pop($categories);
        }

        array_unshift($categories, $categoryId);
        $categories = array_unique($categories);

        $allRecentCategories[$configPath][$marketplaceOrAccountId] = implode(',', $categories);

        $this->registry->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    public function removeEbayRecent()
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());

        foreach ($this->getEbayCategoryTypes() as $categoryType) {
            unset($allRecentCategories[$this->getRecentConfigPath($categoryType)]);
        }

        $this->registry->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    public function removeStoreRecent()
    {
        $allRecentCategories = $this->registry->getValueFromJson($this->getConfigGroup());

        foreach ($this->getStoreCategoryTypes() as $categoryType) {
            unset($allRecentCategories[$this->getRecentConfigPath($categoryType)]);
        }

        $this->registry->setValue($this->getConfigGroup(), $allRecentCategories);
    }

    // ---------------------------------------

    protected function getRecentConfigPath($categoryType)
    {
        $configPaths = [
            self::TYPE_EBAY_MAIN       => '/ebay/main/',
            self::TYPE_EBAY_SECONDARY  => '/ebay/secondary/',
            self::TYPE_STORE_MAIN      => '/store/main/',
            self::TYPE_STORE_SECONDARY => '/store/secondary/',
        ];

        return $configPaths[$categoryType];
    }

    //########################################

    //todo categories: remove?
    public function fillCategoriesPaths(array &$data, \Ess\M2ePro\Model\Listing $listing)
    {
        $ebayCategoryHelper = $this->componentEbayCategoryEbay;
        $ebayStoreCategoryHelper = $this->componentEbayCategoryStore;

        $temp = [
            'category_main'            => [
                'call' => [$ebayCategoryHelper, 'getPath'],
                'arg'  => $listing->getMarketplaceId(),
            ],
            'category_secondary'       => [
                'call' => [$ebayCategoryHelper, 'getPath'],
                'arg'  => $listing->getMarketplaceId(),
            ],
            'store_category_main'      => [
                'call' => [$ebayStoreCategoryHelper, 'getPath'],
                'arg'  => $listing->getAccountId(),
            ],
            'store_category_secondary' => [
                'call' => [$ebayStoreCategoryHelper, 'getPath'],
                'arg'  => $listing->getAccountId(),
            ],
        ];

        foreach ($temp as $key => $value) {
            if (!isset($data[$key . '_mode']) || !empty($data[$key . '_path'])) {
                continue;
            }

            if ($data[$key . '_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
                $data[$key . '_path'] = call_user_func($value['call'], $data[$key . '_id'], $value['arg']);
            }

            if ($data[$key . '_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
                $attributeLabel = $this->magentoAttributeHelper->getAttributeLabel(
                    $data[$key . '_attribute'],
                    $listing->getStoreId()
                );
                $data[$key . '_path'] = 'Magento Attribute' . ' > ' . $attributeLabel;
            }
        }
    }

    public function getCategoryTitles()
    {
        $titles = [];

        $type = self::TYPE_EBAY_MAIN;
        $titles[$type] = $this->translation->__('Primary Category');

        $type = self::TYPE_EBAY_SECONDARY;
        $titles[$type] = $this->translation->__('Secondary Category');

        $type = self::TYPE_STORE_MAIN;
        $titles[$type] = $this->translation->__('Store Primary Category');

        $type = self::TYPE_STORE_SECONDARY;
        $titles[$type] = $this->translation->__('Store Secondary Category');

        return $titles;
    }

    public function getCategoryTitle($type)
    {
        $titles = $this->getCategoryTitles();

        if (isset($titles[$type])) {
            return $titles[$type];
        }

        return '';
    }

    // ----------------------------------------

    /**
     * @return string
     */
    private function getConfigGroup(): string
    {
        return '/ebay/category/recent/';
    }
}
