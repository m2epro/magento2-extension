<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type;

use \Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;

abstract class Response extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
     */
    private $configurator = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected $requestData = NULL;

    /**
     * @var array
     */
    protected $requestMetaData = array();

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function processSuccess(array $response, array $responseParams = array());

    //########################################

    protected function prepareMetadata()
    {
        // backward compatibility for case when we have old request data and new response logic
        $metadata = $this->getRequestMetaData();
        if (!isset($metadata["is_listing_type_fixed"])) {
            $metadata["is_listing_type_fixed"] = $this->getEbayListingProduct()->isListingTypeFixed();
            $this->setRequestMetaData($metadata);
        }
    }

    //########################################

    /**
     * @param array $params
     */
    public function setParams(array $params = array())
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
     * @param \Ess\M2ePro\Model\Listing\Product $object
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $object)
    {
        $this->listingProduct = $object;
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
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $object
     */
    public function setConfigurator(\Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $object
     */
    public function setRequestData(\Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $object)
    {
        $this->requestData = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestData()
    {
        return $this->requestData;
    }

    // ---------------------------------------

    public function getRequestMetaData()
    {
        return $this->requestMetaData;
    }

    public function setRequestMetaData($value)
    {
        $this->requestMetaData = $value;
        return $this;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    protected function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
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
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
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
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //########################################

    /**
     * @param $itemId
     * @return \Ess\M2ePro\Model\Ebay\Item
     */
    protected function createEbayItem($itemId)
    {
        $data = array(
            'account_id'     => $this->getAccount()->getId(),
            'marketplace_id' => $this->getMarketplace()->getId(),
            'item_id'        => (double)$itemId,
            'product_id'     => (int)$this->getListingProduct()->getProductId(),
            'store_id'       => (int)$this->getListing()->getStoreId()
        );

        if ($this->getRequestData()->isVariationItem() && $this->getRequestData()->getVariations()) {

            $variations = array();
            $requestMetadata = $this->getRequestMetaData();

            foreach ($this->getRequestData()->getVariations() as $variation) {
                $channelOptions = $variation['specifics'];
                $productOptions = $variation['specifics'];

                if (empty($requestMetadata['variations_specifics_replacements'])) {
                    $variations[] = array(
                        'product_options' => $productOptions,
                        'channel_options' => $channelOptions,
                    );

                    continue;
                }

                foreach ($requestMetadata['variations_specifics_replacements'] as $productValue => $channelValue) {
                    if (!isset($productOptions[$channelValue])) {
                        continue;
                    }

                    $productOptions[$productValue] = $productOptions[$channelValue];
                    unset($productOptions[$channelValue]);
                }

                $variations[] = array(
                    'product_options' => $productOptions,
                    'channel_options' => $channelOptions,
                );
            }

            $data['variations'] = $this->getHelper('Data')->jsonEncode($variations);
        }

        /** @var \Ess\M2ePro\Model\Ebay\Item $object */
        $object = $this->activeRecordFactory->getObject('Ebay\Item');
        $object->setData($data)->save();

        return $object;
    }

    protected function updateVariationsValues($saveQtySold)
    {
        if (!$this->getRequestData()->isVariationItem()) {
            return;
        }

        $requestVariations = $this->getRequestData()->getVariations();

        $requestMetadata     = $this->getRequestMetaData();
        $variationIdsIndexes = !empty($requestMetadata['variation_ids_indexes'])
            ? $requestMetadata['variation_ids_indexes'] : array();

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {

            if ($this->getRequestData()->hasVariations()) {

                if (!isset($variationIdsIndexes[$variation->getId()])) {
                    continue;
                }

                $requestVariation = $requestVariations[$variationIdsIndexes[$variation->getId()]];

                if ($requestVariation['delete']) {
                    $variation->delete();
                    continue;
                }

                $data = array(
                    'online_sku'   => $requestVariation['sku'],
                    'online_price' => $requestVariation['price'],
                    'add'          => 0,
                    'delete'       => 0,
                );

                /** @var EbayVariation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                $data['online_qty_sold'] = $saveQtySold ? (int)$ebayVariation->getOnlineQtySold() : 0;
                $data['online_qty'] = $requestVariation['qty'] + $data['online_qty_sold'];

                if (!empty($requestVariation['details']['mpn'])) {
                    $variationAdditionalData = $variation->getAdditionalData();
                    $variationAdditionalData['ebay_mpn_value'] = $requestVariation['details']['mpn'];

                    $data['additional_data'] = $this->getHelper('Data')->jsonEncode($variationAdditionalData);
                }

                $variation->getChildObject()->addData($data)->save();
            }

            $variation->getChildObject()->setStatus($this->getListingProduct()->getStatus());
        }
    }

    //########################################

    protected function appendStatusHiddenValue($data)
    {
        if (($this->getRequestData()->hasQty() && $this->getRequestData()->getQty() <= 0) ||
            ($this->getRequestData()->hasVariations() && $this->getRequestData()->getVariationQty() <= 0)) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
        }
        return $data;
    }

    protected function appendStatusChangerValue($data, $responseParams)
    {
        if (isset($this->params['status_changer'])) {
            $data['status_changer'] = (int)$this->params['status_changer'];
        }

        if (isset($responseParams['status_changer'])) {
            $data['status_changer'] = (int)$responseParams['status_changer'];
        }

        return $data;
    }

    // ---------------------------------------

    protected function appendOnlineBidsValue($data)
    {
        $metadata = $this->getRequestMetaData();

        if ($metadata["is_listing_type_fixed"]) {
            $data['online_bids'] = NULL;
        } else {
            $data['online_bids'] = 0;
        }

        return $data;
    }

    protected function appendOnlineQtyValues($data)
    {
        $data['online_qty_sold'] = 0;

        if ($this->getRequestData()->hasVariations()) {
            $data['online_qty'] = $this->getRequestData()->getVariationQty();
        } else if ($this->getRequestData()->hasQty()) {
            $data['online_qty'] = $this->getRequestData()->getQty();
        }

        return $data;
    }

    protected function appendOnlinePriceValues($data)
    {
        $metadata = $this->getRequestMetaData();

        if ($metadata["is_listing_type_fixed"]) {

            $data['online_start_price'] = NULL;
            $data['online_reserve_price'] = NULL;
            $data['online_buyitnow_price'] = NULL;

            if ($this->getRequestData()->hasVariations()) {

                if (!$this->getRequestData()->hasOutOfStockControlResult()) {
                    $calculateWithEmptyQty = $this->getEbayListingProduct()->getOutOfStockControl();
                } else {
                    $calculateWithEmptyQty = $this->getRequestData()->getOutOfStockControlResult();
                }

                $data['online_current_price'] = $this->getRequestData()->getVariationPrice($calculateWithEmptyQty);

            } else if ($this->getRequestData()->hasPriceFixed()) {
                $data['online_current_price'] = $this->getRequestData()->getPriceFixed();
            }

        } else {

            if ($this->getRequestData()->hasPriceStart()) {
                $data['online_start_price'] = $this->getRequestData()->getPriceStart();
                $data['online_current_price'] = $this->getRequestData()->getPriceStart();
            }
            if ($this->getRequestData()->hasPriceReserve()) {
                $data['online_reserve_price'] = $this->getRequestData()->getPriceReserve();
            }
            if ($this->getRequestData()->hasPriceBuyItNow()) {
                $data['online_buyitnow_price'] = $this->getRequestData()->getPriceBuyItNow();
            }
        }

        return $data;
    }

    protected function appendOnlineInfoDataValues($data)
    {
        if ($this->getRequestData()->hasSku()) {
            $data['online_sku'] = $this->getRequestData()->getSku();
        }

        if ($this->getRequestData()->hasTitle()) {
            $data['online_title'] = $this->getRequestData()->getTitle();
        }

        if ($this->getRequestData()->hasDuration()) {
            $data['online_duration'] = $this->getRequestData()->getDuration();
        }

        if ($this->getRequestData()->hasPrimaryCategory()) {

            $tempPath = $this->getHelper('Component\Ebay\Category\Ebay')->getPath(
                $this->getRequestData()->getPrimaryCategory(),
                $this->getMarketplace()->getId()
            );

            if ($tempPath) {
                $data['online_category'] = $tempPath.' ('.$this->getRequestData()->getPrimaryCategory().')';
            } else {
                $data['online_category'] = $this->getRequestData()->getPrimaryCategory();
            }
        }

        return $data;
    }

    // ---------------------------------------

    protected function appendOutOfStockValues($data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if ($this->getRequestData()->hasOutOfStockControl()) {
            $data['additional_data']['out_of_stock_control'] = $this->getRequestData()->getOutOfStockControl();
        }

        return $data;
    }

    protected function appendItemFeesValues($data, $response)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (isset($response['ebay_item_fees'])) {
            $data['additional_data']['ebay_item_fees'] = $response['ebay_item_fees'];
        }

        return $data;
    }

    protected function appendStartDateEndDateValues($data, $response)
    {
        if (isset($response['ebay_start_date_raw'])) {
            $data['start_date'] = \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString(
                $response['ebay_start_date_raw']
            );
        }

        if (isset($response['ebay_end_date_raw'])) {
            $data['end_date'] = \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime::ebayTimeToString(
                $response['ebay_end_date_raw']
            );
        }

        return $data;
    }

    protected function appendGalleryImagesValues($data, $response, $responseParams)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (isset($response['is_eps_ebay_images_mode'])) {
            $data['additional_data']['is_eps_ebay_images_mode'] = $response['is_eps_ebay_images_mode'];
        }

        if (!isset($responseParams['is_images_upload_error']) || !$responseParams['is_images_upload_error']) {

            $metadata = $this->getRequestMetaData();

            if ($this->getRequestData()->hasImages()) {

                $key = 'ebay_product_images_hash';
                $imagesData = $this->getRequestData()->getImages();

                if (!empty($metadata[$key]) && isset($imagesData['images'])) {
                    $data['additional_data'][$key] = $metadata[$key];
                }
            }

            if ($this->getRequestData()->hasVariationsImages()) {

                $key = 'ebay_product_variation_images_hash';

                if (!empty($metadata[$key])) {
                    $data['additional_data'][$key] = $metadata[$key];
                }
            }
        }

        return $data;
    }

    protected function appendIsVariationMpnFilledValue($data)
    {
        if (!$this->getRequestData()->hasVariations()) {
            return $data;
        }

        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        $isVariationMpnFilled = false;

        foreach ($this->getRequestData()->getVariations() as $variation) {
            if (empty($variation['details']['mpn'])) {
                continue;
            }

            $isVariationMpnFilled = true;
            break;
        }

        $data['additional_data']['is_variation_mpn_filled'] = $isVariationMpnFilled;

        if (!$isVariationMpnFilled) {
            $data['additional_data']['without_mpn_variation_issue'] = true;
        }

        return $data;
    }

    protected function appendVariationsThatCanNotBeDeleted(array $data, array $response)
    {
        if (!$this->getRequestData()->isVariationItem()) {
            return $data;
        }

        $variations = isset($response['variations_that_can_not_be_deleted'])
            ? $response['variations_that_can_not_be_deleted'] : array();

        $data['additional_data']['variations_that_can_not_be_deleted'] = $variations;

        return $data;
    }

    protected function appendIsVariationValue(array $data)
    {
        $data["online_is_variation"] = $this->getRequestData()->isVariationItem();

        return $data;
    }

    protected function appendIsAuctionType(array $data)
    {
        $metadata = $this->getRequestMetaData();
        $data["online_is_auction_type"] = !$metadata["is_listing_type_fixed"];

        return $data;
    }

    //########################################

    protected function runAccountPickupStoreStateUpdater()
    {
        $pickupStoreStateUpdater = $this->modelFactory->getObject('Ebay\Listing\Product\PickupStore\State\Updater');
        $pickupStoreStateUpdater->setListingProduct($this->getListingProduct());
        $pickupStoreStateUpdater->process();
    }

    //########################################
}