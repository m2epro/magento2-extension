<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation;

class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    // ---------------------------------------

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

    //########################################

    /**
     * @return bool
     */
    public function isVariationProduct()
    {
        return (bool)(int)$this->getAmazonListingProduct()->getData('is_variation_product');
    }

    /**
     * @return bool
     */
    public function isVariationParent()
    {
        return (bool)(int)$this->getAmazonListingProduct()->getData('is_variation_parent');
    }

    /**
     * @return int
     */
    public function getVariationParentId()
    {
        return (int)$this->getAmazonListingProduct()->getData('variation_parent_id');
    }

    //########################################

    /**
     * @return bool
     */
    public function isSimpleType()
    {
        return !$this->isVariationProduct();
    }

    /**
     * @return bool
     */
    public function isIndividualType()
    {
        return $this->isVariationProduct() &&
               !$this->isVariationParent() &&
               !$this->getVariationParentId();
    }

    /**
     * @return bool
     */
    public function isRelationParentType()
    {
        return $this->isVariationProduct() &&
               $this->isVariationParent() &&
               !$this->getVariationParentId();
    }

    /**
     * @return bool
     */
    public function isRelationChildType()
    {
        return $this->isVariationProduct() &&
               !$this->isVariationParent() &&
               $this->getVariationParentId();
    }

    // ---------------------------------------

    public function setSimpleType()
    {
        $this->getAmazonListingProduct()->setData('is_variation_product',0)
                                  ->setData('is_variation_parent',0)
                                  ->setData('variation_parent_id',NULL)
                                  ->save();
    }

    public function setIndividualType()
    {
        $this->getAmazonListingProduct()->setData('is_variation_parent',0)
                                  ->setData('variation_parent_id',NULL)
                                  ->save();
    }

    public function setRelationParentType()
    {
        $this->getAmazonListingProduct()->setData('is_variation_parent',1)
                                  ->setData('variation_parent_id',NULL)
                                  ->save();
    }

    public function setRelationChildType($variationParentId)
    {
        $this->getAmazonListingProduct()->setData('is_variation_parent',0)
                                  ->setData('variation_parent_id',$variationParentId)
                                  ->save();
    }

    //########################################

    /**
     * @return bool
     */
    public function isIndividualMode()
    {
        return $this->isIndividualType();
    }

    /**
     * @return bool
     */
    public function isRelationMode()
    {
        return $this->isRelationParentType() || $this->isRelationChildType();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isLogicalUnit()
    {
        return $this->isRelationParentType();
    }

    /**
     * @return bool
     */
    public function isPhysicalUnit()
    {
        return $this->isIndividualType() || $this->isRelationChildType();
    }

    //########################################

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getTypeModel()
    {
        $model = NULL;

        if ($this->isIndividualType()) {
            $model = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Manager\Type\Individual');
        } else if ($this->isRelationParentType()) {
            $model = $this->modelFactory
                ->getObject('Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation');
        } else if ($this->isRelationChildType()) {
            $model = $this->modelFactory
                ->getObject('Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation');
        } else {
            throw new \Ess\M2ePro\Model\Exception('This Product is not a Variation Product.');
        }

        /** @var $model \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\AbstractModel */
        $model->setVariationManager($this);

        return $model;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function modeCanBeSwitched()
    {
        return ($this->isIndividualType() || $this->isRelationParentType()) &&
               !$this->getAmazonListingProduct()->getGeneralId() &&
               !$this->getAmazonListingProduct()->isGeneralIdOwner();
    }

    public function switchModeToAnother()
    {
        if (!$this->modeCanBeSwitched()) {
            return false;
        }

        if ($this->isIndividualType()) {
            $this->getTypeModel()->clearTypeData();
            $this->setRelationParentType();
            $this->getTypeModel()->resetProductAttributes();
            $this->getTypeModel()->getProcessor()->process();
        } else if ($this->isRelationParentType()) {
            $this->getTypeModel()->getProcessor()->process();
            $this->getTypeModel()->clearTypeData();
            $this->setIndividualType();
            $this->getTypeModel()->resetProductVariation();
        }

        return true;
    }

    //########################################
}