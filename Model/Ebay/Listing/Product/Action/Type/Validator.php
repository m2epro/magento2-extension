<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator;
use Ess\M2ePro\Model\Factory;

abstract class Validator extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var array */
    protected $params = [];
    /** @var Configurator $configurator */
    protected $configurator = null;
    /** @var array */
    protected $messages = [];
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        Factory $modelFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\CollectionFactory $variationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option $variationOptionResource,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->variationCollectionFactory = $variationCollectionFactory;
        $this->variationOptionResource = $variationOptionResource;
    }

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
     *
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
     *
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
        $this->messages[] = [
            'text' => $message,
            'type' => $type,
        ];
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
            $this->addMessage('Categories Settings are not set');

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validatePrice()
    {
        if ($this->getEbayListingProduct()->isVariationsReady()) {
            if (!$this->validateVariationsFixedPrice()) {
                return false;
            }

            return true;
        }

        if ($this->getEbayListingProduct()->isListingTypeAuction()) {
            if (!$this->validateStartPrice()) {
                return false;
            }

            if (!$this->validateReservePrice()) {
                return false;
            }

            if (!$this->validateBuyItNowPrice()) {
                return false;
            }

            return true;
        } else {
            if (!$this->validateFixedPrice()) {
                return false;
            }
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
        $clearQty = $this->getClearQty();

        if ($clearQty > 0 && $qty <= 0) {
            $message = 'You’re submitting an item with QTY contradicting the QTY settings in your Selling Policy.
            Please check Minimum Quantity to Be Listed and Quantity Percentage options.';

            $this->addMessage($message);

            return false;
        }

        if ($qty <= 0) {
            if (
                isset($this->params['status_changer']) &&
                $this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER
            ) {
                $message = 'You are submitting an Item with zero quantity. It contradicts eBay requirements.';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= ' Please apply the Stop Action instead.';
                }

                $this->addMessage($message);
            } else {
                $message = 'Cannot submit an Item with zero quantity. It contradicts eBay requirements.
                            This action has been generated automatically based on your Synchronization Rule settings. ';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= 'The error occurs when the Stop Rules are not properly configured or disabled. ';
                }

                $message .= 'Please review your settings.';

                $this->addMessage($message);
            }

            return false;
        }

        $this->setData('qty', $qty);
        $this->setData('clear_qty', $clearQty);

        return true;
    }

    // ---------------------------------------

    protected function validateIsVariationProductWithoutVariations()
    {
        if (
            $this->getEbayListingProduct()->isVariationMode() &&
            !$this->getEbayListingProduct()->isVariationsReady()
        ) {
            $supportLink = 'https://help.m2epro.com/support/solutions/articles/9000223366';
            $msg = "Unable to list the product(s) because product variations are assigned incorrectly
                or missing for the selected Store View. <a href=\"$supportLink\" target=\"_blank\">Learn more...</a>";
            $this->addMessage($msg);

            return false;
        }

        return true;
    }

    protected function validateVariationsOptions(): bool
    {
        $totalVariationsCount = 0;
        $totalVariationsCountWithoutDeleted = 0;
        $totalDeletedVariationsCount = 0;
        $uniqueAttributesValues = [];

        /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
        foreach ($this->getEbayListingProduct()->getVariations(true) as $variation) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */
            foreach ($variation->getOptions(true) as $option) {
                if (!$ebayVariation->isDelete()) {
                    $uniqueAttributesValues[$option->getAttribute()][$option->getOption()] = true;
                }

                // Max 5 pair attribute-option:
                // Color: Blue, Size: XL, ...
                if (count($uniqueAttributesValues) > 5) {
                    $this->addMessage(
                        'Variations of this Magento Product are out of the eBay Variational Item limits.
                        Its number of Variational Attributes is more than 5.
                        That is why, this Product cannot be updated on eBay.
                        Please, decrease the number of Attributes to solve this issue.'
                    );

                    return false;
                }

                // Maximum 60 options by one attribute:
                // Color: Red, Blue, Green, ...
                if (
                    isset($uniqueAttributesValues[$option->getAttribute()])
                    && count($uniqueAttributesValues[$option->getAttribute()]) > 60
                ) {
                    $this->addMessage(
                        'Variations of this Magento Product are out of the eBay Variational Item limits.
                        Its number of Options for some Variational Attribute(s) is more than 60.
                        That is why, this Product cannot be updated on eBay.
                        Please, decrease the number of Options to solve this issue.'
                    );

                    return false;
                }
            }

            $totalVariationsCount++;
            if ($ebayVariation->isDelete()) {
                $totalDeletedVariationsCount++;
            } else {
                $totalVariationsCountWithoutDeleted++;
            }

            // Not more than 250 possible variations
            if ($totalVariationsCountWithoutDeleted > 250) {
                $this->addMessage(
                    'Variations of this Magento Product are out of the eBay Variational Item limits.
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
        if (
            !$this->getConfigurator()->isPriceAllowed() ||
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

            if (isset($this->getData()['variation_fixed_price_' . $variation->getId()])) {
                $variationPrice = $this->getData()['variation_fixed_price_' . $variation->getId()];
            } else {
                $variationPrice = $variation->getChildObject()->getPrice();
            }

            if ($variationPrice < 0.99) {
                $this->addMessage(
                    'The Fixed Price must be greater than 0.99. Please, check the Selling Policy and Product Settings.'
                );

                return false;
            }

            $this->setData('variation_fixed_price_' . $variation->getId(), $variationPrice);
        }

        return true;
    }

    protected function validateFixedPrice()
    {
        if (
            !$this->getConfigurator()->isPriceAllowed() ||
            !$this->getEbayListingProduct()->isListingTypeFixed() ||
            $this->getEbayListingProduct()->isVariationsReady()
        ) {
            return true;
        }

        $price = $this->getFixedPrice();
        if ($price < 0.99) {
            $this->addMessage(
                'The Fixed Price must be greater than 0.99. Please, check the Selling Policy and Product Settings.'
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
            $this->addMessage(
                'The Start Price must be greater than 0.99. Please, check the Selling Policy and Product Settings.'
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
            $this->addMessage(
                'The Reserve Price must be greater than 0.99. Please, check the Selling Policy and Product Settings.'
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
            $this->addMessage(
                'The Buy It Now Price must be greater than 0.99.
                 Please, check the Selling Policy and Product Settings.'
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

    protected function getClearQty()
    {
        if (isset($this->getData()['clear_qty'])) {
            return $this->getData()['clear_qty'];
        }

        return $this->getEbayListingProduct()->getQty(true);
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
