<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon;

use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractExistingProductsHandler;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\OtherListingsHandler
 */
class OtherListingsHandler extends AbstractExistingProductsHandler
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
    protected $preparedListingsOtherCollection;

    //########################################

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

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function updateReceivedOtherListings()
    {
        foreach (array_chunk(array_keys($this->responseData), 200) as $skuPack) {

            $stmtTemp = $this->getPdoStatementExistingListings($skuPack);
            while ($existingItem = $stmtTemp->fetch()) {
                if (!isset($this->responseData[$existingItem['sku']])) {
                    continue;
                }

                $receivedItem = $this->responseData[$existingItem['sku']];
                unset($this->responseData[$existingItem['sku']]);

                $newData = [
                    'general_id'         => (string)$receivedItem['identifiers']['general_id'],
                    'title'              => (string)$receivedItem['title'],
                    'online_price'       => (float)$receivedItem['price'],
                    'online_qty'         => (int)$receivedItem['qty'],
                    'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                    'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
                ];

                if ($newData['is_afn_channel']) {
                    $newData['online_qty'] = null;
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
                } else {
                    if ($newData['online_qty'] > 0) {
                        $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                    } else {
                        $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                    }
                }

                $existingData = [
                    'general_id'         => (string)$existingItem['general_id'],
                    'title'              => (string)$existingItem['title'],
                    'online_price'       => (float)$existingItem['online_price'],
                    'online_qty'         => (int)$existingItem['online_qty'],
                    'is_afn_channel'     => (bool)$existingItem['is_afn_channel'],
                    'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id'],
                    'status'             => (int)$existingItem['status']
                ];

                if ($receivedItem['title'] === null ||
                    $receivedItem['title'] == \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER) {
                    unset($newData['title'], $existingData['title']);
                }

                if ($existingItem['is_repricing'] && !$existingItem['is_repricing_disabled']) {
                    unset($newData['online_price'], $existingData['online_price']);
                }

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
            /** @var $mappingModel \Ess\M2ePro\Model\Amazon\Listing\Other\Mapping */
            $mappingModel = $this->modelFactory->getObject('Amazon_Listing_Other_Mapping');
            $mappingModel->initialize($this->getAccount());
        }

        foreach ($this->responseData as $receivedItem) {

            $newData = [
                'account_id'     => $this->getAccount()->getId(),
                'marketplace_id' => $this->getAccount()->getChildObject()->getMarketplace()->getId(),
                'product_id'     => null,

                'general_id' => (string)$receivedItem['identifiers']['general_id'],

                'sku'   => (string)$receivedItem['identifiers']['sku'],
                'title' => $receivedItem['title'],

                'online_price' => (float)$receivedItem['price'],
                'online_qty'   => (int)$receivedItem['qty'],

                'is_afn_channel'     => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            ];

            if (isset($this->responserParams['full_items_data']) && $this->responserParams['full_items_data'] &&
                $newData['title'] == \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER
            ) {
                $newData['title'] = null;
            }

            if ((bool)$newData['is_afn_channel']) {
                $newData['online_qty'] = null;
                $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
            } else {
                if ((int)$newData['online_qty'] > 0) {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                } else {
                    $newData['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
            }

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
        if ($this->preparedListingsOtherCollection !== null) {
            return $this->preparedListingsOtherCollection;
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Other')->getCollection();
        $collection->addFieldToFilter('account_id', (int)$this->getAccount()->getId());

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [
                'main_table.status',
                'second_table.sku',
                'second_table.general_id',
                'second_table.title',
                'second_table.online_price',
                'second_table.online_qty',
                'second_table.is_afn_channel',
                'second_table.is_isbn_general_id',
                'second_table.listing_other_id',
                'second_table.is_repricing',
                'second_table.is_repricing_disabled'
            ]
        );

        return $this->preparedListingsOtherCollection = $collection;
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'sku';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
