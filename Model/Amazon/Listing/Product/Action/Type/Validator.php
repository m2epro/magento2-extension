<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Tag\ValidatorIssues;

abstract class Validator extends \Ess\M2ePro\Model\AbstractModel
{
    public const ERROR_ITEM_BLOCKED_ON_AMAZON = '0001-m2e';
    public const ERROR_CODE_ZERO_PRICE = '0004-m2e';
    public const ERROR_CODE_ZERO_BUSINESS_PRICE = '0007-m2e';
    public const ERROR_PARENT_HAS_NO_CHILD = '0008-m2e';
    public const ERROR_DIFFERENT_ACTIONS_CHILD_PRODUCTS = '0009-m2e';
    public const ERROR_NOT_PHYSICAL_PRODUCT = '0010-m2e';
    public const VARIATION_MAGENTO_NOT_SELECTED = '0011-m2e';
    public const VARIATION_CHANNEL_NOT_SELECTED = '0012-m2e';
    public const ERROR_REQUIRE_MANUAL_ASIN_SEARCH = '0013-m2e';
    public const ERROR_INVALID_GENERAL_ID = '0014-m2e';
    public const ERROR_GENERAL_ID_NOT_FOUND = '0015-m2e';
    public const ERROR_MULTIPLE_PRODUCTS_FOUND_GENERAL_ID = '0016-m2e';
    public const ERROR_PARENT_FOUND_CHILD_EXPECTED_GENERAL_ID = '0017-m2e';
    public const ERROR_AMAZON_RESTRICTIONS_BY_GENERAL_ID = '0018-m2e';
    public const ERROR_VARIATION_ATTRIBUTES_MISMATCH_GENERAL_ID = '0019-m2e';
    public const ERROR_CHILD_FOUND_PARENT_EXPECTED = '0020-m2e';
    public const ERROR_INVALID_WORLDWIDE_ID = '0021-m2e';
    public const ERROR_WORLDWIDE_ID_NOT_FOUND = '0022-m2e';
    public const ERROR_MULTIPLE_PRODUCTS_FOUND_WORLDWIDE_ID = '0023-m2e';
    public const ERROR_PARENT_FOUND_CHILD_EXPECTED_WORLDWIDE_ID = '0024-m2e';
    public const ERROR_NOT_CHILD = '0028-m2e';
    public const ERROR_CHILD_RELATED_TO_ANOTHER_PARENT = '0029-m2e';
    public const ERROR_NO_CHILD_WITH_VARIATION_MATCH = '0030-m2e';
    public const ERROR_IDENTIFIER_MISSING = '0032-m2e';
    public const ERROR_PRODUCT_TYPE_MISSING = '0033-m2e';
    public const ERROR_VARIATION_THEME_MISSING = '0034-m2e';
    public const ERROR_INVALID_IDENTIFIER = '0035-m2e';
    public const ERROR_CREATE_IDENTIFIER_FAILED = '0036-m2e';
    public const ERROR_FBA_ITEM_LIST = '0037-m2e';
    public const PRODUCT_TYPE_INVALID = '0038-m2e';
    public const ITEM_CONDITION_NOT_SPECIFIED = '0039-m2e';
    public const PARENT_NOT_LINKED = '0040-m2e';
    public const PRODUCT_MISSING_LINK_OR_NEW_IDENTIFIER = '0041-m2e';
    public const ERROR_SKU_ALREADY_PROCESSING = '0043-m2e';
    public const ERROR_DUPLICATE_SKU_LISTING = '0044-m2e';
    public const ERROR_DUPLICATE_SKU_UNMANAGED = '0045-m2e';
    public const ERROR_SKU_MISSING = '0046-m2e';
    public const ERROR_SKU_LENGTH_EXCEEDED = '0047-m2e';
    public const ERROR_SKU_ASSIGN_PARENT_CHILD_EXPECTED = '0049-m2e';
    public const ERROR_SKU_ASSIGNED_TO_DIFFERENT_ASIN = '0050-m2e';
    public const ERROR_SKU_ASSIGN_CHILD_PARENT_EXPECTED = '0051-m2e';
    public const ERROR_PARENT_LISTING_API_RESTRICTION = '0052-m2e';
    public const ERROR_VARIATION_ATTRIBUTES_MISMATCH_BY_SKU = '0053-m2e';
    public const ERROR_NOT_CHILD_BY_SKU = '0054-m2e';
    public const ERROR_CHILD_PARENT_API_RESTRICTION = '0055-m2e';
    public const ERROR_SKU_RELATED_TO_ANOTHER_PARENT_IDENTIFIER = '0056-m2e';
    public const ERROR_SKU_RELATED_TO_ANOTHER_IDENTIFIER = '0057-m2e';
    public const ERROR_NO_CHILD_WITH_ATTRIBUTES_MATCH = '0058-m2e';
    public const ERROR_IDENTIFIER_ALREADY_USED_FOR_ANOTHER_PRODUCT = '0059-m2e';
    public const ERROR_FBA_ITEM_RELIST = '0060-m2e';
    public const ERROR_CANNOT_SWITCH_FULFILLMENT_NO_QTY_FEED = '0061-m2e';
    public const FULFILLMENT_ALREADY_APPLIED = '0062-m2e';
    public const ERROR_FBA_ITEM_STOP = '0063-m2e';

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
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;
    private \Ess\M2ePro\Model\Connector\Connection\Response\MessageFactory $messageFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response\MessageFactory $messageFactory
    ) {
        $this->messageFactory = $messageFactory;
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

    protected function isChangerUser(): bool
    {
        $params = $this->getParams();
        if (!array_key_exists('status_changer', $params)) {
            return false;
        }

        return (int)$params['status_changer'] === \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
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
     *
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

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    protected function addMessage(string $text, ?string $errorCode = null, string $type = Message::TYPE_ERROR): void
    {
        $message = $this->messageFactory->create();
        $message->initFromPreparedData(
            $text,
            $type,
            Message::SENDER_COMPONENT,
            $errorCode
        );

        $this->messages[] = $message;
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
            $this->addMessage(
                'You have to list Item first.',
                ValidatorIssues::NOT_USER_ERROR
            );

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateBlocked()
    {
        if ($this->isChangerUser()) {
            return true;
        }

        if ($this->getListingProduct()->isBlocked()) {
            $this->addMessage(
                'The Action can not be executed as the Item was Closed, Incomplete or Blocked on Amazon.
                 Please, go to Amazon Seller Central and activate the Item.
                 After the next Synchronization the Item will be available.',
                self::ERROR_ITEM_BLOCKED_ON_AMAZON
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

        return $this->forceValidateQty();
    }

    protected function forceValidateQty(): bool
    {
        $qty = $this->getQty();
        $clearQty = $this->getClearQty();

        if ($clearQty > 0 && $qty <= 0) {
            $message = 'You’re submitting an item with QTY contradicting the QTY settings in your Selling Policy.
            Please check Minimum Quantity to Be Listed and Quantity Percentage options.';

            $this->addMessage($message, ValidatorIssues::ERROR_QUANTITY_POLICY_CONTRADICTION);

            return false;
        }

        if ($qty <= 0) {
            if (
                isset($this->params['status_changer']) &&
                $this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER
            ) {
                $message = 'You are submitting an Item with zero quantity. It contradicts Amazon requirements.';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= ' Please apply the Stop Action instead.';
                }

                $this->addMessage($message, ValidatorIssues::ERROR_CODE_ZERO_QTY);
            } else {
                $message = 'Cannot submit an Item with zero quantity. It contradicts Amazon requirements.
                            This action has been generated automatically based on your Synchronization Rule settings. ';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= 'The error occurs when the Stop Rules are not properly configured or disabled. ';
                }

                $message .= 'Please review your settings.';

                $this->addMessage($message, ValidatorIssues::ERROR_CODE_ZERO_QTY);
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
                    null,
                    Message::TYPE_WARNING
                );
            }

            return true;
        }

        if ($this->getAmazonListingProduct()->isRepricingManaged()) {
            $this->getConfigurator()->disallowRegularPrice();

            $this->addMessage(
                'Price of this Product is managed by Amazon Repricer, it isn\'t updated by M2E Pro.',
                null,
                Message::TYPE_NOTICE
            );

            return true;
        }

        $regularPrice = $this->getRegularPrice();
        if ($regularPrice <= 0) {
            $this->addMessage(
                'The Price must be greater than 0. Please, check the Selling Policy and Product Settings.',
                self::ERROR_CODE_ZERO_PRICE
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

        $isAllowedBusinessCustomers = $this
            ->getAmazonListingProduct()
            ->isAllowedForBusinessCustomers();

        $onlineBusinessPrice = $this
            ->getAmazonListingProduct()
            ->getOnlineBusinessPrice();

        if ($isAllowedBusinessCustomers === false && $onlineBusinessPrice > 0) {
            $this->getConfigurator()->disallowBusinessPrice();
            $this->setData('delete_business_price_flag', true);

            return true;
        }

        if ($isAllowedBusinessCustomers === false) {
            $this->getConfigurator()->disallowBusinessPrice();

            return true;
        }

        $businessPrice = $this->getBusinessPrice();
        if ($businessPrice <= 0) {
            $this->addMessage(
                'The Business Price must be greater than 0. Please, check the Selling Policy and Product Settings.',
                self::ERROR_CODE_ZERO_BUSINESS_PRICE
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
            $this->addMessage(
                'This Parent has no Child Products on which the chosen Action can be performed.',
                self::ERROR_PARENT_HAS_NO_CHILD
            );

            return false;
        }
        if ($this->getListingProduct()->getData('child_locked')) {
            $this->addMessage(
                'This Action cannot be fully performed because there are
                                different Actions in progress on some Child Products',
                self::ERROR_DIFFERENT_ACTIONS_CHILD_PRODUCTS
            );

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validatePhysicalUnitAndSimple()
    {
        if (!$this->getVariationManager()->isPhysicalUnit() && !$this->getVariationManager()->isSimpleType()) {
            $this->addMessage('Only physical Products can be processed.', self::ERROR_NOT_PHYSICAL_PRODUCT);

            return false;
        }

        return true;
    }

    protected function validatePhysicalUnitMatching()
    {
        if (!$this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {
            $this->addMessage('You have to select Magento Variation.', self::VARIATION_MAGENTO_NOT_SELECTED);

            return false;
        }

        if ($this->getVariationManager()->isIndividualType()) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        if (!$this->getAmazonListingProduct()->isGeneralIdOwner() && !$typeModel->isVariationChannelMatched()) {
            $this->addMessage('You have to select Channel Variation.', self::VARIATION_CHANNEL_NOT_SELECTED);

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
}
