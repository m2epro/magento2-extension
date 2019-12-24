<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Model\Lock\Item\Manager;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\RunResetProducts
 */
class RunResetProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('selected_products');
        if (empty($productsIds)) {
            $this->setAjaxContent($this->__('You should select Products'));
            return $this->getResult();
        }

        $listingsProductsIds = explode(',', $productsIds);

        $listingsProducts = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingsProducts->addFieldToFilter('listing_product_id', $listingsProductsIds);

        if ($listingsProducts->getSize() === 0) {
            $this->setAjaxContent($this->__('No products provided.'));
            return $this->getResult();
        }

        $result       = 'success';
        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                                  ->getResource()
                                                  ->getNextActionId();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Logger $logger */
        $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');
        $logger->setActionId($logsActionId);
        $logger->setAction(\Ess\M2ePro\Model\Listing\Log::ACTION_RESET_BLOCKED_PRODUCT);
        $logger->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        foreach ($listingsProducts->getItems() as $index => $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if ($listingProduct->getChildObject()->isOnlinePriceInvalid() ||
                $listingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED
            ) {
                $result  = 'error';
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    'Item cannot be reset. Most probably it is not blocked or requires a price adjusting.',
                    \Ess\M2ePro\Model\Response\Message::TYPE_ERROR
                );

                $logger->logListingProductMessage($listingProduct, $message);
                continue;
            }

            if ($this->isLocked($listingProduct)) {
                $result = 'warning';
                continue;
            }

            $listingProduct->addData([
                'status'                  => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'online_qty'              => null,
                'online_price'            => null,
                'online_promotions'       => null,
                'online_details'          => null,
                'is_details_data_changed' => 0,
                'is_online_price_invalid' => 0,
                'status_change_reasons'   => null,
                'is_missed_on_channel'    => 0,
                'tried_to_list'           => 0
            ]);
            $listingProduct->save();

            if ($listingProduct->getChildObject()->getVariationManager()->isRelationChildType()) {
                /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
                $parentListingProduct = $listingProduct->getChildObject()->getVariationManager()->getTypeModel()
                                                       ->getParentListingProduct();

                $parentType = $parentListingProduct->getChildObject()->getVariationManager()->getTypeModel();
                $parentType->getProcessor()->process();
            }
        }

        $this->setJsonContent([
            'result'    => $result,
            'action_id' => $logsActionId
        ]);
        return $this->getResult();
    }

    private function isLocked($listingProduct)
    {
        if ($listingProduct->isSetProcessingLock(null)) {
            return true;
        }

        /** @var Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItemManager->setNick($listingProduct->getComponentMode()
                                  . '_listing_product_'
                                  . $listingProduct->getId());
        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(1800)) {
            $lockItemManager->remove();
            return false;
        }

        return true;
    }
}
