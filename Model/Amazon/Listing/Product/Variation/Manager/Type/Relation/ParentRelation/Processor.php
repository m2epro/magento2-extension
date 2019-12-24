<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $marketplaceId = null;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $typeModel */
    private $typeModel = null;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory|null  */
    private $activeRecordFactory = null;

    /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplate */
    private $descriptionTemplate = null;

    private $possibleThemes = null;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    public function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @param $listingProduct
     * @return $this
     */
    public function setListingProduct($listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function process()
    {
        if ($this->listingProduct === null) {
            throw new \Ess\M2ePro\Model\Exception('Listing Product was not set.');
        }

        $this->getTypeModel()->enableCache();

        foreach ($this->getSortedProcessors() as $processor) {
            $this->getProcessorModel($processor)->process();
        }

        $this->listingProduct->getChildObject()->setData('variation_parent_need_processor', 0);
        $this->listingProduct->save();
    }

    //########################################

    private function getSortedProcessors()
    {
        return [
            'Template',
            'GeneralIdOwner',
            'Attributes',
            'Theme',
            'MatchedAttributes',
            'Options',
            'Status',
            'Selling',
        ];
    }

    private function getProcessorModel($processorName)
    {
        $model = $this->modelFactory->getObject(
            'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\\'.$processorName
        );
        $model->setProcessor($this);

        return $model;
    }

    //########################################

    /**
     * @return bool
     */
    public function isGeneralIdSet()
    {
        return (bool)$this->getAmazonListingProduct()->getGeneralId();
    }

    /**
     * @return bool
     */
    public function isGeneralIdOwner()
    {
        return $this->getAmazonListingProduct()->isGeneralIdOwner();
    }

    //########################################

    /**
     * @return array
     */
    public function getMagentoProductVariations()
    {
        return $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();
    }

    public function getProductVariation(array $options)
    {
        return $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationTypeStandard($options);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
     */
    public function getTypeModel()
    {
        if ($this->typeModel !== null) {
            return $this->typeModel;
        }

        return $this->typeModel = $this->getAmazonListingProduct()
            ->getVariationManager()
            ->getTypeModel();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $childListingProduct
     * @return bool
     */
    public function tryToRemoveChildListingProduct(\Ess\M2ePro\Model\Listing\Product $childListingProduct)
    {
        if ($childListingProduct->isLocked()) {
            return false;
        }

        if ($childListingProduct->isStoppable()) {
            $this->activeRecordFactory->getObject('StopQueue')->add($childListingProduct);
        }

        $this->getTypeModel()->removeChildListingProduct($childListingProduct->getId());

        return true;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        if ($this->descriptionTemplate !== null) {
            return $this->descriptionTemplate;
        }

        return $this->descriptionTemplate = $this->getAmazonListingProduct()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     */
    public function getAmazonDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return array|null
     */
    public function getPossibleThemes()
    {
        if ($this->possibleThemes !== null) {
            return $this->possibleThemes;
        }

        $marketPlaceId = $this->getMarketplaceId();

        $possibleThemes = $this->modelFactory->getObject('Amazon_Marketplace_Details')
            ->setMarketplaceId($marketPlaceId)
            ->getVariationThemes(
                $this->getAmazonDescriptionTemplate()->getProductDataNick()
            );

        $variationHelper = $this->getHelper('Component_Amazon_Variation');
        $themesUsageData = $variationHelper->getThemesUsageData();
        $usedThemes = [];

        if (!empty($themesUsageData[$marketPlaceId])) {
            foreach ($themesUsageData[$marketPlaceId] as $theme => $count) {
                if (!empty($possibleThemes[$theme])) {
                    $usedThemes[$theme] = $possibleThemes[$theme];
                }
            }
        }

        return $this->possibleThemes = array_merge($usedThemes, $possibleThemes);
    }

    /**
     * @return int|null
     */
    public function getMarketplaceId()
    {
        if ($this->marketplaceId !== null) {
            return $this->marketplaceId;
        }

        return $this->marketplaceId = $this->getListingProduct()->getListing()->getMarketplaceId();
    }

    //########################################
}
