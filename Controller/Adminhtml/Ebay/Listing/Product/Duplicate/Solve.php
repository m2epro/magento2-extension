<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Duplicate;

class Solve extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId, null, false);

        $result   = true;
        $messages = [];

        if (is_null($listingProduct)) {

            $result = false;
            $messages[] = $this->__("Unable to load product ID [{$listingProductId}].");
        }

        if ($result && $this->getRequest()->getParam('stop_duplicated_item') == 1) {
            $result = $this->solveEbayItemDuplicateStop($listingProduct, $messages);
        }

        if ($result) {

            $additionalData = $listingProduct->getAdditionalData();
            unset($additionalData['item_duplicate_action_required']);

            $listingProduct->getChildObject()->setData(
                'item_uuid', $listingProduct->getChildObject()->generateItemUUID()
            );
            $listingProduct->getChildObject()->setData('is_duplicate', 0);
            $listingProduct->setData('additional_data', $this->getHelper('Data')->jsonEncode($additionalData));
            $listingProduct->save();
        }

        if ($result && $this->getRequest()->getParam('list_current_item') == 1) {
            $result = $this->solveEbayItemDuplicateList($listingProduct, $messages);
        }

        $this->setJsonContent([
            'result'  => $result,
            'message' => implode(' ', $messages)
        ]);
        return $this->getResult();
    }

    //########################################

    private function solveEbayItemDuplicateStop(\Ess\M2ePro\Model\Listing\Product $listingProduct, array &$messages)
    {
        $duplicateMark = $listingProduct->getSetting('additional_data', 'item_duplicate_action_required');
        $itemId = $duplicateMark['item_id'];

        if (!$itemId) {

            $messages[] = $this->__('Item ID is not presented.');
            return false;
        }

        /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('item', 'update', 'ends',
            ['items' => [$itemId]], null,
            $listingProduct->getMarketplace()->getId(),
            $listingProduct->getAccount()->getId()
        );

        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if (!isset($response['result'][0]['ebay_end_date_raw'])) {
            $messages[] = $this->__('Unable to stop eBay item ID.');
            return false;
        }

        return true;
    }

    private function solveEbayItemDuplicateList(\Ess\M2ePro\Model\Listing\Product $listingProduct, array &$messages)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

        $listingProduct = clone $listingProduct;
        $listingProduct->setActionConfigurator($configurator);

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Item\Dispatcher');
        $dispatcher->process(\Ess\M2ePro\Model\Listing\Product::ACTION_LIST, [$listingProduct], [
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER,
            'is_realtime'    => true,
        ]);

        return true;
    }

    //########################################
}