<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class RemoveHandler extends \Ess\M2ePro\Model\Listing\Product\RemoveHandler
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\EventDispatcher */
    private $eventDispatcher;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product */
    private $parentAmazonListingProductForProcess;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product|null */
    private $amazonListingProduct = null;

    public function __construct(
        EventDispatcher $eventDispatcher,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->eventDispatcher = $eventDispatcher;
        $this->exceptionHelper = $exceptionHelper;
    }

    protected function eventBeforeProcess(): void
    {
        parent::eventBeforeProcess();

        $this->amazonListingProduct = $this->getAmazonListingProduct();

        $variationManager = $this->getAmazonListingProduct()->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
            $parentAmazonListingProduct = $variationManager
                ->getTypeModel()
                ->getAmazonParentListingProduct();

            $this->parentAmazonListingProductForProcess = $parentAmazonListingProduct;

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $variationManager->getTypeModel();

            if ($childTypeModel->isVariationProductMatched()) {
                $parentAmazonListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                    $variationManager->getTypeModel()->getProductOptions()
                );
            }
        }
    }

    protected function eventAfterProcess(): void
    {
        parent::eventAfterProcess();

        try {
            $this->processParentRelation();
            $this->dispatchEvents($this->amazonListingProduct);
        } catch (\Throwable $exception) {
            $this->exceptionHelper->process($exception);
        }
    }

    private function processParentRelation(): void
    {
        if ($this->parentAmazonListingProductForProcess === null) {
            return;
        }

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $this->parentAmazonListingProductForProcess->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    private function dispatchEvents(\Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct): void
    {
        if ($amazonListingProduct->isAfnChannel()) {
            $this->eventDispatcher->dispatchEventFbaProductDeleted(
                $amazonListingProduct->getAmazonAccount()->getMerchantId(),
                $amazonListingProduct->getSku()
            );
        }
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }
}
