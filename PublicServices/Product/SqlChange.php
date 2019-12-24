<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/*
    // $this->_objectManager instanceof \Magento\Framework\ObjectManagerInterface
    $model = $this->_objectManager->create('\Ess\M2ePro\PublicServices\Product\SqlChange');

    // notify M2E Pro about some change of product with ID 17
    $model->markProductChanged(17);

    // make price change of product with ID 18 and then notify M2E Pro
    $model->markPriceWasChanged(18);

    // make QTY change of product with ID 19 and then notify M2E Pro
    $model->markQtyWasChanged(19);

    // make status change of product with ID 20 and then notify M2E Pro
    $model->markStatusWasChanged(20);

    // make attribute 'custom_attribute_code' value change from 'old' to 'new' of product with ID 21
    // in store with ID 1 and then notify M2E Pro
    $model->markProductAttributeChanged(21, 'custom_attribute_code', 1, 'old', 'new');

    $model->applyChanges();
*/

namespace Ess\M2ePro\PublicServices\Product;

/**
 * Class \Ess\M2ePro\PublicServices\Product\SqlChange
 */
class SqlChange extends \Ess\M2ePro\Model\AbstractModel
{
    const VERSION = '1.0.2';

    protected $preventDuplicatesMode = true;

    protected $changes = [];

    protected $resource;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resource = $resource;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function needPreventDuplicates($value = null)
    {
        if ($value === null) {
            return $this->preventDuplicatesMode;
        }

        $this->preventDuplicatesMode = $value;
        return $this;
    }

    // ---------------------------------------

    public function applyChanges()
    {
        $this->filterOnlyAffectedChanges();

        if (count($this->changes) <= 0) {
            return $this;
        }

        $this->needPreventDuplicates() ? $this->insertPreventDuplicates()
                                       : $this->simpleInsert();

        return $this->flushChanges();
    }

    /**
     * @return $this
     */
    public function flushChanges()
    {
        $this->changes = [];
        return $this;
    }

    //########################################

    public function markProductChanged($productId)
    {
        $change = $this->_getSkeleton();
        $change['product_id'] = (int)$productId;

        return $this->_addChange($change);
    }

    public function markPriceWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    public function markStatusWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    public function markQtyWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    public function markProductAttributeChanged(
        $productId,
        $attributeCode,
        $storeId,
        $valueOld = null,
        $valueNew = null
    ) {
        $this->markProductChanged($productId);

        $change = $this->_getSkeleton();
        $change['product_id'] = (int)$productId;
        $change['store_id']   = (int)$storeId;
        $change['attribute']  = $attributeCode;
        $change['value_old']  = $valueOld;
        $change['value_new']  = $valueNew;

        return $this->_addChange($change);
    }

    // ---------------------------------------

    public function markProductCreated($productId)
    {
        $change = $this->_getSkeleton();
        $change['product_id'] = (int)$productId;
        $change['action']     = \Ess\M2ePro\Model\ProductChange::ACTION_CREATE;
        $change['attribute']  = null;

        return $this->_addChange($change);
    }

    public function markProductRemoved($productId)
    {
        $change = $this->_getSkeleton();
        $change['product_id'] = (int)$productId;
        $change['action']     = \Ess\M2ePro\Model\ProductChange::ACTION_DELETE;
        $change['attribute']  = null;

        return $this->_addChange($change);
    }

    //########################################

    private function _getSkeleton()
    {
        return [
            'product_id'    => null,
            'store_id'      => null,
            'action'        => \Ess\M2ePro\Model\ProductChange::ACTION_UPDATE,
            'attribute'     => \Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE,
            'value_old'     => null,
            'value_new'     => null,
            'initiators'    => \Ess\M2ePro\Model\ProductChange::INITIATOR_DEVELOPER,
            'count_changes' => null,
            'update_date'   => $date = $this->getHelper('Data')->getCurrentGmtDate(),
            'create_date'   => $date
        ];
    }

    private function _addChange(array $change)
    {
        $key = $change['product_id'].'##'.$change['store_id'].'##'.$change['attribute'];

        $this->hasChangesCounter($change) && $change['count_changes'] = 1;

        if (!array_key_exists($key, $this->changes)) {
            $this->changes[$key] = $change;
            return $this;
        }

        if (!$this->hasChangesCounter($change)) {
            return $this;
        }

        $this->changes[$key]['value_new'] = $change['value_new'];
        $this->changes[$key]['count_changes']++;

        return $this;
    }

    // ---------------------------------------

    private function getAffectedProductsIds()
    {
        $productIds = [];

        foreach ($this->changes as $change) {
            $productIds[] = (int)$change['product_id'];
        }

        return array_unique($productIds);
    }

    private function hasChangesCounter($change)
    {
        if ($change['attribute'] == \Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE) {
            return false;
        }

        if ($change['action'] == \Ess\M2ePro\Model\ProductChange::ACTION_CREATE ||
            $change['action'] == \Ess\M2ePro\Model\ProductChange::ACTION_DELETE) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    private function filterOnlyAffectedChanges()
    {
        if (count($this->changes) <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();

        $listingProductTable  = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product');
        $variationOptionTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product_variation_option');

        $simpleProductsSelect = $connection
            ->select()
            ->distinct()
            ->from($listingProductTable, ['product_id']);

        $variationsProductsSelect = $connection
            ->select()
            ->distinct()
            ->from($variationOptionTable, ['product_id']);

        $stmtQuery = $connection
            ->select()
            ->union([$simpleProductsSelect, $variationsProductsSelect])
            ->query();

        $productsInListings = [];
        while ($productId = $stmtQuery->fetchColumn()) {
            $productsInListings[] = (int)$productId;
        }

        foreach ($this->changes as $key => $change) {
            if (!in_array($change['product_id'], $productsInListings)) {
                unset($this->changes[$key]);
            }
        }
    }

    private function insertPreventDuplicates()
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_product_change');

        $queryStmt = $connection
            ->select()
            ->from($tableName)
            ->where('`product_id` IN (?)', $this->getAffectedProductsIds())
            ->query();

        $existedChanges = [];

        while ($row = $queryStmt->fetch()) {
            $key = $row['product_id'].'##'.$row['store_id'].'##'.$row['attribute'];
            $existedChanges[$key] = $row;
        }

        $inserts = [];
        foreach ($this->changes as $changeKey => $change) {
            if (array_key_exists($changeKey, $existedChanges) && !$this->hasChangesCounter($change)) {
                continue;
            }

            if (!array_key_exists($changeKey, $existedChanges)) {
                $inserts[] = $change;
                continue;
            }

            $id = $existedChanges[$changeKey]['id'];
            $changesCounter = $existedChanges[$changeKey]['count_changes'] + $change['count_changes'];

            $connection->update(
                $tableName,
                [
                    'count_changes' => $changesCounter,
                    'value_new'     => $change['value_new'],
                    'update_date'   => $change['update_date']
                ],
                "id = {$id}"
            );
        }

        count($inserts) && $connection->insertMultiple($tableName, $inserts);
    }

    private function simpleInsert()
    {
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_product_change');

        $this->resource->getConnection()->insertMultiple($tableName, $this->changes);
    }

    //########################################
}
