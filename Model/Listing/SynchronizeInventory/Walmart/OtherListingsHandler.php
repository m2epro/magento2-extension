<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Walmart;

use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractExistingProductsHandler;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\Walmart\OtherListingsHandler
 */
class OtherListingsHandler extends AbstractExistingProductsHandler
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
    protected $preparedListingsOtherCollection;

    /**
     * @param array $responseData
     * @return array|void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    public function handle(array $responseData)
    {
        $this->responseData = $responseData;

        $this->updateReceivedOtherListings();
        $this->createNotExistedOtherListings();
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function updateReceivedOtherListings()
    {
        $dataHelper      = $this->helperFactory->getObject('Data');
        $componentHelper = $this->helperFactory->getObject('Component\Walmart');

        foreach (array_chunk(array_keys($this->responseData), 200) as $wpids) {

            $stmtTemp = $this->getPdoStatementExistingListings($wpids);
            while ($existingItem = $stmtTemp->fetch()) {

                $receivedItem = $this->responseData[$existingItem['wpid']];
                unset($this->responseData[$existingItem['wpid']]);

                $isOnlinePriceInvalid = in_array(
                    \Ess\M2ePro\Helper\Component\Walmart::PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE,
                    $receivedItem['status_change_reason']
                );

                $newData = [
                    'upc'                   => !empty($receivedItem['upc']) ? (string)$receivedItem['upc'] : null,
                    'gtin'                  => !empty($receivedItem['gtin']) ? (string)$receivedItem['gtin'] : null,
                    'wpid'                  => (string)$receivedItem['wpid'],
                    'item_id'               => (string)$receivedItem['item_id'],
                    'sku'                   => (string)$receivedItem['sku'],
                    'title'                 => (string)$receivedItem['title'],
                    'online_price'          => (float)$receivedItem['price'],
                    'online_qty'            => (int)$receivedItem['qty'],
                    'publish_status'        => (string)$receivedItem['publish_status'],
                    'lifecycle_status'      => (string)$receivedItem['lifecycle_status'],
                    'status_change_reasons' => $dataHelper->jsonEncode($receivedItem['status_change_reason']),
                    'is_online_price_invalid' => $isOnlinePriceInvalid,
                ];

                $newData['status'] = $componentHelper->getResultProductStatus(
                    $receivedItem['publish_status'], $receivedItem['lifecycle_status'], $newData['online_qty']
                );

                $existingData = [
                    'upc'                   => !empty($existingItem['upc']) ? (string)$existingItem['upc'] : null,
                    'gtin'                  => !empty($existingItem['gtin']) ? (string)$existingItem['gtin'] : null,
                    'wpid'                  => (string)$existingItem['wpid'],
                    'item_id'               => (string)$existingItem['item_id'],
                    'sku'                   => (string)$existingItem['sku'],
                    'title'                 => (string)$existingItem['title'],
                    'online_price'          => (float)$existingItem['online_price'],
                    'online_qty'            => (int)$existingItem['online_qty'],
                    'publish_status'        => (string)$existingItem['publish_status'],
                    'lifecycle_status'      => (string)$existingItem['lifecycle_status'],
                    'status_change_reasons' => (string)$existingItem['status_change_reasons'],
                    'status'                => (int)$existingItem['status'],
                    'is_online_price_invalid' => (bool)$existingItem['is_online_price_invalid'],
                ];

                if ($newData == $existingData) {
                    continue;
                }

                if ($newData['status'] != $existingData['status']) {
                    $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
                }

                $newData['id'] = (int)$existingItem['listing_other_id'];

                /** @var \Ess\M2ePro\Model\Listing\Other $listingOtherModel */
                $listingOtherModel = $this->parentFactory->getObject($this->getComponentMode(), 'Listing_Other');
                $listingOtherModel->addData($newData)->getChildObject()->addData($newData);
                $listingOtherModel->save();
            }
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function createNotExistedOtherListings()
    {
        $isMappingEnabled = $this->getAccount()->getChildObject()->isOtherListingsMappingEnabled();

        if ($isMappingEnabled) {
            /** @var $mappingModel \Ess\M2ePro\Model\Walmart\Listing\Other\Mapping */
            $mappingModel = $this->modelFactory->getObject('Walmart_Listing_Other_Mapping');
            $mappingModel->initialize($this->getAccount());
        }

        $dataHelper      = $this->helperFactory->getObject('Data');
        $componentHelper = $this->helperFactory->getObject('Component\Walmart');

        foreach ($this->responseData as $receivedItem) {

            $isOnlinePriceInvalid = in_array(
                \Ess\M2ePro\Helper\Component\Walmart::PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE,
                $receivedItem['status_change_reason']
            );

            $newData = [
                'account_id'     => $this->getAccount()->getId(),
                'marketplace_id' => $this->getAccount()->getChildObject()->getMarketplace()->getId(),
                'product_id'     => null,

                'upc'     => !empty($receivedItem['upc']) ? (string)$receivedItem['upc'] : null,
                'gtin'    => !empty($receivedItem['gtin']) ? (string)$receivedItem['gtin'] : null,
                'wpid'    => (string)$receivedItem['wpid'],
                'item_id' => (string)$receivedItem['item_id'],

                'sku'   => (string)$receivedItem['sku'],
                'title' => $receivedItem['title'],

                'online_price' => (float)$receivedItem['price'],
                'online_qty'   => (int)$receivedItem['qty'],

                'publish_status'        => (string)$receivedItem['publish_status'],
                'lifecycle_status'      => (string)$receivedItem['lifecycle_status'],
                'status_change_reasons' => $dataHelper->jsonEncode($receivedItem['status_change_reason']),
                'is_online_price_invalid' => $isOnlinePriceInvalid,
            ];

            $newData['status'] = $componentHelper->getResultProductStatus(
                $receivedItem['publish_status'], $receivedItem['lifecycle_status'], $newData['online_qty']
            );

            $newData['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

            /** @var \Ess\M2ePro\Model\Listing\Other $listingOtherModel */
            $listingOtherModel = $this->parentFactory->getObject($this->getComponentMode(), 'Listing_Other');
            $listingOtherModel->addData($newData)->save();

            if ($isMappingEnabled) {
                $mappingModel->autoMapOtherListingProduct($listingOtherModel);
            }
        }
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPreparedProductsCollection()
    {
        if ($this->preparedListingsOtherCollection) {
            return $this->preparedListingsOtherCollection;
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Other')->getCollection();
        $collection->addFieldToFilter('account_id', (int)$this->getAccount()->getId());

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'main_table.status',
                'second_table.sku',
                'second_table.title',
                'second_table.online_price',
                'second_table.online_qty',
                'second_table.publish_status',
                'second_table.lifecycle_status',
                'second_table.status_change_reasons',
                'second_table.upc',
                'second_table.gtin',
                'second_table.ean',
                'second_table.wpid',
                'second_table.item_id',
                'second_table.listing_other_id',
                'second_table.is_online_price_invalid'
            ]
        );

        return $this->preparedListingsOtherCollection = $collection;
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'wpid';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
