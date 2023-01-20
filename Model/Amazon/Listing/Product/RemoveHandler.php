<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class RemoveHandler extends \Ess\M2ePro\Model\Listing\Product\RemoveHandler
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product */
    private $parentAmazonListingProductForProcess;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->exceptionHelper = $exceptionHelper;
    }

    protected function eventBeforeProcess(): void
    {
        parent::eventBeforeProcess();

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

        if ($this->parentAmazonListingProductForProcess === null) {
            return;
        }

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $this->parentAmazonListingProductForProcess->getVariationManager()->getTypeModel();
        try {
            $parentTypeModel->getProcessor()->process();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);
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
