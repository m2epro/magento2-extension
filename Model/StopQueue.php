<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

class StopQueue extends ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\StopQueue');
    }

    //########################################

    public function getItemData()
    {
        return $this->getData('item_data');
    }

    public function getDecodedItemData()
    {
        return $this->getHelper('Data')->jsonDecode($this->getItemData());
    }

    // ---------------------------------------

    public function getAccountHash()
    {
        return $this->getData('account_hash');
    }

    public function getMarketplaceId()
    {
        return $this->getData('marketplace_id');
    }

    public function getComponentMode()
    {
        return $this->getData('component_mode');
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return (bool)$this->getData('is_processed');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function add(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!$listingProduct->isStoppable()) {
            return false;
        }

        $itemData = $this->getItemDataByListingProduct($listingProduct);

        if (is_null($itemData)) {
            return false;
        }

        $marketplaceNativeId = $listingProduct->isComponentModeEbay() ?
                                        $listingProduct->getMarketplace()->getNativeId() : NULL;

        $addedData = array(
            'item_data' => $this->getHelper('Data')->jsonEncode($itemData),
            'account_hash' => $listingProduct->getAccount()->getChildObject()->getServerHash(),
            'marketplace_id' => $marketplaceNativeId,
            'component_mode' => $listingProduct->getComponentMode(),
            'is_processed' => 0
        );

        $this->activeRecordFactory->getObject('StopQueue')->setData($addedData)->save();

        return true;
    }

    private function getItemDataByListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $connectorName = ucfirst($listingProduct->getComponentMode()).'\Connector\\';
        $connectorName .= $listingProduct->isComponentModeEbay() ? 'Item' : 'Product';
        $connectorName .= '\Stop\Requester';

        $connectorParams = array(
            'logs_action_id' => 0,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN,
        );

        try {

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
            $dispatcher = $this->modelFactory->getObject(
                ucfirst($listingProduct->getComponentMode()).'\Connector\Dispatcher'
            );

            $connector = $dispatcher->getCustomConnector($connectorName, $connectorParams);
            $connector->setListingProduct($listingProduct);

            $itemData = $connector->getRequestDataPackage();
        } catch (\Exception $exception) {
            return NULL;
        }

        if (!isset($itemData['data'])) {
            return NULL;
        }

        return $itemData['data'];
    }

    //########################################
}