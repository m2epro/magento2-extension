<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
 */
abstract class Validator extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
    private $configurator = null;

    /**
     * @var array
     */
    private $messages = [];

    protected $activeRecordFactory;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
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

    protected function addMessage($message, $type = \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR)
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

    protected function validateSku()
    {
        if (!$this->getAmazonListingProduct()->getSku()) {
            $this->addMessage('You have to list Item first.');
            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateBlocked()
    {
        if ($this->getListingProduct()->isBlocked()) {
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
        $clearQty = $this->getClearQty();

        if ($clearQty > 0 && $qty <= 0) {
            $message = 'You’re submitting an item with QTY contradicting the QTY settings in your Selling Policy. 
            Please check Minimum Quantity to Be Listed and Quantity Percentage options.';

            $this->addMessage($message);

            return false;
        }

        if ($qty <= 0) {
            if (isset($this->params['status_changer']) &&
                $this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
                $message = 'You are submitting an Item with zero quantity. It contradicts Amazon requirements.';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= ' Please apply the Stop Action instead.';
                }

                $this->addMessage($message);
            } else {
                $message = 'Cannot submit an Item with zero quantity. It contradicts Amazon requirements.
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
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            }

            return true;
        }

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            $this->getAmazonListingProduct()->isRepricingManaged()
        ) {
            $this->getConfigurator()->disallowRegularPrice();

            $this->addMessage(
                'This product is used by Amazon Repricing Tool.
                 The Price cannot be updated through the M2E Pro.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            return true;
        }

        $regularPrice = $this->getRegularPrice();
        if ($regularPrice <= 0) {
            $this->addMessage(
                'The Price must be greater than 0. Please, check the Selling Policy and Product Settings.'
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
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            }

            return true;
        }

        $businessPrice = $this->getBusinessPrice();
        if ($businessPrice <= 0) {
            $this->addMessage(
                'The Business Price must be greater than 0. Please, check the Selling Policy and Product Settings.'
            );

            return false;
        }

        $this->setData('business_price', $businessPrice);

        return true;
    }

    // ---------------------------------------

    protected function validateParentListingProduct()
    {
        if ($this->getListingProduct()->getData('no_child_for_processing')) {
            $this->addMessage('This Parent has no Child Products on which the chosen Action can be performed.');
            return false;
        }
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
            $this->addMessage('Only physical Products can be processed.');
            return false;
        }

        return true;
    }

    protected function validatePhysicalUnitMatching()
    {
        if (!$this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $this->addMessage('You have to select Magento Variation.');
            return false;
        }

        if ($this->getVariationManager()->isIndividualType()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        if (!$this->getAmazonListingProduct()->isGeneralIdOwner() && !$typeModel->isVariationChannelMatched()) {
            $this->addMessage('You have to select Channel Variation.');
            return false;
        }

        return true;
    }

    //########################################

    protected function getRegularPrice()
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

    protected function getClearQty()
    {
        if (isset($this->getData()['clear_qty'])) {
            return $this->getData('clear_qty');
        }

        return $this->getAmazonListingProduct()->getQty(true);
    }

    //########################################
}
