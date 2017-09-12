<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise;

class Validator extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    //########################################

    /**
     * @return bool
     */
    public function validate()
    {
        if (!$this->validateBlocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProductFlags()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        $params = $this->getParams();

        if (!empty($params['switch_to']) && !$this->getConfigurator()->isQtyAllowed()) {
            // M2ePro\TRANSLATIONS
            // Fulfillment mode can not be switched if QTY feed is not allowed.
            $this->addMessage('Fulfillment mode can not be switched if QTY feed is not allowed.');
            return false;
        }

        if ($this->getConfigurator()->isQtyAllowed()) {

            if ($this->getAmazonListingProduct()->isAfnChannel()) {

                if (empty($params['switch_to'])) {

                    $this->getConfigurator()->disallowQty();

                    // M2ePro\TRANSLATIONS
                    // This Product is an FBA Item, so it’s Quantity updating will change it to MFN. Thus QTY feed, Production Time and Restock Date Values will not be updated. Inventory management for FBA Items is currently unavailable in M2E Pro. However, you can do that directly in your Amazon Seller Central.
                    $this->addMessage(
                        'This Product is an FBA Item, so it’s Quantity updating will change it to MFN. Thus QTY feed,
                        Production Time and Restock Date Values will not be updated. Inventory management for FBA
                        Items is currently unavailable in M2E Pro. However, you can do that directly in your Amazon
                        Seller Central.',
                        \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                    );

                } else {

                    $afn = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN;

                    if ($params['switch_to'] === $afn) {
                        // M2ePro\TRANSLATIONS
                        // You cannot switch Fulfillment because it is applied now.
                        $this->addMessage('You cannot switch Fulfillment because it is applied now.');
                        return false;
                    }
                }

            } else {

                $mfn = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN;

                if (!empty($params['switch_to']) && $params['switch_to'] === $mfn) {
                    // M2ePro\TRANSLATIONS
                    // You cannot switch Fulfillment because it is applied now.
                    $this->addMessage('You cannot switch Fulfillment because it is applied now.');
                    return false;
                }
            }
        }

        if ($this->getAmazonListingProduct()->isAfnChannel()) {

            if ($this->getConfigurator()->isShippingOverrideAllowed() &&
                $this->getAmazonAccount()->isShippingModeOverride() &&
                $this->getAmazonListingProduct()->isExistShippingOverrideTemplate()) {

                $this->getConfigurator()->disallowShippingOverride();

                // M2ePro_TRANSLATIONS
                // The Shipping Override Settings will not be sent for this Product because it is an FBA Item. Amazon will handle the delivery of the Order.
                $this->addMessage(
                    'The Shipping Override Settings will not be sent for this Product because it is an FBA Item.
                    Amazon will handle the delivery of the Order.',
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            } elseif ($this->getConfigurator()->isShippingTemplateAllowed() &&
                $this->getAmazonAccount()->isShippingModeTemplate() &&
                $this->getAmazonListingProduct()->isExistShippingTemplateTemplate()) {

                $this->getConfigurator()->disallowShippingTemplate();

                // M2ePro_TRANSLATIONS
                // The Shipping Template Settings will not be sent for this Product because it is an FBA Item. Amazon will handle the delivery of the Order.
                $this->addMessage(
                    'The Shipping Template Settings will not be sent for this Product because it is an FBA Item.
                    Amazon will handle the delivery of the Order.',
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            }
        }

        if ($this->getVariationManager()->isPhysicalUnit() && !$this->validatePhysicalUnitMatching()) {
            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        if (!$this->getAmazonListingProduct()->isAfnChannel() &&
            (!$this->getListingProduct()->isListed() || !$this->getListingProduct()->isRevisable())
        ) {

            // M2ePro\TRANSLATIONS
            // Item is not Listed or not available
            $this->addMessage('Item is not Listed or not available');

            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        if (!$this->validateRegularPrice() || !$this->validateBusinessPrice()) {
            return false;
        }

        return true;
    }

    //########################################
}