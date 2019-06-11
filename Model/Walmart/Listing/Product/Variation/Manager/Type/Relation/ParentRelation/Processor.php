<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $marketplaceId = null;

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $typeModel */
    private $typeModel = null;

    /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplate */
    private $descriptionTemplate = null;

    /** @var \Ess\M2ePro\Model\Walmart\Template\Category $descriptionTemplate */
    private $walmartCategoryTemplate = null;

    private $possibleChannelAttributes = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
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
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    public function getWalmartListingProduct()
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

    public function process()
    {
        if (is_null($this->listingProduct)) {
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
        return array(
            'Template',
            'Attributes',
            'MatchedAttributes',
            'Options',
            'Status',
            'Selling',
        );
    }

    private function getProcessorModel($processorName)
    {
        $model = $this->modelFactory->getObject(
            'Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\\' . $processorName
        );
        $model->setProcessor($this);

        return $model;
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
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
     */
    public function getTypeModel()
    {
        if (!is_null($this->typeModel)) {
            return $this->typeModel;
        }

        return $this->typeModel = $this->getWalmartListingProduct()
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
        if (!is_null($this->descriptionTemplate)) {
            return $this->descriptionTemplate;
        }

        return $this->descriptionTemplate = $this->getWalmartListingProduct()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Description
     */
    public function getWalmartDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Category
     */
    public function getWalmartCategoryTemplate()
    {
        if (!is_null($this->walmartCategoryTemplate)) {
            return $this->walmartCategoryTemplate;
        }

        return $this->walmartCategoryTemplate = $this->getWalmartListingProduct()->getCategoryTemplate();
    }

    //########################################

    /**
     * @return array|null
     */
    public function getPossibleChannelAttributes()
    {
        if (!is_null($this->possibleChannelAttributes)) {
            return $this->possibleChannelAttributes;
        }

        $possibleChannelAttributes = $this->modelFactory->getObject('Walmart\Marketplace\Details')
            ->setMarketplaceId($this->getMarketplaceId())
            ->getVariationAttributes(
                $this->getWalmartCategoryTemplate()->getProductDataNick()
            );

        return $this->possibleChannelAttributes = $possibleChannelAttributes;
    }

    /**
     * @return int|null
     */
    public function getMarketplaceId()
    {
        if (!is_null($this->marketplaceId)) {
            return $this->marketplaceId;
        }

        return $this->marketplaceId = $this->getListingProduct()->getListing()->getMarketplaceId();
    }

    //########################################
}