<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\ProductChange
 */
class ProductChange extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    const INITIATOR_UNKNOWN         = 0;
    const INITIATOR_OBSERVER        = 1;
    const INITIATOR_SYNCHRONIZATION = 2;
    const INITIATOR_INSPECTOR       = 3;
    const INITIATOR_DEVELOPER       = 4;
    const INITIATOR_MAGMI_PLUGIN    = 5;

    const UPDATE_ATTRIBUTE_CODE = '__INSTANCE__';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\ProductChange');
    }

    //########################################

    public function addCreateAction($productId, $initiator = self::INITIATOR_UNKNOWN)
    {
        $tempCollection = $this->activeRecordFactory->getObject('ProductChange')
                                ->getCollection()
                                ->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('action', self::ACTION_CREATE);

        $tempChanges = $tempCollection->toArray();

        if ($tempChanges['totalRecords'] <= 0) {
            $dataForAdd = [
                'product_id' => $productId,
                'action' => self::ACTION_CREATE,
                'initiators' => $initiator
            ];

            $this->activeRecordFactory->getObject('ProductChange')
                     ->setData($dataForAdd)
                     ->save();

            return true;
        }

        return false;
    }

    public function addDeleteAction($productId, $initiator = self::INITIATOR_UNKNOWN)
    {
        $tempCollection = $this->activeRecordFactory->getObject('ProductChange')
                                ->getCollection()
                                ->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('action', self::ACTION_DELETE);

        $tempChanges = $tempCollection->toArray();

        if ($tempChanges['totalRecords'] <= 0) {
            $dataForAdd = [
                'product_id' => $productId,
                'action' => self::ACTION_DELETE,
                'initiators' => $initiator
            ];

            $this->activeRecordFactory->getObject('ProductChange')
                     ->setData($dataForAdd)
                     ->save();

            return true;
        }

        return false;
    }

    public function addUpdateAction($productId, $initiator = self::INITIATOR_UNKNOWN)
    {
        $changeCollection = $this->activeRecordFactory->getObject('ProductChange')
                                ->getCollection()
                                ->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('action', self::ACTION_UPDATE)
                                ->addFieldToFilter('attribute', self::UPDATE_ATTRIBUTE_CODE);

        $tempChanges = $changeCollection->toArray();

        if ($tempChanges['totalRecords'] <= 0) {
            $dataForAdd = [
                'product_id' => $productId,
                'action' => self::ACTION_UPDATE,
                'attribute' => self::UPDATE_ATTRIBUTE_CODE,
                'initiators' => $initiator
            ];

            $this->activeRecordFactory->getObject('ProductChange')
                     ->setData($dataForAdd)
                     ->save();

            return true;
        }

        /** @var ProductChange $change */
        $change = reset($tempChanges['items']);

        $initiators = explode(',', $change['initiators']);
        if (in_array($initiator, $initiators)) {
            return false;
        }

        $initiators[] = $initiator;
        $initiators = implode(',', array_unique($initiators));

        $dataForUpdate = [
            'count_changes' => $change['count_changes']+1,
            'initiators'    => $initiators
        ];

        $this->activeRecordFactory->getObject('ProductChange')
            ->load($change['id'])
            ->addData($dataForUpdate)
            ->save();

        return false;
    }

    // ---------------------------------------

    public function updateAttribute(
        $productId,
        $attribute,
        $valueOld,
        $valueNew,
        $initiator = self::INITIATOR_UNKNOWN,
        $storeId = null
    ) {
        $tempCollection = $this->activeRecordFactory->getObject('ProductChange')
                                ->getCollection()
                                ->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('action', self::ACTION_UPDATE)
                                ->addFieldToFilter('attribute', $attribute);

        if ($storeId === null) {
            $tempCollection->addFieldToFilter('store_id', ['null'=>true]);
        } else {
            $tempCollection->addFieldToFilter('store_id', $storeId);
        }

        $tempChanges = $tempCollection->toArray();

        if ($tempChanges['totalRecords'] <= 0) {
            if ($valueOld == $valueNew) {
                return false;
            }

             $dataForAdd = [
                'product_id' => $productId,
                'store_id' => $storeId,
                'action' => self::ACTION_UPDATE,
                'attribute' => $attribute,
                'value_old' => $valueOld,
                'value_new' => $valueNew,
                'count_changes' => 1,
                'initiators' => $initiator
             ];

             $this->activeRecordFactory->getObject('ProductChange')
                     ->setData($dataForAdd)
                     ->save();

             return true;
        }

        if ($tempChanges['items'][0]['value_old'] == $valueNew) {
              $this->activeRecordFactory->getObject('ProductChange')
                    ->setId($tempChanges['items'][0]['id'])
                    ->delete();

              return true;
        } elseif ($valueOld != $valueNew) {
             $initiators = explode(',', $tempChanges['items'][0]['initiators']);
             $initiators[] = $initiator;
             $initiators = implode(',', array_unique($initiators));

             $dataForUpdate = [
                'value_new' => $valueNew,
                'count_changes' => $tempChanges['items'][0]['count_changes']+1,
                'initiators' => $initiators
             ];

             $this->activeRecordFactory->getObject('ProductChange')
                     ->load($tempChanges['items'][0]['id'])
                     ->addData($dataForUpdate)
                     ->save();

             return true;
        }

        return false;
    }

    //########################################

    public function removeDeletedProduct($product)
    {
        $productId = $product instanceof \Magento\Catalog\Model\Product ?
                        (int)$product->getId() : (int)$product;

        $productsChanges = $this->activeRecordFactory->getObject('ProductChange')
                                        ->getCollection()
                                        ->addFieldToFilter('product_id', $productId)
                                        ->getItems();

        foreach ($productsChanges as $productChange) {
            /** @var ProductChange $productChange */
            $productChange->delete();
        }
    }

    //########################################
}
