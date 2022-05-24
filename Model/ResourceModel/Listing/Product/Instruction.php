<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction
 */
class Instruction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_product_instruction', 'id');
    }

    //########################################

    /**
     * @param array $instructionsData
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function add(array $instructionsData)
    {
        if (empty($instructionsData)) {
            return;
        }

        $listingsProductsIds = [];

        foreach ($instructionsData as $instructionData) {
            $listingsProductsIds[] = $instructionData['listing_product_id'];
        }

        $listingsProductsCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $instructionSelectExpression = new \Zend_Db_Expr(
            "IFNULL(CONCAT('[\"', GROUP_CONCAT(DISTINCT lpi.type SEPARATOR '\",\"'), '\"]'), '[]')"
        );

        $listingsProductsCollection
            ->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns([
                'id' => 'main_table.id',
                'component_mode' => 'main_table.component_mode',
            ])
            ->joinLeft(
                ['lpi' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_listing_product_instruction')],
                'lpi.listing_product_id = main_table.id AND lpi.component = main_table.component_mode',
                ['instruction_json_types' => $instructionSelectExpression]
            )
            ->where('main_table.id IN (?)', array_unique($listingsProductsIds))
            ->group(['main_table.id', 'main_table.component_mode'])
            ->order('main_table.id');

        $dataHelper = $this->getHelper('Data');
        foreach ($instructionsData as $index => &$instructionData) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $listingsProductsCollection->getItemById($instructionData['listing_product_id']);
            if ($listingProduct === null) {
                unset($instructionsData[$index]);
                continue;
            }

            $encodedInstructionTypes = $listingProduct->getData('instruction_json_types');
            $instructionTypes = $dataHelper->jsonDecode($encodedInstructionTypes);

            if (in_array($instructionData['type'], $instructionTypes, true)) {
                unset($instructionsData[$index]);
                continue;
            }

            $instructionData['component'] = $listingProduct->getComponentMode();
            $instructionData['create_date'] = $dataHelper->getCurrentGmtDate();
        }

        if (empty($instructionsData)) {
            return;
        }

        $this->getConnection()->insertMultiple($this->getMainTable(), $instructionsData);
    }

    /**
     * @param array $instructionsIds
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function remove(array $instructionsIds)
    {
        if (empty($instructionsIds)) {
            return;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                'id IN (?)' => $instructionsIds,
                'skip_until IS NULL OR ? > skip_until' => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
            ]
        );
    }

    //########################################
}
