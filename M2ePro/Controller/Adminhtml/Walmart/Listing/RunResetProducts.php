<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

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
                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    'Item cannot be reset. Most probably it is not blocked or requires a price adjusting.',
                    \Ess\M2ePro\Model\Response\Message::TYPE_ERROR
                );

                $logger->logListingProductMessage($listingProduct, $message);
                continue;
            }

            /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
            $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
            $lockManager->setListingProduct($listingProduct);
            $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
            $lockManager->setLogsActionId($logsActionId);
            $lockManager->setLogsAction(\Ess\M2ePro\Model\Listing\Log::ACTION_RESET_BLOCKED_PRODUCT);

            if ($lockManager->checkLocking()) {
                $result = 'warning';
                continue;
            }

            $listingProduct->addData([
                'status'                  => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'online_qty'              => null,
                'online_price'            => null,
                'online_promotions'       => null,
                'online_details'          => null,
                'is_online_price_invalid' => 0,
                'status_change_reasons'   => null,
                'is_missed_on_channel'    => 0
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

        $instructionsData = [];
        foreach ($listingsProductsIds as $listingProductId) {
            $instructionsData[] = [
                'listing_product_id' => $listingProductId,
                'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'type'               => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_ADDED,
                'initiator'          => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_ADDING_PRODUCT,
                'priority'           => 30,
            ];
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructionsData);


        $this->setJsonContent([
            'result'    => $result,
            'action_id' => $logsActionId
        ]);
        return $this->getResult();
    }
}
