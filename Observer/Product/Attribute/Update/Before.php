<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\Attribute\Update;

/**
 * Class \Ess\M2ePro\Observer\Product\Attribute\Update\Before
 */
class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->objectManager = $objectManager;
    }

    //########################################

    public function process()
    {
        $changedProductsIds = $this->getEventObserver()->getData('product_ids');
        $attributesData = $this->getEventObserver()->getData('attributes_data');
        $storeId = $this->getEventObserver()->getData('store_id');

        if (empty($changedProductsIds) || empty($attributesData)) {
            return;
        }

        /** @var \Ess\M2ePro\PublicServices\Product\SqlChange $changesModel */
        $changesModel = $this->objectManager->get('Ess\M2ePro\PublicServices\Product\SqlChange');
        $affectedProductsIds = $this->getAffectedProducts($changedProductsIds);

        foreach ($changedProductsIds as $productId) {
            if (!in_array((int)$productId, $affectedProductsIds)) {
                continue;
            }

            foreach ($attributesData as $attributeName => $attributeValue) {
                $changesModel->markProductAttributeChanged(
                    $productId,
                    $attributeName,
                    $storeId,
                    $this->getHelper('Module\Translation')->__('Unknown'),
                    $attributeValue
                );
            }
        }

        $changesModel->applyChanges();
    }

    //########################################

    private function getAffectedProducts(array $changedProductsIds)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('product_id', ['in' => $changedProductsIds]);

        $collection->getSelect()->distinct(true);
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(['product_id']);

        $result = $collection->getColumnValues('product_id');
        $result = array_map('intval', $result);

        return $result;
    }

    //########################################
}
