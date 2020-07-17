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

    $model->applyChanges();
*/

namespace Ess\M2ePro\PublicServices\Product;

/**
 * Class \Ess\M2ePro\PublicServices\Product\SqlChange
 */
class SqlChange extends \Ess\M2ePro\Model\AbstractModel
{
    const VERSION = '2.0.1';

    const INSTRUCTION_TYPE_PRODUCT_CHANGED = 'sql_change_product_changed';
    const INSTRUCTION_TYPE_STATUS_CHANGED  = 'sql_change_status_changed';
    const INSTRUCTION_TYPE_QTY_CHANGED     = 'sql_change_qty_changed';
    const INSTRUCTION_TYPE_PRICE_CHANGED   = 'sql_change_price_changed';

    const INSTRUCTION_INITIATOR = 'public_services_sql_change_processor';

    protected $preventDuplicatesMode = true;

    protected $changesData = [];

    protected $activeRecordFactory;
    protected $resource;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resource = $resource;
        parent::__construct($helperFactory, $modelFactory);
    }
    //########################################

    public function enablePreventDuplicatesMode()
    {
        $this->preventDuplicatesMode = true;
    }

    public function disablePreventDuplicatesMode()
    {
        $this->preventDuplicatesMode = false;
    }

    //########################################

    public function applyChanges()
    {
        $instructionsData = $this->getInstructionsData();

        if ($this->preventDuplicatesMode) {
            $instructionsData = $this->filterExistedInstructions($instructionsData);
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()
            ->add($instructionsData);

        $this->flushChanges();

        return $this;
    }

    /**
     * @return $this
     */
    public function flushChanges()
    {
        $this->changesData = [];
        return $this;
    }

    //########################################

    /**
     * Backward compatibility issue
     * @param $productId
     * @return $this
     */
    public function markQtyWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    /**
     * Backward compatibility issue
     * @param $productId
     * @return $this
     */
    public function markPriceWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    /**
     * Backward compatibility issue
     * @param $productId
     * @return $this
     */
    public function markStatusWasChanged($productId)
    {
        return $this->markProductChanged($productId);
    }

    //----------------------------------------

    public function markProductAttributeChanged(
        $productId,
        $attributeCode,
        $storeId,
        $valueOld = null,
        $valueNew = null
    ) {
        throw new \Ess\M2ePro\Model\Exception\Logic('Method is not supported.');
    }

    //########################################

    public function markProductChanged($productId)
    {
        $this->changesData[] = [
            'product_id'       => (int)$productId,
            'instruction_type' => self::INSTRUCTION_TYPE_PRODUCT_CHANGED,
        ];
        return $this;
    }

    public function markStatusChanged($productId)
    {
        $this->changesData[] = [
            'product_id'       => (int)$productId,
            'instruction_type' => self::INSTRUCTION_TYPE_STATUS_CHANGED,
        ];
        return $this;
    }

    public function markQtyChanged($productId)
    {
        $this->changesData[] = [
            'product_id'       => (int)$productId,
            'instruction_type' => self::INSTRUCTION_TYPE_QTY_CHANGED,
        ];
        return $this;
    }

    public function markPriceChanged($productId)
    {
        $this->changesData[] = [
            'product_id'       => (int)$productId,
            'instruction_type' => self::INSTRUCTION_TYPE_PRICE_CHANGED,
        ];
        return $this;
    }

    //########################################

    protected function getInstructionsData()
    {
        if (empty($this->changesData)) {
            return [];
        }

        $productInstructionTypes = [];

        foreach ($this->changesData as $changeData) {
            $productId = (int)$changeData['product_id'];

            $productInstructionTypes[$productId][] = $changeData['instruction_type'];
            $productInstructionTypes[$productId] = array_unique($productInstructionTypes[$productId]);
        }

        $connection = $this->resource->getConnection();

        $listingProductTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product');
        $variationTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product_variation');
        $variationOptionTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product_variation_option');

        $instructionsData = [];

        foreach (array_chunk($productInstructionTypes, 1000, true) as $productInstructionTypesPart) {
            $simpleProductsSelect = $connection
                ->select()
                ->from($listingProductTable, ['magento_product_id' => 'product_id', 'listing_product_id' => 'id'])
                ->where('product_id IN (?)', array_keys($productInstructionTypesPart));

            $variationsProductsSelect = $connection
                ->select()
                ->from(['lpvo' => $variationOptionTable], ['magento_product_id' => 'product_id'])
                ->joinLeft(
                    ['lpv' => $variationTable],
                    'lpv.id = lpvo.listing_product_variation_id',
                    ['listing_product_id']
                )
                ->where('product_id IN (?)', array_keys($productInstructionTypesPart));

            $stmtQuery = $connection
                ->select()
                ->union([$simpleProductsSelect, $variationsProductsSelect])
                ->query();

            while ($row = $stmtQuery->fetch()) {
                $magentoProductId = (int)$row['magento_product_id'];
                $listingProductId = (int)$row['listing_product_id'];

                foreach ($productInstructionTypesPart[$magentoProductId] as $instructionType) {
                    $instructionsData[] = [
                        'listing_product_id' => $listingProductId,
                        'type'               => $instructionType,
                        'initiator'          => self::INSTRUCTION_INITIATOR,
                        'priority'           => 50,
                    ];
                }
            }
        }

        return $instructionsData;
    }

    protected function filterExistedInstructions(array $instructionsData)
    {
        $indexedInstructionsData = [];

        foreach ($instructionsData as $instructionData) {
            $key = $instructionData['listing_product_id'].'##'.$instructionData['type'];
            $indexedInstructionsData[$key] = $instructionData;
        }

        $connection = $this->resource->getConnection();

        $instructionTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product_instruction');

        $stmt = $connection
            ->select()
            ->from($instructionTable, ['listing_product_id', 'type'])
            ->query();

        while ($row = $stmt->fetch()) {
            $listingProductId = (int)$row['listing_product_id'];
            $type             = $row['type'];

            if (isset($indexedInstructionsData[$listingProductId.'##'.$type])) {
                unset($indexedInstructionsData[$listingProductId.'##'.$type]);
            }
        }

        return array_values($indexedInstructionsData);
    }

    //########################################
}
