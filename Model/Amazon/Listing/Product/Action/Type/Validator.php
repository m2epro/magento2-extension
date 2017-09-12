<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

abstract class Validator extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
    private $configurator = NULL;

    /**
     * @var array
     */
    private $messages = array();

    protected $activeRecordFactory;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

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
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator
     * @return $this
     */
    public function setConfigurator(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator)
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        $this->getAmazonAccount()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    protected function getAmazonMarketplace()
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
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    protected function getAmazonAccount()
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
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    protected function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    protected function getAmazonListingProduct()
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

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
     */
    protected function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    //########################################

    abstract public function validate();

    protected function addMessage($message, $type =\Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR)
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

    protected function validateSku()
    {
        if (!$this->getAmazonListingProduct()->getSku()) {

            // M2ePro\TRANSLATIONS
            // You have to list Item first.
            $this->addMessage('You have to list Item first.');

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateBlocked()
    {
        if ($this->getListingProduct()->isBlocked()) {

// M2ePro\TRANSLATIONS
// The Action can not be executed as the Item was Closed, Incomplete or Blocked on Amazon. Please, go to Amazon Seller Central and activate the Item. After the next Synchronization the Item will be available.
            $this->addMessage(
                'The Action can not be executed as the Item was Closed, Incomplete or Blocked on Amazon.
                 Please, go to Amazon Seller Central and activate the Item.
                 After the next Synchronization the Item will be available.'
            );

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
                'The Quantity must be greater than 0. Please, check the Price, Quantity and
                Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('qty', $qty);

        return true;
    }

    protected function validateRegularPrice()
    {
        if (!$this->getConfigurator()->isRegularPriceAllowed()) {
            return true;
        }

        if (!$this->getAmazonListingProduct()->isAllowedForRegularCustomers()) {
            $this->getConfigurator()->disallowRegularPrice();

            if ($this->getAmazonListingProduct()->getOnlineRegularPrice()) {
                $this->addMessage(
                    'B2C Price can not be disabled by Revise/Relist action due to Amazon restrictions.
                    Both B2C and B2B Price values will be available on the Channel.',
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING);
            }

            return true;
        }

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $this->getAmazonListingProduct()->isRepricingEnabled()
        ) {

            $this->getConfigurator()->disallowRegularPrice();

            $this->addMessage(
                'This product is used by Amazon Repricing Tool.
                 The Price cannot be updated through the M2E Pro.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            return true;
        }

        $regularPrice = $this->getPrice();
        if ($regularPrice <= 0) {

            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0. Please, check the Price, Quantity and Format Policy and Product Settings.
            $this->addMessage(
                'The Price must be greater than 0. Please, check the Price, Quantity and
                Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('regular_price', $regularPrice);

        return true;
    }

    protected function validateBusinessPrice()
    {
        if (!$this->getConfigurator()->isBusinessPriceAllowed()) {
            return true;
        }

        if (!$this->getAmazonListingProduct()->isAllowedForBusinessCustomers()) {
            $this->getConfigurator()->disallowBusinessPrice();

            if ($this->getAmazonListingProduct()->getOnlineBusinessPrice()) {
                $this->addMessage(
                    'B2B Price can not be disabled by Revise/Relist action due to Amazon restrictions.
                    Both B2B and B2C Price values will be available on the Channel.',
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING);
            }

            return true;
        }

        $businessPrice = $this->getBusinessPrice();
        if ($businessPrice <= 0) {

            // M2ePro_TRANSLATIONS
            // The Price must be greater than 0. Please, check the Selling Format Policy and Product Settings.
            $this->addMessage(
                'The Business Price must be greater than 0.
                Please, check the Selling Format Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('business_price', $businessPrice);

        return true;
    }

    // ---------------------------------------

    protected function validateLogicalUnit()
    {
        if (!$this->getVariationManager()->isLogicalUnit()) {

            // M2ePro\TRANSLATIONS
            // Only logical Products can be processed.
            $this->addMessage('Only logical Products can be processed.');

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateParentListingProductFlags()
    {
        if ($this->getListingProduct()->getData('no_child_for_processing')) {
// M2ePro\TRANSLATIONS
// This Parent has no Child Products on which the chosen Action can be performed.
            $this->addMessage('This Parent has no Child Products on which the chosen Action can be performed.');
            return false;
        }
// M2ePro\TRANSLATIONS
// This Action cannot be fully performed because there are different actions in progress on some Child Products
        if ($this->getListingProduct()->getData('child_locked')) {
            $this->addMessage('This Action cannot be fully performed because there are
                                different Actions in progress on some Child Products');
            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validatePhysicalUnitAndSimple()
    {
        if (!$this->getVariationManager()->isPhysicalUnit() && !$this->getVariationManager()->isSimpleType()) {

            // M2ePro\TRANSLATIONS
            // Only physical Products can be processed.
            $this->addMessage('Only physical Products can be processed.');

            return false;
        }

        return true;
    }

    protected function validatePhysicalUnitMatching()
    {
        if (!$this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            // M2ePro\TRANSLATIONS
            // You have to select Magento Variation.
            $this->addMessage('You have to select Magento Variation.');

            return false;
        }

        if ($this->getVariationManager()->isIndividualType()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        if (!$this->getAmazonListingProduct()->isGeneralIdOwner() && !$typeModel->isVariationChannelMatched()) {

            // M2ePro\TRANSLATIONS
            // You have to select Channel Variation.
            $this->addMessage('You have to select Channel Variation.');

            return false;
        }

        return true;
    }

    //########################################

    protected function getPrice()
    {
        if (isset($this->getData()['regular_price'])) {
            return $this->getData('regular_price');
        }

        return $this->getAmazonListingProduct()->getRegularPrice();
    }

    protected function getBusinessPrice()
    {
        if (isset($this->getData()['business_price'])) {
            return $this->getData('business_price');
        }

        return $this->getAmazonListingProduct()->getBusinessPrice();
    }

    protected function getQty()
    {
        if (isset($this->getData()['qty'])) {
            return $this->getData('qty');
        }

        return $this->getAmazonListingProduct()->getQty();
    }

    //########################################
}