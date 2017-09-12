<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    protected $synchronizationConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    )
    {
        $this->synchronizationConfig = $synchronizationConfig;
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_product', 'id');
    }

    //########################################

    public function getProductIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(array('lp' => $this->getMainTable()))
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(array('product_id'))
                       ->where('id IN (?)', $listingProductIds);

        return $select->query()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getItemsByProductId($productId, array $filters = array())
    {
        $cacheKey   = __METHOD__.$productId.sha1($this->getHelper('Data')->jsonEncode($filters));
        $cacheValue = $this->getHelper('Data\Cache\Runtime')->getValue($cacheKey);

        if (!is_null($cacheValue)) {
            return $cacheValue;
        }

        $simpleProductsSelect = $this->getConnection()
            ->select()
            ->from(
                $this->getMainTable(),
                array('id','component_mode','option_id' => new \Zend_Db_Expr('NULL'))
            )
            ->where("`product_id` = ?",(int)$productId);

        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                $simpleProductsSelect->where('`'.$column.'` = ?', $value);
            }
        }

        $variationTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource()
            ->getMainTable();
        $optionTable = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')->getResource()
            ->getMainTable();

        $variationsProductsSelect = $this->getConnection()
            ->select()
            ->from(
                array('lp' => $this->getMainTable()),
                array('id','component_mode')
            )
            ->join(
                array('lpv' => $variationTable),
                '`lp`.`id` = `lpv`.`listing_product_id`',
                array()
            )
            ->join(
                array('lpvo' => $optionTable),
                '`lpv`.`id` = `lpvo`.`listing_product_variation_id`',
                array('option_id' => 'id')
            )
            ->where("`lpvo`.`product_id` = ?",(int)$productId)
            ->where("`lpvo`.`product_type` != ?", "simple");

        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                $variationsProductsSelect->where('`lp`.`'.$column.'` = ?', $value);
            }
        }

        $unionSelect = $this->getConnection()->select()->union(array(
            $simpleProductsSelect,
            $variationsProductsSelect
        ));

        $result = array();
        $foundOptionsIds = array();

        foreach ($unionSelect->query()->fetchAll() as $item) {
            $tempListingProductId = $item['id'];

            if (!empty($item['option_id'])) {
                $foundOptionsIds[$tempListingProductId][] = $item['option_id'];
            }

            if (!empty($result[$tempListingProductId])) {
                continue;
            }

            $result[$tempListingProductId] = $this->parentFactory->getObjectLoaded(
                $item['component_mode'], 'Listing\Product', (int)$tempListingProductId
            );
        }

        foreach ($foundOptionsIds as $listingProductId => $optionsIds) {
            if (empty($result[$listingProductId]) || empty($optionsIds)) {
                continue;
            }

            $result[$listingProductId]->setData('found_options_ids', $optionsIds);
        }

        $this->getHelper('Data\Cache\Runtime')->setValue($cacheKey, $result);

        return array_values($result);
    }

    //########################################

    public function getChangedItems(array $attributes,
                                    $componentMode = NULL,
                                    $withStoreFilter = false)
    {
        $resultsByListingProduct = $this->getChangedItemsByListingProduct($attributes,
                                                                          $componentMode,
                                                                          $withStoreFilter);

        $resultsByVariationOption = $this->getChangedItemsByVariationOption($attributes,
                                                                            $componentMode,
                                                                            $withStoreFilter);

        $results = array();

        foreach ($resultsByListingProduct as $item) {
            if (isset($results[$item['id'].'_'.$item['changed_attribute']])) {
                continue;
            }
            $results[$item['id'].'_'.$item['changed_attribute']] = $item;
        }

        foreach ($resultsByVariationOption as $item) {
            if (isset($results[$item['id'].'_'.$item['changed_attribute']])) {
                continue;
            }
            $results[$item['id'].'_'.$item['changed_attribute']] = $item;
        }

        return array_values($results);
    }

    // ---------------------------------------

    public function getChangedItemsByListingProduct(array $attributes,
                                                    $componentMode = NULL,
                                                    $withStoreFilter = false)
    {
        if (count($attributes) <= 0) {
            return array();
        }

        $listingsTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $productsChangesTable = $this->activeRecordFactory->getObject('ProductChange')->getResource()->getMainTable();

        $limit = (int)$this->synchronizationConfig->getGroupValue(
            '/settings/product_change/', 'max_count_per_one_time'
        );

        $select = $this->getConnection()
                       ->select()
                       ->from($productsChangesTable,'*')
                       ->order(array('id ASC'))
                       ->limit($limit);

        $select = $this->getConnection()
                       ->select()
                       ->from(
                          array('pc' => $select),
                          array(
                              'changed_attribute'=>'attribute',
                              'changed_to_value'=>'value_new',
                              'change_initiators'=>'initiators',
                          )
                       )
                       ->join(
                          array('lp' => $this->getMainTable()),
                          '`pc`.`product_id` = `lp`.`product_id`',
                          'id'
                       )
                       ->where('`pc`.`action` = ?',(string)\Ess\M2ePro\Model\ProductChange::ACTION_UPDATE)
                       ->where("`pc`.`attribute` IN ('".implode("','",$attributes)."')");

        if ($withStoreFilter) {
            $select->join(array('l' => $listingsTable),'`lp`.`listing_id` = `l`.`id`',array());
            $select->where("`l`.`store_id` = `pc`.`store_id`");
        }

        !is_null($componentMode) && $select->where("`lp`.`component_mode` = ?",(string)$componentMode);

        $results = array();

        foreach ($select->query()->fetchAll() as $item) {
            if (isset($results[$item['id'].'_'.$item['changed_attribute']])) {
                continue;
            }
            $results[$item['id'].'_'.$item['changed_attribute']] = $item;
        }

        return array_values($results);
    }

    public function getChangedItemsByVariationOption(array $attributes,
                                                     $componentMode = NULL,
                                                     $withStoreFilter = false)
    {
        if (count($attributes) <= 0) {
            return array();
        }

        $listingsTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $variationsTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getResource()
            ->getMainTable();
        $optionsTable = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')->getResource()
            ->getMainTable();
        $productsChangesTable = $this->activeRecordFactory->getObject('ProductChange')->getResource()->getMainTable();

        $limit = (int)$this->synchronizationConfig->getGroupValue(
            '/settings/product_change/', 'max_count_per_one_time'
        );

        $select = $this->getConnection()
                       ->select()
                       ->from($productsChangesTable,'*')
                       ->order(array('id ASC'))
                       ->limit($limit);

        $select = $this->getConnection()
                       ->select()
                       ->from(
                            array('pc' => $select),
                            array(
                                'changed_attribute'=>'attribute',
                                'changed_to_value'=>'value_new',
                                'change_initiators'=>'initiators',
                            )
                     )
                     ->join(
                        array('lpvo' => $optionsTable),
                        '`pc`.`product_id` = `lpvo`.`product_id`',
                        array()
                     )
                     ->join(
                        array('lpv' => $variationsTable),
                        '`lpvo`.`listing_product_variation_id` = `lpv`.`id`',
                        array()
                     )
                     ->join(
                        array('lp' => $this->getMainTable()),
                        '`lpv`.`listing_product_id` = `lp`.`id`',
                        array('id')
                     )
                     ->where('`pc`.`action` = ?',(string)\Ess\M2ePro\Model\ProductChange::ACTION_UPDATE)
                     ->where("`pc`.`attribute` IN ('".implode("','",$attributes)."')");

        if ($withStoreFilter) {
            $select->join(array('l' => $listingsTable),'`lp`.`listing_id` = `l`.`id`',array());
            $select->where("`l`.`store_id` = `pc`.`store_id`");
        }

        !is_null($componentMode) && $select->where("`lpvo`.`component_mode` = ?",(string)$componentMode);

        $results = array();

        foreach ($select->query()->fetchAll() as $item) {
            if (isset($results[$item['id'].'_'.$item['changed_attribute']])) {
                continue;
            }
            $results[$item['id'].'_'.$item['changed_attribute']] = $item;
        }

        return array_values($results);
    }

    //########################################

    public function setNeedSynchRulesCheck(array $listingsProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array('need_synch_rules_check' => 1),
            array('id IN (?)' => $listingsProductsIds)
        );
    }

    //########################################
}