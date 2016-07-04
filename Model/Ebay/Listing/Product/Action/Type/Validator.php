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
            // The Quantity must be greater than 0. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Quantity must be greater than 0. Please, check the Selling Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('qty', $qty);

        return true;
    }

    // ---------------------------------------

    protected function validateVariationsFixedPrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed() ||
            !$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getEbayListingProduct()->isVariationsReady()
        ) {
            return true;
        }

        $variations = $this->getEbayListingProduct()->getVariations(true);
        foreach ($variations as $variation) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

            if ($variation->getChildObject()->isDelete()) {
                continue;
            }

            if (isset($this->getData()['variation_fixed_price_'.$variation->getId()])) {
                $variationPrice = $this->getData()['variation_fixed_price_'.$variation->getId()];
            } else {
                $variationPrice = $variation->getChildObject()->getPrice();
            }

            if ($variationPrice < 1) {

                // M2ePro_TRANSLATIONS
                // The Price must be greater than 0.99. Please, check the Selling Format Policy and Product Settings.
                $this->addMessage(
                    'The Fixed Price must be greater than 0.99.
                    Please, check the Selling Format Policy and Product Settings.'
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
        if ($price < 1) {

            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0.99. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Fixed Price must be greater than 0.99.
                Please, check the Selling Format Policy and Product Settings.'
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
        if ($price < 1) {

            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0.99. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Start Price must be greater than 0.99.
                 Please, check the Selling Format Policy and Product Settings.'
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

        $price = $this->getReservePrice();
        if ($price < 1) {

            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0.99. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Reserve Price must be greater than 0.99.
                 Please, check the Selling Format Policy and Product Settings.'
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

        $price = $this->getBuyItNowPrice();
        if ($price < 0) {

            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Buy It Now Price must be greater than 0.99.
                 Please, check the Selling Format Policy and Product Settings.'
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