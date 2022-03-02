<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    protected $metadataPool;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;

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
                       ->from(['lp' => $this->getMainTable()])
                       ->reset(\Magento\Framework\DB\Select::COLUMNS)
                       ->columns(['product_id'])
                       ->where('id IN (?)', $listingProductIds);

        return $select->query()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getItemsByProductId(
        $productId,
        array $listingFilters = [],
        array $listingProductFilters = []
    ) {
        $filters    = [$listingFilters, $listingProductFilters];
        $cacheKey   = __METHOD__.$productId.sha1($this->getHelper('Data')->jsonEncode($filters));
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $simpleProductsSelect = $this->getConnection()
            ->select()
            ->from(
                ['lp' => $this->getMainTable()],
                ['id','component_mode','option_id' => new \Zend_Db_Expr('NULL')]
            )
            ->where("`product_id` = ?", (int)$productId);

        if (!empty($listingProductFilters)) {
            foreach ($listingProductFilters as $column => $value) {
                if (is_array($value)) {
                    $simpleProductsSelect->where('`'.$column.'` IN(?)', $value);
                } else {
                    $simpleProductsSelect->where('`'.$column.'` = ?', $value);
                }
            }
        }

        if (!empty($listingFilters)) {
            $simpleProductsSelect->join(
                ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
                '`l`.`id` = `lp`.`listing_id`',
                []
            );

            foreach ($listingFilters as $column => $value) {
                if (is_array($value)) {
                    $simpleProductsSelect->where('`l`.`'.$column.'` IN(?)', $value);
                } else {
                    $simpleProductsSelect->where('`l`.`'.$column.'` = ?', $value);
                }
            }
        }

        $variationTable = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource()
            ->getMainTable();
        $optionTable = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getResource()
            ->getMainTable();

        $variationsProductsSelect = $this->getConnection()
            ->select()
            ->from(
                ['lp' => $this->getMainTable()],
                ['id','component_mode']
            )
            ->join(
                ['lpv' => $variationTable],
                '`lp`.`id` = `lpv`.`listing_product_id`',
                []
            )
            ->join(
                ['lpvo' => $optionTable],
                '`lpv`.`id` = `lpvo`.`listing_product_variation_id`',
                ['option_id' => 'id']
            )
            ->where("`lpvo`.`product_id` = ?", (int)$productId)
            ->where("`lpvo`.`product_type` != ?", "simple");

        if (!empty($listingProductFilters)) {
            foreach ($listingProductFilters as $column => $value) {
                if (is_array($value)) {
                    $variationsProductsSelect->where('`lp`.`'.$column.'` IN(?)', $value);
                } else {
                    $variationsProductsSelect->where('`lp`.`'.$column.'` = ?', $value);
                }
            }
        }

        if (!empty($listingFilters)) {
            $variationsProductsSelect->join(
                ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
                '`l`.`id` = `lp`.`listing_id`',
                []
            );
            foreach ($listingFilters as $column => $value) {
                if (is_array($value)) {
                    $variationsProductsSelect->where('`l`.`'.$column.'` IN(?)', $value);
                } else {
                    $variationsProductsSelect->where('`l`.`'.$column.'` = ?', $value);
                }
            }
        }

        $unionSelect = $this->getConnection()->select()->union([
            $simpleProductsSelect,
            $variationsProductsSelect
        ]);

        $result = [];
        $foundOptionsIds = [];

        foreach ($unionSelect->query()->fetchAll() as $item) {
            $tempListingProductId = $item['id'];

            if (!empty($item['option_id'])) {
                $foundOptionsIds[$tempListingProductId][] = $item['option_id'];
            }

            if (!empty($result[$tempListingProductId])) {
                continue;
            }

            $result[$tempListingProductId] = $this->parentFactory->getObjectLoaded(
                $item['component_mode'],
                'Listing\Product',
                (int)$tempListingProductId
            );
        }

        foreach ($foundOptionsIds as $listingProductId => $optionsIds) {
            if (empty($result[$listingProductId]) || empty($optionsIds)) {
                continue;
            }

            $result[$listingProductId]->setData('found_options_ids', $optionsIds);
        }

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $result);

        return array_values($result);
    }

    //########################################

    public function getParentEntityIdsByChild($childId)
    {
        $select = $this->getConnection()
             ->select()
             ->from(['l' => $this->getHelper('Module_Database_Structure')
                                 ->getTableNameWithPrefix('catalog_product_link')], [])
             ->join(
                 ['e' => $this->getHelper('Module_Database_Structure')
                              ->getTableNameWithPrefix('catalog_product_entity')],
                 'e.' .
                 $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField() . ' = l.product_id',
                 ['e.entity_id']
             )
             ->where('l.linked_product_id = ?', $childId)
             ->where(
                 'link_type_id = ?',
                 Link::LINK_TYPE_GROUPED
             );

        return $this->getConnection()->fetchCol($select);
    }

    //########################################
}
