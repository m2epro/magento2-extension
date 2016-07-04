<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Product\Attribute\Update;

class Before extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        $changedProductsIds = $this->getEventObserver()->getData('product_ids');
        $attributesData = $this->getEventObserver()->getData('attributes_data');
        $storeId = $this->getEventObserver()->getData('store_id');

        if (empty($changedProductsIds) || empty($attributesData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\PublicServices\Product\SqlChange $changesModel */
        $changesModel = $this->modelFactory->getObject('PublicServices\Product\SqlChange');

        foreach ($changedProductsIds as $productId) {
            foreach ($attributesData as $attributeName => $attributeValue) {

                $changesModel->markProductAttributeChanged(
                    $productId, $attributeName, $storeId,
                    $this->getHelper('Module\Translation')->__('Unknown'), $attributeValue
                );
            }
        }

        $changesModel->applyChanges();
    }

    //########################################
}