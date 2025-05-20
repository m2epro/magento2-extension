<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator;

class General extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    /**
     * @return bool
     */
    public function validate()
    {
        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProduct()) {
            return false;
        }

        if ($this->getAmazonListingProduct()->isAfnChannel()) {
            $this->addMessage(
                'List Action for FBA Items is impossible as their Quantity is unknown. You can run Revise
            Action for such Items, but the Quantity value will be ignored.',
                self::ERROR_FBA_ITEM_LIST
            );

            return false;
        }

        if (!$this->getListingProduct()->isNotListed() || !$this->getListingProduct()->isListable()) {
            $this->addMessage(
                'Item is already on Amazon, or not available.',
                \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR
            );

            return false;
        }

        if ($this->getVariationManager()->isLogicalUnit()) {
            return true;
        }

        if (!$this->validateQty()) {
            return false;
        }

        //todo wrong?
        if (!$this->validateRegularPrice() || !$this->validateBusinessPrice()) {
            return false;
        }

        if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $productType = $this->getAmazonListingProduct()->getProductTypeTemplate();
            if (
                $productType === null
                || $productType->getNick() === \Ess\M2ePro\Model\Amazon\Template\ProductType::GENERAL_PRODUCT_TYPE_NICK
            ) {
                $this->addMessage(
                    "To list a new ASIN/ISBN on Amazon, please assign a valid Product Type. "
                    . "Product Type 'General' cannot be used.",
                    self::PRODUCT_TYPE_INVALID
                );
                return false;
            }
        }

        $condition = $this->getAmazonListingProduct()->getListingSource()->getCondition();
        if (empty($condition)) {
            $this->addMessage(
                'You cannot list this Product because the Item Condition is not specified.
                               You can set the Condition in the Selling Settings of the Listing.',
                self::ITEM_CONDITION_NOT_SPECIFIED
            );
            return false;
        }

        if ($this->getVariationManager()->isPhysicalUnit() && !$this->validatePhysicalUnitMatching()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationChildType() && !$this->validateChildRequirements()) {
            return false;
        }

        return true;
    }

    //########################################

    private function validateChildRequirements()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $this->getVariationManager()
                                           ->getTypeModel()
                                           ->getParentListingProduct()
                                           ->getChildObject();

        if (!$parentAmazonListingProduct->getGeneralId()) {
            $this->addMessage(
                'You cannot list this Product because for managing Child Products,
                              the respective Parent Product needs to be connected to Amazon Parent Product.
                              Please link your Magento Parent Product to Amazon Parent Product and try again.',
                self::PARENT_NOT_LINKED
            );

            return false;
        }

        if (
            !$this->getAmazonListingProduct()->isGeneralIdOwner() &&
            !$this->getAmazonListingProduct()->getGeneralId()
        ) {
            $this->addMessage(
                'You cannot list this Product because it has to be whether linked to
                              existing Amazon Product or to be ready for creation of the new ASIN/ISBN.',
                self::PRODUCT_MISSING_LINK_OR_NEW_IDENTIFIER
            );

            return false;
        }

        return true;
    }
}
