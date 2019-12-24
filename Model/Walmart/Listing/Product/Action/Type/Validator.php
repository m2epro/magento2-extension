<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type;

use Ess\M2ePro\Helper\Component\Walmart\Configuration as ConfigurationHelper;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator
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

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
    private $configurator = null;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var array
     */
    protected $data = [];

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
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator
     * @return $this
     */
    public function setConfigurator(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator)
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
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
        return $this->getWalmartAccount()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    protected function getWalmartMarketplace()
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
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    protected function getWalmartAccount()
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
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    protected function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    protected function getWalmartListingProduct()
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
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    protected function getVariationManager()
    {
        return $this->getWalmartListingProduct()->getVariationManager();
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

    // ---------------------------------------

    /**
     * @param $key
     * @return array
     */
    public function getValidatorData($key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setValidatorData($data)
    {
        $this->data = $data;
        return $this;
    }

    //########################################

    protected function validateSku()
    {
        if (!$this->getWalmartListingProduct()->getSku()) {
            // M2ePro\TRANSLATIONS
            // You have to list Item first.
            $this->addMessage('You have to list Item first.');
            return false;
        }

        $params = $this->getParams();
        if (isset($params['changed_sku'])) {
            if (preg_match('/[.\s-]+/', $params['changed\sku'])) {
                $this->addMessage(
                    'Item SKU was not updated because it contains special characters,
                    i.e. hyphen (-), space ( ), and period (.), that are not allowed by Walmart.
                    Please enter SKU in a correct format. M2E Pro will resubmit the new value automatically.'
                );
                return false;
            }

            if (strlen($params['changed_sku']) > \Ess\M2ePro\Helper\Component\Walmart::SKU_MAX_LENGTH) {
                $this->addMessage('The length of SKU must be less than 50 characters.');
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------

    protected function validateCategory()
    {
        if (!$this->getWalmartListingProduct()->isExistCategoryTemplate()) {
            // M2ePro\TRANSLATIONS
            // Categories Settings are not set.
            $this->addMessage('Categories Settings are not set.');

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateOnlinePriceInvalidBlocked()
    {
        if ($this->getListingProduct()->isBlocked() && $this->getWalmartListingProduct()->isOnlinePriceInvalid()) {
            $message = <<<HTML
The action cannot be submitted. Your Item is in Inactive (Blocked) status because it violates Walmart pricing rules.
 Please adjust the Item Price to comply with the Walmart requirements.
 Once the changes are applied, Walmart Item will become Active automatically.
HTML;

            $this->addMessage($message);
            return false;
        }

        return true;
    }

    protected function validateMissedOnChannelBlocked()
    {
        if ($this->getListingProduct()->isBlocked() && $this->getWalmartListingProduct()->isMissedOnChannel()) {
            $message = <<<HTML
The action cannot be submitted. Your Item is in Inactive (Blocked) status because it seems that the corresponding
 Walmart Item does not exist in your Channel inventory. Please contact Walmart Support Team to resolve the issue.
HTML;

            $this->addMessage($message);
            return false;
        }

        return true;
    }

    protected function validateGeneralBlocked()
    {
        if ($this->getListingProduct()->isBlocked() &&
            !$this->getWalmartListingProduct()->isMissedOnChannel() &&
            !$this->getWalmartListingProduct()->isOnlinePriceInvalid()
        ) {
            $message = <<<HTML
The action cannot be submitted. Your Item is in Inactive (Blocked) status because some Item data may
 contradict Walmart rules. To restore the Item to Active status, please adjust the related Policy settings and
 click Reset next to that Item. M2E Pro will resubmit the Item automatically.
HTML;

            $this->addMessage($message);
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
            if (isset($this->params['status_changer']) &&
                $this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
                $message = 'You are submitting an Item with zero quantity. It contradicts Walmart requirements.';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= ' Please apply the Stop Action instead.';
                }

                $this->addMessage($message);
            } else {
                $message = 'Cannot submit an Item with zero quantity. It contradicts Walmart requirements.
                            This action has been generated automatically based on your Synchronization Rule settings. ';

                if ($this->getListingProduct()->isStoppable()) {
                    $message .= 'The error occurs when the Stop Rules are not properly configured or disabled. ';
                }

                $message .= 'Please review your settings.';

                $this->addMessage($message);
            }

            return false;
        }

        $this->data['qty'] = $qty;

        return true;
    }

    protected function validatePrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return true;
        }

        $price = $this->getPrice();
        if ($price <= 0) {
            // M2ePro\TRANSLATIONS
            // The Price must be greater than 0. Please, check the Selling Policy and Product Settings.
            $this->addMessage(
                'The Price must be greater than 0. Please, check the Selling Policy and Product Settings.'
            );

            return false;
        }

        $this->data['price'] = $price;

        return true;
    }

    // ---------------------------------------

    public function validateStartEndDates()
    {
        if (!$this->getConfigurator()->isDetailsAllowed()) {
            return true;
        }

        $startDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getStartDate();

        if (!empty($startDate) && !strtotime($startDate)) {
            $this->addMessage('Start Date has invalid format.');
            return false;
        }

        $endDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getEndDate();

        if (!empty($endDate)) {
            if (!strtotime($endDate)) {
                $this->addMessage('End Date has invalid format.');
                return false;
            }

            if (strtotime($endDate) < $this->getHelper('Data')->getCurrentGmtDate(true)) {
                $this->addMessage('End Date must be greater than current date');
                return false;
            }
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

        return true;
    }

    //########################################

    protected function validateMagentoProductType()
    {
        if ($this->getMagentoProduct()->isBundleType() ||
            $this->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
            $this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
        ) {
            $message = <<<HTML
Magento Simple with Custom Options, Bundle and Downloadable with Separated Links Products cannot be submitted to
the Walmart marketplace. These types of Magento Variational Products contradict Walmart Variant Group parameters.
Only Product Variations created based on Magento Configurable or Grouped Product types can be sold on
the Walmart website.
HTML;
            $this->addMessage($message);
            return false;
        }

        return true;
    }

    //########################################

    protected function getPrice()
    {
        if (isset($this->data['price'])) {
            return $this->data['price'];
        }

        return $this->getWalmartListingProduct()->getPrice();
    }

    protected function getQty()
    {
        if (isset($this->data['qty'])) {
            return $this->data['qty'];
        }

        return $this->getWalmartListingProduct()->getQty();
    }

    protected function getPromotions()
    {
        if (isset($this->data['promotions'])) {
            return $this->data['promotions'];
        }

        return $this->getWalmartListingProduct()->getPromotions();
    }

    //########################################

    protected function validatePromotions()
    {
        if (!$this->getConfigurator()->isPromotionsAllowed()) {
            return true;
        }

        $requiredAttributesMap = [
            'start_date'       => $this->helperFactory->getObject('Module\Translation')->__('Start Date'),
            'end_date'         => $this->helperFactory->getObject('Module\Translation')->__('End Date'),
            'price'            => $this->helperFactory->getObject('Module\Translation')->__('Promotion Price'),
            'comparison_price' => $this->helperFactory->getObject('Module\Translation')->__('Comparison Price'),
        ];

        $promotions = $this->getPromotions();
        foreach ($promotions as $promotionIndex => $promotionRow) {
            foreach ($requiredAttributesMap as $requiredAttributeKey => $requiredAttributeTitle) {
                if (empty($promotionRow[$requiredAttributeKey])) {
                    $message = <<<HTML
Invalid Promotion #%s. The Promotion Price has no defined value.
 Please adjust Magento Attribute "%s" value set for the Promotion Price in your Selling Policy.
HTML;
                    $this->addMessage(sprintf($message, $promotionIndex + 1, $requiredAttributeTitle));
                    return false;
                }
            }

            if (!strtotime($promotionRow['start_date'])) {
                $message = <<<HTML
Invalid Promotion #%s. The Start Date has incorrect format.
 Please adjust Magento Attribute value set for the Promotion Start Date in your Selling Policy.
HTML;
                $this->addMessage(sprintf($message, $promotionIndex + 1));
                return false;
            }

            if (!strtotime($promotionRow['end_date'])) {
                $message = <<<HTML
Invalid Promotion #%s. The End Date has incorrect format.
 Please adjust Magento Attribute value set for the Promotion End Date in your Selling Policy.
HTML;
                $this->addMessage(sprintf($message, $promotionIndex + 1));
                return false;
            }

            if (strtotime($promotionRow['end_date']) < strtotime($promotionRow['start_date'])) {
                $message = <<<HTML
Invalid Promotion #%s. The Start and End Date range is incorrect.
 Please adjust the Promotion Dates set in your Selling Policy.
HTML;
                $this->addMessage(sprintf($message, $promotionIndex + 1));
                return false;
            }

            if ($promotionRow['comparison_price'] <= $promotionRow['price']) {
                $message = <<<HTML
Invalid Promotion #%s. Comparison Price must be greater than Promotion Price.
 Please adjust the Price settings for the given Promotion in your Selling Policy.
HTML;
                $this->addMessage(sprintf($message, $promotionIndex + 1));
                return false;
            }
        }

        $this->data['promotions'] = $promotions;

        return true;
    }

    //########################################

    protected function validatePriceAndPromotionsFeedBlocked()
    {
        if ($this->getWalmartListingProduct()->getListDate() === null) {
            return true;
        }

        try {
            $borderDate = new \DateTime($this->getWalmartListingProduct()->getListDate(), new \DateTimeZone('UTC'));
            $borderDate->modify('+24 hours');
        } catch (\Exception $exception) {
            return true;
        }

        if ($borderDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return true;
        }

        if ($this->getConfigurator()->isPromotionsAllowed()) {
            $this->getConfigurator()->disallowPromotions();
            $this->addMessage(
                'Item Promotion Price will not be submitted during this action.
                Walmart allows updating the Promotion Price information no sooner than 24 hours after the
                relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $this->getConfigurator()->disallowPrice();
            $this->addMessage(
                'Item Price will not be submitted during this action.
                Walmart allows updating the Price information no sooner than 24 hours after the relevant product
                is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        return true;
    }

    //########################################

    protected function validateProductIds()
    {
        if (!$this->getConfigurator()->isDetailsAllowed()) {
            return true;
        }

        $isAtLeastOneSpecified = false;

        if ($gtin = $this->getGtin()) {
            if (strtoupper($gtin) !== ConfigurationHelper::PRODUCT_ID_OVERRIDE_CUSTOM_CODE &&
                !$this->getHelper('Data')->isGTIN($gtin)
            ) {
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'The action cannot be completed because the product GTIN has incorrect format: "%id%".
                        Please adjust the related Magento Attribute value and resubmit the action.',
                        ['!id' => $gtin]
                    )
                );
                return false;
            }

            $this->data['gtin'] = $gtin;
            $isAtLeastOneSpecified = true;
        }

        if ($upc = $this->getUpc()) {
            if (strtoupper($upc) !== ConfigurationHelper::PRODUCT_ID_OVERRIDE_CUSTOM_CODE &&
                !$this->getHelper('Data')->isUpc($upc)
            ) {
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'The action cannot be completed because the product UPC has incorrect format: "%id%".
                        Please adjust the related Magento Attribute value and resubmit the action.',
                        ['!id' => $upc]
                    )
                );
                return false;
            }

            $this->data['upc'] = $upc;
            $isAtLeastOneSpecified = true;
        }

        if ($ean = $this->getEan()) {
            if (strtoupper($ean) !== ConfigurationHelper::PRODUCT_ID_OVERRIDE_CUSTOM_CODE &&
                !$this->getHelper('Data')->isEAN($ean)
            ) {
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'The action cannot be completed because the product EAN has incorrect format: "%id%".
                        Please adjust the related Magento Attribute value and resubmit the action.',
                        ['!id' => $ean]
                    )
                );
                return false;
            }

            $this->data['ean'] = $ean;
            $isAtLeastOneSpecified = true;
        }

        if ($isbn = $this->getIsbn()) {
            if (strtoupper($isbn) !== ConfigurationHelper::PRODUCT_ID_OVERRIDE_CUSTOM_CODE &&
                !$this->getHelper('Data')->isISBN($isbn)
            ) {
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'The action cannot be completed because the product ISBN has incorrect format: "%id%".
                        Please adjust the related Magento Attribute value and resubmit the action.',
                        ['!id' => $isbn]
                    )
                );
                return false;
            }

            $this->data['isbn'] = $isbn;
            $isAtLeastOneSpecified = true;
        }

        if (!$isAtLeastOneSpecified) {
            $this->addMessage(
                'The Item was not listed because it has no defined Product ID. Walmart requires that all Items sold
                on the website have Product IDs. Please provide a valid GTIN, UPC, EAN or ISBN for the Product.
                M2E Pro will try to list the Item again.'
            );
            return false;
        }

        return true;
    }

    protected function getGtin()
    {
        if (isset($this->data['gtin'])) {
            return $this->data['gtin'];
        }

        return $this->getWalmartListingProduct()->getGtin();
    }

    protected function getUpc()
    {
        if (isset($this->data['upc'])) {
            return $this->data['upc'];
        }

        return $this->getWalmartListingProduct()->getUpc();
    }

    protected function getEan()
    {
        if (isset($this->data['ean'])) {
            return $this->data['ean'];
        }

        return $this->getWalmartListingProduct()->getEan();
    }

    protected function getIsbn()
    {
        if (isset($this->data['isbn'])) {
            return $this->data['isbn'];
        }

        return $this->getWalmartListingProduct()->getIsbn();
    }

    //########################################
}
