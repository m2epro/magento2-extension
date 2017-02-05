<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor;

class Mass extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_PROCESSORS_COUNT_PER_ONE_TIME = 1000;

    //########################################

    /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
    private $listingsProducts = array();

    private $forceExecuting = true;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param array $listingsProducts
     * @return $this
     */
    public function setListingsProducts(array $listingsProducts)
    {
        $this->listingsProducts = $listingsProducts;
        return $this;
    }

    /**
     * @param bool $forceExecuting
     * @return $this
     */
    public function setForceExecuting($forceExecuting = true)
    {
        $this->forceExecuting = $forceExecuting;
        return $this;
    }

    //########################################

    public function execute()
    {
        $uniqueProcessors = $this->getUniqueProcessors();

        $alreadyProcessed = array();

        foreach ($uniqueProcessors as $listingProductId => $processor) {
            if (!$this->forceExecuting && count($alreadyProcessed) >= self::MAX_PROCESSORS_COUNT_PER_ONE_TIME) {
                break;
            }

            $processor->process();

            $alreadyProcessed[] = $listingProductId;
        }

        if ($this->forceExecuting || count($uniqueProcessors) <= count($alreadyProcessed)) {
            return;
        }

        $notProcessedListingProductIds = array_unique(array_diff(array_keys($uniqueProcessors), $alreadyProcessed));

        $resource  = $this->amazonFactory->getObject('Listing\Product')->getResource();
        $connWrite = $resource->getConnection();

        $connWrite->update(
            $resource->getChildTable(\Ess\M2ePro\Helper\Component\Amazon::NICK),
            array('variation_parent_need_processor' => 1),
            array(
                'is_variation_parent = ?'   => 1,
                'listing_product_id IN (?)' => $notProcessedListingProductIds,
            )
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor[]
     */
    private function getUniqueProcessors()
    {
        $processors = array();

        foreach ($this->listingsProducts as $listingProduct) {
            if (isset($processors[$listingProduct->getId()])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $variationManager = $amazonListingProduct->getVariationManager();

            if (!$variationManager->isRelationParentType()) {
                continue;
            }

            $processors[$listingProduct->getId()] = $variationManager->getTypeModel()->getProcessor();
        }

        return $processors;
    }

    //########################################
}