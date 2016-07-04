<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop;

class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function validate()
    {
        $params = $this->getParams();

        if (empty($params['remove']) && !$this->validateBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProductFlags()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if ($this->getAmazonListingProduct()->isAfnChannel()) {

            if (!empty($params['remove'])) {

                // M2ePro\TRANSLATIONS
                // Stop Action for FBA Items is impossible as their Quantity is unknown.
                $this->addMessage('Stop Action for FBA Items is impossible as their Quantity is unknown.');
                $this->getListingProduct()->delete();
                $this->getListingProduct()->isDeleted(true);

            } else {

                // M2ePro\TRANSLATIONS
                // Stop Action for FBA Items is impossible as their Quantity is unknown. You can run Revise Action for such Items, but the Quantity value will be ignored.
                $this->addMessage('Stop Action for FBA Items is impossible as their Quantity is unknown. You can run
                 Revise Action for such Items, but the Quantity value will be ignored.');
            }

            return false;
        }

        if (!$this->getListingProduct()->isListed() || !$this->getListingProduct()->isStoppable()) {

            if (empty($params['remove'])) {

                // M2ePro\TRANSLATIONS
                // Item is not Listed or not available
                $this->addMessage('Item is not active or not available');

            } else {
                if ($this->getVariationManager()->isRelationChildType() &&
                    $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
                ) {
                    $parentAmazonListingProduct = $this->getVariationManager()
                        ->getTypeModel()
                        ->getAmazonParentListingProduct();

                    $parentAmazonListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                        $this->getVariationManager()->getTypeModel()->getProductOptions()
                    );
                }

                $this->getListingProduct()->delete();
                $this->getListingProduct()->isDeleted(true);
            }

            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        return true;
    }

    //########################################
}