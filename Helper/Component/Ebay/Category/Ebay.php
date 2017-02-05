<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Category;

class Ebay extends \Ess\M2ePro\Helper\AbstractHelper
{
    const CACHE_TAG = '_ebay_dictionary_data_';

    const PRODUCT_IDENTIFIER_STATUS_DISABLED = 0;
    const PRODUCT_IDENTIFIER_STATUS_ENABLED  = 1;
    const PRODUCT_IDENTIFIER_STATUS_REQUIRED = 2;

    protected $modelFactory;
    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     * @param bool $includeTitle
     * @return string
     */
    public function getPath($categoryId, $marketplaceId, $includeTitle = true)
    {
        $category = $this->ebayFactory->getObjectLoaded('Marketplace',(int)$marketplaceId)
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
     * @return int|null
     */
    public function getTopLevel($categoryId, $marketplaceId)
    {
        $topLevel = NULL;
        for ($i = 1; $i < 10; $i++) {

            $category = $this->ebayFactory->getObjectLoaded('Marketplace',(int)$marketplaceId)
                ->getChildObject()
                ->getCategory((int)$categoryId);

            if (!$category || ($i == 1 && !$category['is_leaf'])) {
                return NULL;
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
     * @return bool|null
     */
    public function isVariationEnabled($categoryId, $marketplaceId)
    {
        $features = $this->getFeatures($categoryId, $marketplaceId);
        if (is_null($features)) {
            return NULL;
        }

        return !empty($features['variation_enabled']);
    }

    /**
     * @param int $categoryId
     * @param int $marketplaceId
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
     * @return array|null
     */
    public function getFeatures($categoryId, $marketplaceId)
    {
        $cacheKey = '_ebay_category_features_'.$marketplaceId.'_'.$categoryId;

        if (($cacheValue = $this->getHelper('Data\Cache\Permanent')->getValue($cacheKey)) !== NULL) {
            return $cacheValue;
        }

        /** @var $connRead \Magento\Framework\DB\Adapter\AdapterInterface */
        $connRead = $this->resourceConnection->getConnection();
        $tableDictCategory = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_category');

        $dbSelect = $connRead->select()
                             ->from($tableDictCategory, 'features')
                             ->where('`marketplace_id` = ?',(int)$marketplaceId)
                             ->where('`category_id` = ?',(int)$categoryId);

        $categoryRow = $connRead->fetchAssoc($dbSelect);
        $categoryRow = array_shift($categoryRow);

        // not found marketplace category row
        if (!$categoryRow) {
            return NULL;
        }

        $features = array();
        if (!is_null($categoryRow['features'])) {
            $features = (array)$this->getHelper('Data')->jsonDecode($categoryRow['features']);
        }

        $this->getHelper('Data\Cache\Permanent')->setValue($cacheKey,$features,array(self::CACHE_TAG));
        return $features;
    }

    /**
     * @param int $categoryId
     * @param int $marketplaceId
     * @return array|null
     */
    public function getSpecifics($categoryId, $marketplaceId)
    {
        $cacheKey = '_ebay_category_item_specifics_'.$categoryId.'_'.$marketplaceId;

        if (($cacheValue = $this->getHelper('Data\Cache\Permanent')->getValue($cacheKey)) !== NULL) {
            return $cacheValue;
        }

        /** @var $connRead \Magento\Framework\DB\Adapter\AdapterInterface */
        $connRead = $this->resourceConnection->getConnection();
        $tableDictCategory = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_category');

        $dbSelect = $connRead->select()
                             ->from($tableDictCategory,'*')
                             ->where('`marketplace_id` = ?', (int)$marketplaceId)
                             ->where('`category_id` = ?', (int)$categoryId);

        $categoryRow = $connRead->fetchAssoc($dbSelect);
        $categoryRow = array_shift($categoryRow);

        // not found marketplace category row
        if (!$categoryRow) {
            return NULL;
        }

        if (!$categoryRow['is_leaf']) {
            $this->getHelper('Data\Cache\Permanent')->setValue($cacheKey,array(),array(self::CACHE_TAG));
            return array();
        }

        if (!is_null($categoryRow['item_specifics'])) {

            $specifics = (array)$this->getHelper('Data')->jsonDecode($categoryRow['item_specifics']);

        } else {

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('category','get','specifics',
                                                                   array('category_id' => $categoryId), 'specifics',
                                                                   $marketplaceId, NULL, NULL);

            $dispatcherObject->process($connectorObj);
            $specifics = (array)$connectorObj->getResponseData();
            $encodedSpecifics = $this->getHelper('Data')->jsonEncode($specifics);

            /** @var $connWrite \Magento\Framework\DB\Adapter\AdapterInterface */
            $connWrite = $this->resourceConnection->getConnection();
            $connWrite->update($tableDictCategory,
                               array('item_specifics' => $encodedSpecifics),
                               array('marketplace_id = ?' => (int)$marketplaceId,
                                     'category_id = ?' => (int)$categoryId));
        }

        $this->getHelper('Data\Cache\Permanent')->setValue($cacheKey,$specifics,array(self::CACHE_TAG));
        return $specifics;
    }

    //########################################

    public function getSameTemplatesData($ids)
    {
        return $this->getHelper('Component\Ebay\Category')->getSameTemplatesData(
            $ids, $this->activeRecordFactory->getObject('Ebay\Template\Category')->getResource()->getMainTable(),
            array('category_main')
        );
    }

    public function exists($categoryId, $marketplaceId)
    {
        /** @var $connRead \Magento\Framework\DB\Adapter\AdapterInterface */
        $connRead = $this->resourceConnection->getConnection('core_read');
        $tableDictCategories = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_category');

        $dbSelect = $connRead->select()
                             ->from($tableDictCategories, 'COUNT(*)')
                             ->where('`marketplace_id` = ?', (int)$marketplaceId)
                             ->where('`category_id` = ?', (int)$categoryId);

        return $dbSelect->query()->fetchColumn() == 1;
    }

    public function isExistDeletedCategories()
    {
        /** @var $connRead \Magento\Framework\DB\Adapter\AdapterInterface */
        $connRead = $this->resourceConnection->getConnection('core_read');

        $etcTable = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getResource()->getMainTable();
        $etocTable = $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')
            ->getResource()->getMainTable();
        $edcTable = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_category');

        $etcSelect = $connRead->select();
        $etcSelect->from(
                array('etc' => $etcTable)
            )
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array(
                'category_main_id as category_id',
                'marketplace_id',
            ))
            ->where('category_main_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(array('category_id', 'marketplace_id'));

        $etocSelect = $connRead->select();
        $etocSelect->from(
                array('etc' => $etocTable)
            )
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array(
                'category_secondary_id as category_id',
                'marketplace_id',
            ))
            ->where('category_secondary_mode = ?', \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY)
            ->group(array('category_id', 'marketplace_id'));

        $unionSelect = $connRead->select();
        $unionSelect->union(array(
            $etcSelect,
            $etocSelect,
        ));

        $mainSelect = $connRead->select();
        $mainSelect->reset()
            ->from(array('main_table' => $unionSelect))
            ->joinLeft(
                array('edc' => $edcTable),
                'edc.marketplace_id = main_table.marketplace_id
                 AND edc.category_id = main_table.category_id'
            )
            ->where('edc.category_id IS NULL');

        return $connRead->query($mainSelect)->fetchColumn() !== false;
    }

    //########################################
}