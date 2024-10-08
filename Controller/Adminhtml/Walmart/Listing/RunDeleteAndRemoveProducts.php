<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class RunDeleteAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLoggerResource;
    private \Ess\M2ePro\Model\Listing\Log $listingLogger;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLoggerResource,
        \Ess\M2ePro\Model\Listing\Log $listingLogger,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->exceptionHelper = $exceptionHelper;
        $this->listingLoggerResource = $listingLoggerResource;
        $this->listingLogger = $listingLogger;
    }

    public function execute()
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return $this->setRawContent('You should select Products');
        }

        $productsCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $productsCollection->addFieldToFilter('id', explode(',', $listingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $productsCollection->getItems();
        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        /** @var \Ess\M2ePro\Model\Listing\Product[][] $parentListingsProducts */
        $parentListingsProducts = [];
        /** @var \Ess\M2ePro\Model\Listing\Product[][] $childListingsProducts */
        $childListingsProducts = [];

        foreach ($listingsProducts as $index => $listingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {
                /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
                $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
                $lockManager->setListingProduct($listingProduct);

                $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
                $lockManager->setLogsActionId($logsActionId);
                $lockManager->setLogsAction(\Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT);

                if ($lockManager->checkLocking()) {
                    unset($listingsProducts[$index]);
                }

                continue;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $typeModel */
            $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

            $tempChildListingsProducts = $typeModel->getChildListingsProducts();

            $isParentLocked = false;

            foreach ($tempChildListingsProducts as $tempChildListingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
                $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
                $lockManager->setListingProduct($tempChildListingProduct);

                $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
                $lockManager->setLogsActionId($logsActionId);
                $lockManager->setLogsAction(\Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT);

                if ($lockManager->checkLocking()) {
                    $isParentLocked = true;
                    break;
                }
            }

            unset($listingsProducts[$index]);

            if (!$isParentLocked) {
                // @codingStandardsIgnoreLine
                $childListingsProducts = array_merge($childListingsProducts, $tempChildListingsProducts);
                $parentListingsProducts[$index] = $listingProduct;
            }
        }

        $listingsProducts = array_merge($listingsProducts, $childListingsProducts);

        if (empty($listingsProducts) && empty($parentListingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        foreach ($listingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$listingProduct->isNotListed()) {
                $connector = $dispatcher->getVirtualConnector(
                    'product',
                    'retire',
                    'entity',
                    ['sku' => $walmartListingProduct->getSku()],
                    null,
                    $listingProduct->getAccount()
                );

                try {
                    $dispatcher->process($connector);

                    $hasErrorsOrWarnings = false;

                    foreach ($connector->getResponse()->getMessages()->getEntities() as $message) {
                        if ($message->isError() || $message->isWarning()) {
                            $this->writeLog(
                                $listingProduct,
                                (string)__($message->getText()),
                                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                            );
                            $hasErrorsOrWarnings = true;
                        }
                    }

                    if (!$hasErrorsOrWarnings) {
                        $this->writeLog(
                            $listingProduct,
                            (string)__('Item was Retired from Walmart catalog.'),
                            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
                        );
                    }

                    if ($hasErrorsOrWarnings) {
                        continue;
                    }
                } catch (\Throwable $exception) {
                    $this->exceptionHelper->process($exception);

                    continue;
                }
            }

            $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($listingProduct);
            $removeHandler->process();
        }

        foreach ($parentListingsProducts as $parentListingProduct) {
            $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($parentListingProduct);
            $removeHandler->process();
        }

        $this->setJsonContent(['result' => 'success']);

        return $this->getResult();
    }

    private function writeLog(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        string $message,
        int $type
    ): void {
        $this->listingLogger->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $this->listingLogger->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            $this->listingLoggerResource->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_RETIRE_AND_REMOVE_PRODUCT,
            $message,
            $type
        );
    }
}
