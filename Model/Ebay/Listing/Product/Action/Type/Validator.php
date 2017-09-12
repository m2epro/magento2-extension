<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator;

abstract class Validator extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = array();

    /** @var Configurator $configurator */
    private $configurator = NULL;

    /**
     * @var array
     */
    private $messages = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    //########################################

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    // ---------------------------------------

    /**
     * @param Configurator $configurator
     * @return $this
     */
    public function setConfigurator(Configurator $configurator)
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * @return Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

    abstract public function validate();

    //########################################

    protected function addMessage($message, $type = Message::TYPE_ERROR)
    {
        $this->messages[] = array(
            'text' => $message,
            'type' => $type,
        );
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getListingProduct()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    protected function getEbayMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    protected function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing
     */
    protected function getEbayListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    protected function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //########################################

    protected function validateCategory()
    {
        if (!$this->getEbayListingProduct()->isSetCategoryTemplate()) {

            // M2ePro\TRANSLATIONS
            // Categories Settings are not set
            $this->addMessage('Categories Settings are not set');

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateQty()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return true;
        }

        $qty = $this->getQty();
        if ($qty <= 0) {

// M2ePro\TRANSLATIONS
// The Quantity must be greater than 0. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Quantity must be greater than 0. Please, check the Price, Quantity and Format
                Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('qty', $qty);

        return true;
    }

    // ---------------------------------------

    protected function validateIsVariationProductWithoutVariations()
    {
        if ($this->getEbayListingProduct()->isVariationMode() &&
            !$this->getEbayListingProduct()->isVariationsReady()) {
            // M2ePro_TRANSLATIONS
            // M2E Pro identifies this Product as a Variational one. But no Variations can be obtained from it. The problem could be related to the fact that Product Variations are not assigned to Magento Store View your M2E Pro Listing is created for. In order to be processed, the Product data should be available within Website that M2E Pro appeals to. Another possible reason is an impact of the external plugins. The 3rd party tools override Magento core functionality, therefore, prevent M2E Pro from processing the Product data correctly. Make sure you have selected an appropriate Website in each Associated Product and no 3rd party extension overrides your settings. Otherwise, contact M2E Pro Support Team to resolve the issue.
            $this->addMessage(
                'M2E Pro identifies this Product as a Variational one. But no Variations can be obtained from it.
                The problem could be related to the fact that Product Variations are not assigned to Magento Store
                View your M2E Pro Listing is created for. In order to be processed, the Product data should be
                available within Website that M2E Pro appeals to.
                Another possible reason is an impact of the external plugins. The 3rd party tools override
                Magento core functionality, therefore, prevent M2E Pro from processing the Product data correctly.
                Make sure you have selected an appropriate Website in each Associated Product and no 3rd party
                extension overrides your settings. Otherwise, contact M2E Pro Support Team to resolve the issue.'
            );

            return false;
        }

        return true;
    }

    protected function validateVariationsOptions()
    {
        $totalVariationsCount = 0;
        $totalDeletedVariationsCount = 0;
        $uniqueAttributesValues = array();

        foreach ($this->getEbayListingProduct()->getVariations(true) as $variation) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

            foreach ($variation->getOptions(true) as $option) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

                $uniqueAttributesValues[$option->getAttribute()][$option->getOption()] = true;

                // Max 5 pair attribute-option:
                // Color: Blue, Size: XL, ...
                if (count($uniqueAttributesValues) > 5) {

                    $this->addMessage(
                        'Variations of this Magento Product are out of the eBay Variational Items limits.
                        Its number of Variational Attributes is more than 5.
                        That is why, this Product cannot be updated on eBay.
                        Please, decrease the number of Attributes to solve this issue.'
                    );
                    return false;
                }

                // Maximum 60 options by one attribute:
                // Color: Red, Blue, Green, ...
                if (count($uniqueAttributesValues[$option->getAttribute()]) > 60) {

                    $this->addMessage(
                        'Variations of this Magento Product are out of the eBay Variational Items limits.
                        Its number of Options for some Variational Attribute(s) is more than 60.
                        That is why, this Product cannot be updated on eBay.
                        Please, decrease the number of Options to solve this issue.'
                    );
                    return false;
                }
            }

            $totalVariationsCount++;
            $variation->isDeleted() && $totalDeletedVariationsCount++;

            // Not more that 250 possible variations
            if ($totalVariationsCount > 250) {

                $this->addMessage(
                    'Variations of this Magento Product are out of the eBay Variational Items limits.
                    The Number of Variations is more than 250. That is why, this Product cannot be updated on eBay.
                    Please, decrease the number of Variations to solve this issue.'
                );
                return false;
            }
        }

        if ($totalVariationsCount == $totalDeletedVariationsCount) {

            $this->addMessage(
                'This Product was listed to eBay as Variational Item.
                Changing of the Item type from Variational to Non-Variational during Revise/Relist
                actions is restricted by eBay.
                At the moment this Product is considered as Simple without any Variations,
                that does not allow updating eBay Variational Item.'
            );
            return false;
        }

        return true;
    }

    protected function validateVariationsFixedPrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() ||
            !$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getEbayListingProduct()->isVariationsReady()
        ) {
            return true;
        }

        foreach ($this->getEbayListingProduct()->getVariations(true) as $variation) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

            if ($variation->getChildObject()->isDelete()) {
                continue;
            }

            if (isset($this->getData()['variation_fixed_price_'.$variation->getId()])) {
                $variationPrice = $this->getData()['variation_fixed_price_'.$variation->getId()];
            } else {
                $variationPrice = $variation->getChildObject()->getPrice();
            }

            if ($variationPrice < 0.99) {

// M2ePro_TRANSLATIONS
// The Price must be greater than 0.99. Please, check the Price, Quantity and Format Policy and Product Settings.
                $this->addMessage(
                    'The Fixed Price must be greater than 0.99.
                    Please, check the Price, Quantity and Format Policy and Product Settings.'
                );

                return false;
            }

            $this->setData('variation_fixed_price_'.$variation->getId(), $variationPrice);
        }

        return true;
    }

    protected function validateFixedPrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() ||
            !$this->getEbayListingProduct()->isListingTypeFixed() ||
            $this->getEbayListingProduct()->isVariationsReady()
        ) {
            return true;
        }

        $price = $this->getFixedPrice();
        if ($price < 0.99) {

// M2ePro\TRANSLATIONS
// The Price must be greater than 0.99. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Fixed Price must be greater than 0.99.
                Please, check the Price, Quantity and Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('price_fixed', $price);

        return true;
    }

    protected function validateStartPrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() || !$this->getEbayListingProduct()->isListingTypeAuction()) {
            return true;
        }

        $price = $this->getStartPrice();
        if ($price < 0.99) {

// M2ePro\TRANSLATIONS
// The Price must be greater than 0.99. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Start Price must be greater than 0.99.
                 Please, check the Price, Quantity and Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('price_start', $price);

        return true;
    }

    protected function validateReservePrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() || !$this->getEbayListingProduct()->isListingTypeAuction()) {
            return true;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isReservePriceModeNone()) {
            return true;
        }

        $price = $this->getReservePrice();
        if ($price < 0.99) {

// M2ePro\TRANSLATIONS
// The Price must be greater than 0.99. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Reserve Price must be greater than 0.99.
                 Please, check the Price, Quantity and Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('price_reserve', $price);

        return true;
    }

    protected function validateBuyItNowPrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() || !$this->getEbayListingProduct()->isListingTypeAuction()) {
            return true;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isBuyItNowPriceModeNone()) {
            return true;
        }

        $price = $this->getBuyItNowPrice();
        if ($price < 0.99) {

// M2ePro\TRANSLATIONS
// The Price must be greater than 0. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Buy It Now Price must be greater than 0.99.
                 Please, check the Price, Quantity and Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('price_buyitnow', $price);

        return true;
    }

    //########################################

    protected function getQty()
    {
        if (isset($this->getData()['qty'])) {
            return $this->getData()['qty'];
        }

        return $this->getEbayListingProduct()->getQty();
    }

    protected function getFixedPrice()
    {
        if (isset($this->getData()['price_fixed'])) {
            return $this->getData()['price_fixed'];
        }

        return $this->getEbayListingProduct()->getFixedPrice();
    }

    protected function getStartPrice()
    {
        if (!empty($this->getData()['price_start'])) {
            return $this->getData()['price_start'];
        }

        return $this->getEbayListingProduct()->getStartPrice();
    }

    protected function getReservePrice()
    {
        if (!empty($this->getData()['price_reserve'])) {
            return $this->getData()['price_reserve'];
        }

        return $this->getEbayListingProduct()->getReservePrice();
    }

    protected function getBuyItNowPrice()
    {
        if (!empty($this->getData()['price_buyitnow'])) {
            return $this->getData()['price_buyitnow'];
        }

        return $this->getEbayListingProduct()->getBuyItNowPrice();
    }

    //########################################
}