<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Category;

class Ebay
{
    private const CACHE_TAG = '_ebay_dictionary_data_';

    public const PRODUCT_IDENTIFIER_STATUS_DISABLED = 0;
    public const PRODUCT_IDENTIFIER_STATUS_ENABLED = 1;
    public const PRODUCT_IDENTIFIER_STATUS_REQUIRED = 2;

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructure;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cache;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructure,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cache,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;
        $this->dbStructure = $dbStructure;
        $this->cache = $cache;
        $this->dataHelper = $dataHelper;
        $this->exceptionHelper = $exceptionHelper;
    }

    // ----------------------------------------

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     * @param bool $includeTitle
     *
     * @return string
     */
    public function getPath($categoryId, $marketplaceId, $includeTitle = true)
    {
        $category = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$marketplaceId)
                                      ->getChildObject()
                                      ->getCategory((int)$categoryId);

        if (!$category) {
            return '';
        }

        $category['path'] = str_replace(' > ', '>', $category['path']);

        return $category['path'] . ($includeTitle ? '>' . $category['title'] : '');
    }

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     *
     * @return int|null
     */
    public function getTopLevel($categoryId, $marketplaceId)
    {
        $topLevel = null;
        for ($i = 1; $i < 10; $i++) {
            $category = $this->ebayFactory->getObjectLoaded('Marketplace', (int)$marketplaceId)
                                          ->getChildObject()
                                          ->getCategory((int)$categoryId);

            if (!$category || ($i == 1 && !$category['is_leaf'])) {
                return null;
            }

            $topLevel = $category['category_id'];

            if (!$category['parent_category_id']) {
                return $topLevel;
            }

            $categoryId = (int)$category['parent_category_id'];
        }

        return $topLevel;
    }

    // ---------------------------------------

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     *
     * @return bool|null
     */
    public function isVariationEnabled($categoryId, $marketplaceId)
    {
        $features = $this->getFeatures($categoryId, $marketplaceId);
        if ($features === null) {
            return null;
        }

        return !empty($features['variation_enabled']);
    }

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     *
     * @return bool
     */
    public function hasRequiredSpecifics($categoryId, $marketplaceId)
    {
        $specifics = $this->getSpecifics($categoryId, $marketplaceId);

        if (empty($specifics)) {
            return false;
        }

        foreach ($specifics as $specific) {
            if ($specific['required']) {
                return true;
            }
        }

        return false;
    }

    //########################################

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     *
     * @return array|null
     */
    public function getFeatures($categoryId, $marketplaceId)
    {
        $cacheKey = '_ebay_category_features_' . $marketplaceId . '_' . $categoryId;

        if (($cacheValue = $this->cache->getValue($cacheKey)) !== null) {
            return $cacheValue;
        }

        $connRead = $this->resourceConnection->getConnection();
        $tableDictCategory = $this->dbStructure
                                  ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');

        $dbSelect = $connRead->select()
                             ->from($tableDictCategory, 'features')
                             ->where('`marketplace_id` = ?', (int)$marketplaceId)
                             ->where('`category_id` = ?', (int)$categoryId);

        $categoryRow = $connRead->fetchAssoc($dbSelect);
        $categoryRow = array_shift($categoryRow);

        // not found marketplace category row
        if (!$categoryRow) {
            return null;
        }

        $features = [];
        if ($categoryRow['features'] !== null) {
            $features = (array)$this->dataHelper->jsonDecode($categoryRow['features']);
        }

        $this->cache->setValue($cacheKey, $features, [self::CACHE_TAG, 'marketplace']);

        return $features;
    }

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     *
     * @return array|null
     */
    public function getSpecifics($categoryId, $marketplaceId)
    {
        $cacheKey = '_ebay_category_item_specifics_' . $categoryId . '_' . $marketplaceId;

        if (($cacheValue = $this->cache->getValue($cacheKey)) !== null) {
            return $cacheValue;
        }

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connRead */
        $connRead = $this->resourceConnection->getConnection();
        $tableDictCategory = $this->dbStructure
                                  ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');

        $dbSelect = $connRead->select()
                             ->from($tableDictCategory, '*')
                             ->where('`marketplace_id` = ?', (int)$marketplaceId)
                             ->where('`category_id` = ?', (int)$categoryId);

        $categoryRow = $connRead->fetchAssoc($dbSelect);
        $categoryRow = array_shift($categoryRow);

        // not found marketplace category row
        if (!$categoryRow) {
            return null;
        }

        if (!$categoryRow['is_leaf']) {
            $this->cache->setValue($cacheKey, [], [self::CACHE_TAG, 'marketplace']);

            return [];
        }

        if ($categoryRow['item_specifics'] !== null) {
            $specifics = (array)$this->dataHelper->jsonDecode($categoryRow['item_specifics']);
        } else {
            try {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObject */
                $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector(
                    'category',
                    'get',
                    'specifics',
                    ['category_id' => $categoryId],
                    'specifics',
                    $marketplaceId,
                    null,
                    null
                );

                $dispatcherObject->process($connectorObj);
                $specifics = (array)$connectorObj->getResponseData();
            } catch (\Exception $exception) {
                $this->exceptionHelper->process($exception);

                return null;
            }

            $connWrite = $this->resourceConnection->getConnection();
            $connWrite->update(
                $tableDictCategory,
                ['item_specifics' => $this->dataHelper->jsonEncode($specifics)],
                [
                    'marketplace_id = ?' => (int)$marketplaceId,
                    'category_id = ?'    => (int)$categoryId,
                ]
            );
        }

        $this->cache->setValue($cacheKey, $specifics, [self::CACHE_TAG, 'marketplace']);

        return $specifics;
    }

    //########################################

    public function exists($categoryId, $marketplaceId)
    {
        $connRead = $this->resourceConnection->getConnection();
        $dbSelect = $connRead->select()
                             ->from(
                                 $this->dbStructure
                                      ->getTableNameWithPrefix('m2epro_ebay_dictionary_category'),
                                 'COUNT(*)'
                             )
                             ->where('`marketplace_id` = ?', (int)$marketplaceId)
                             ->where('`category_id` = ?', (int)$categoryId);

        return $dbSelect->query()->fetchColumn() == 1;
    }

    public function isExistDeletedCategories()
    {
        $connRead = $this->resourceConnection->getConnection();

        $stmt = $connRead->select()
                         ->from(
                             [
                                 'etc' => $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()
                                                                    ->getMainTable(),
                             ]
                         )
                         ->joinLeft(
                             [
                                 'edc' => $this->dbStructure
                                               ->getTableNameWithPrefix('m2epro_ebay_dictionary_category'),
                             ],
                             'edc.marketplace_id = etc.marketplace_id AND edc.category_id = etc.category_id'
                         )
                         ->reset(\Magento\Framework\DB\Select::COLUMNS)
                         ->columns(
                             [
                                 'etc.category_id',
                                 'etc.marketplace_id',
                             ]
                         )
                         ->where('etc.category_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
                         ->where('edc.category_id IS NULL')
                         ->group(
                             ['etc.category_id', 'etc.marketplace_id']
                         )
                         ->query();

        return $stmt->fetchColumn() !== false;
    }

    //########################################
}
