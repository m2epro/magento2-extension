<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\SkuResolver
 */
class SkuResolver extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct = null;

    private $skusInProcessing = null;

    private $skusInCurrentRequest = [];

    /** @var \Ess\M2ePro\Model\Response\Message[] */
    private $messages = [];

    protected $walmartFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function setSkusInCurrentRequest(array $skus)
    {
        $this->skusInCurrentRequest = $skus;
        return $this;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Response\Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    //########################################

    public function resolve()
    {
        $sku = $this->getSku();

        if (empty($sku)) {
            // M2ePro\TRANSLATIONS
            // SKU is not provided. Please, check Listing Settings.
            $this->addMessage('SKU is not provided. Please, check Listing Settings.');
            return null;
        }

        $generateSkuMode = $this->getHelper('Component_Walmart_Configuration')->isGenerateSkuModeYes();

        if (!$this->isExistInM2ePro($sku, !$generateSkuMode)) {
            return $sku;
        }

        if (!$generateSkuMode) {
            return null;
        }

        $unifiedSku = $this->getUnifiedSku($sku);
        if ($this->checkSkuRequirements($unifiedSku)) {
            return $unifiedSku;
        }

        $unifiedSku = $this->getUnifiedSku();
        if ($this->checkSkuRequirements($unifiedSku)) {
            return $unifiedSku;
        }

        return $this->getRandomSku();
    }

    //########################################

    private function getUnifiedSku($prefix = 'SKU')
    {
        return $prefix . '_' . $this->getListingProduct()->getProductId() . '_' . $this->getListingProduct()->getId();
    }

    private function getRandomSku()
    {
        $hash = sha1(rand(0, 10000) . microtime(1));
        return $this->getUnifiedSku() . '_' . substr($hash, 0, 10);
    }

    //########################################

    private function checkSkuRequirements($sku)
    {
        if (strlen($sku) > \Ess\M2ePro\Helper\Component\Walmart::SKU_MAX_LENGTH) {
            return false;
        }

        if ($this->isExistInM2ePro($sku, false)) {
            return false;
        }

        return true;
    }

    //########################################

    private function isExistInM2ePro($sku, $addMessages = false)
    {
        if ($this->isAlreadyInCurrentRequest($sku) || $this->isAlreadyInProcessing($sku)) {
            $addMessages && $this->addMessage(
                'Another Product with the same SKU is being Listed simultaneously with this one.
                Please change the SKU or enable the Option Generate Merchant SKU.'
            );
            return true;
        }

        if ($this->isExistInM2eProListings($sku)) {
            $addMessages && $this->addMessage(
                'Product with the same SKU is found in other M2E Pro Listing that is created
                 from the same Merchant ID for the same Marketplace.'
            );
            return true;
        }

        if ($this->isExistInOtherListings($sku)) {
            $addMessages && $this->addMessage(
                'Product with the same SKU is found in M2E Pro 3rd Party Listing.
                Please change the SKU or enable the Option Generate Merchant SKU.'
            );
            return true;
        }

        return false;
    }

    // ---------------------------------------

    private function isAlreadyInCurrentRequest($sku)
    {
        return in_array($sku, $this->skusInCurrentRequest);
    }

    private function isAlreadyInProcessing($sku)
    {
        return in_array($sku, $this->getSkusInProcessing());
    }

    private function isExistInM2eProListings($sku)
    {
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(
            ['l' => $listingTable],
            '`main_table`.`listing_id` = `l`.`id`',
            []
        );

        $collection->addFieldToFilter('sku', $sku);
        $collection->addFieldToFilter('main_table.id', ['neq' => $this->listingProduct->getId()]);
        $collection->addFieldToFilter('l.account_id', $this->getListingProduct()->getAccount()->getId());

        return $collection->getSize() > 0;
    }

    private function isExistInOtherListings($sku)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();

        $collection->addFieldToFilter('sku', $sku);
        $collection->addFieldToFilter('account_id', $this->getListingProduct()->getAccount()->getId());

        return $collection->getSize() > 0;
    }

    //########################################

    private function getSkusInProcessing()
    {
        if ($this->skusInProcessing !== null) {
            return $this->skusInProcessing;
        }

        $processingActionListSkuCollection = $this->activeRecordFactory
            ->getObject('Walmart_Processing_Action_ListAction_Sku')
            ->getCollection();
        $processingActionListSkuCollection->addFieldToFilter('account_id', $this->getListingProduct()
                                                                                ->getListing()
                                                                                ->getAccountId());

        return $this->skusInProcessing = $processingActionListSkuCollection->getColumnValues('sku');
    }

    private function getSku()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getListingProduct()->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getListingProduct()->getId()
                    ]
                );
            }

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);
            $sku = $variation->getChildObject()->getSku();

            if (!empty($sku)) {
                $sku = $this->applySkuModification($sku);
                $sku = $this->removeUnsupportedCharacters($sku);
            }

            /**
             * Only Product Variations created based on Magento Configurable or Grouped Product types can be sold on
             * the Walmart website. So SKU will be taken directly from a Child product and it makes no sense
             * on doing it random.
             */

            //if (strlen($sku) >= \Ess\M2ePro\Helper\Component\Walmart::SKU_MAX_LENGTH) {
            //    $sku = $this->getHelper('Data')->hashString($sku, 'md5', 'RANDOM_');
            //}

            return $sku;
        }

        $helper = $this->getHelper('Component_Walmart_Configuration');

        $sku = '';

        if ($helper->isSkuModeDefault()) {
            $sku = $this->getMagentoProduct()->getSku();
        }

        if ($helper->isSkuModeProductId()) {
            $sku = $this->getMagentoProduct()->getProductId();
        }

        if ($helper->isSkuModeCustomAttribute()) {
            $sku = $this->getMagentoProduct()->getAttributeValue($helper->getSkuCustomAttribute());
        }

        is_string($sku) && $sku = trim($sku);

        if (!empty($sku)) {
            $sku = $this->applySkuModification($sku);
            $sku = $this->removeUnsupportedCharacters($sku);
        }

        return $sku;
    }

    //########################################

    private function applySkuModification($sku)
    {
        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isSkuModificationModeNone()) {
            return $sku;
        }

        if ($helper->isSkuModificationModePrefix()) {
            $sku = $helper->getSkuModificationCustomValue() . $sku;
        } elseif ($helper->isSkuModificationModePostfix()) {
            $sku = $sku . $helper->getSkuModificationCustomValue();
        } elseif ($helper->isSkuModificationModeTemplate()) {
            $sku = str_replace('%value%', $sku, $helper->getSkuModificationCustomValue());
        }

        return $sku;
    }

    private function removeUnsupportedCharacters($sku)
    {
        if (!preg_match('/[.\s-]/', $sku)) {
            return $sku;
        }

        $newSku = preg_replace('/[.\s-]/', '_', $sku);
        $this->addMessage(
            sprintf(
                'The Item SKU will be automatically changed to "%s".
                Special characters, i.e. hyphen (-), space ( ), and period (.), are not allowed by Walmart and
                will be replaced with the underscore ( _ ).
                The Item will remain associated with Magento Product "%s".',
                $newSku,
                $sku
            ),
            \Ess\M2ePro\Model\Response\Message::TYPE_WARNING
        );

        return $newSku;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    private function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getVariationManager()
    {
        return $this->getWalmartListingProduct()->getVariationManager();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    private function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //########################################

    private function addMessage($text, $type = \Ess\M2ePro\Model\Response\Message::TYPE_ERROR)
    {
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData($text, $type);

        $this->messages[] = $message;
    }

    //########################################
}
