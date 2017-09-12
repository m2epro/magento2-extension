<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise;

class Response extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
{
    //########################################

    public function processSuccess(array $response, array $responseParams = array())
    {
        $this->prepareMetadata();

        $data = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );

        if ($this->getConfigurator()->isDefaultMode()) {
            $data['synch_status'] = \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK;
            $data['synch_reasons'] = NULL;
        }

        $data = $this->appendStatusHiddenValue($data);
        $data = $this->appendStatusChangerValue($data, $responseParams);

        $data = $this->appendOnlineBidsValue($data);
        $data = $this->appendOnlineQtyValues($data);
        $data = $this->appendOnlinePriceValues($data);
        $data = $this->appendOnlineInfoDataValues($data);

        $data = $this->appendOutOfStockValues($data);
        $data = $this->appendItemFeesValues($data, $response);
        $data = $this->appendStartDateEndDateValues($data, $response);
        $data = $this->appendGalleryImagesValues($data, $response, $responseParams);

        $data = $this->appendIsVariationMpnFilledValue($data);
        $data = $this->appendVariationsThatCanNotBeDeleted($data, $response);

        $data = $this->appendIsVariationValue($data);
        $data = $this->appendIsAuctionType($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->getListingProduct()->save();

        $this->updateVariationsValues(true);
        $this->updateEbayItem();

        if ($this->getEbayAccount()->isPickupStoreEnabled() && $this->getConfigurator()->isVariationsAllowed()) {
            $this->runAccountPickupStoreStateUpdater();
        }
    }

    public function processAlreadyStopped(array $response, array $responseParams = array())
    {
        $responseParams['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

        $data = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
        );

        $data = $this->appendStatusChangerValue($data, $responseParams);
        $data = $this->appendStartDateEndDateValues($data, $response);

        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        $data['additional_data']['ebay_item_fees'] = array();
        $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);

        $this->getListingProduct()->addData($data)->save();
    }

    //########################################

    /**
     * @return string
     */
    public function getSuccessfulMessage()
    {
        if ($this->getConfigurator()->isDefaultMode()) {
            // M2ePro\TRANSLATIONS
            // Item was successfully Revised
            return 'Item was successfully Revised';
        }

        $sequenceString = '';

        if ($this->getConfigurator()->isVariationsAllowed() && $this->getRequestData()->isVariationItem()) {
            // M2ePro\TRANSLATIONS
            // Variations
            $sequenceString .= 'Variations,';
        } else {
            if ($this->getConfigurator()->isQtyAllowed()) {
                // M2ePro\TRANSLATIONS
                // QTY
                $sequenceString .= 'QTY,';
            }

            if ($this->getConfigurator()->isPriceAllowed()) {
                // M2ePro\TRANSLATIONS
                // Price
                $sequenceString .= 'Price,';
            }
        }

        if ($this->getConfigurator()->isTitleAllowed()) {
            // M2ePro\TRANSLATIONS
            // Title
            $sequenceString .= 'Title,';
        }

        if ($this->getConfigurator()->isSubtitleAllowed()) {
            // M2ePro\TRANSLATIONS
            // Subtitle
            $sequenceString .= 'Subtitle,';
        }

        if ($this->getConfigurator()->isDescriptionAllowed()) {
            // M2ePro\TRANSLATIONS
            // Description
            $sequenceString .= 'Description,';
        }

        if ($this->getConfigurator()->isImagesAllowed()) {
            // M2ePro\TRANSLATIONS
            // Images
            $sequenceString .= 'Images,';
        }

        if ($this->getConfigurator()->isSpecificsAllowed()) {
            // M2ePro\TRANSLATIONS
            // Specifics
            $sequenceString .= 'Specifics,';
        }

        if ($this->getConfigurator()->isShippingServicesAllowed()) {
            // M2ePro\TRANSLATIONS
            // Shipping Services
            $sequenceString .= 'Shipping Services,';
        }

        if (empty($sequenceString)) {
            // M2ePro\TRANSLATIONS
            // Item was successfully Revised
            return 'Item was successfully Revised';
        }

        // M2ePro\TRANSLATIONS
        // was successfully Revised
        return ucfirst(trim($sequenceString,',')).' was successfully Revised';
    }

    //########################################

    protected function appendOnlineBidsValue($data)
    {
        $metadata = $this->getRequestMetaData();

        if ($metadata["is_listing_type_fixed"]) {
            $data['online_bids'] = NULL;
        }

        return $data;
    }

    protected function appendOnlineQtyValues($data)
    {
        $data = parent::appendOnlineQtyValues($data);

        $data['online_qty_sold'] = (int)$this->getEbayListingProduct()->getOnlineQtySold();
        isset($data['online_qty']) && $data['online_qty'] += $data['online_qty_sold'];

        return $data;
    }

    protected function appendOnlinePriceValues($data)
    {
        $data = parent::appendOnlinePriceValues($data);

        if ($this->getRequestData()->hasPriceStart() &&
            $this->getEbayListingProduct()->isListingTypeAuction() &&
            $this->getEbayListingProduct()->getOnlineBids()) {
            unset($data['online_current_price']);
        }

        return $data;
    }

    // ---------------------------------------

    protected function appendItemFeesValues($data, $response)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (isset($response['ebay_item_fees'])) {

            foreach ($response['ebay_item_fees'] as $feeCode => $feeData) {

                if ($feeData['fee'] == 0) {
                    continue;
                }

                if (!isset($data['additional_data']['ebay_item_fees'][$feeCode])) {
                    $data['additional_data']['ebay_item_fees'][$feeCode] = $feeData;
                } else {
                    $data['additional_data']['ebay_item_fees'][$feeCode]['fee'] += $feeData['fee'];
                }
            }
        }

        return $data;
    }

    // ---------------------------------------

    private function updateEbayItem()
    {
        $data = array(
            'account_id'     => $this->getAccount()->getId(),
            'marketplace_id' => $this->getMarketplace()->getId(),
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
        $object = $this->getEbayListingProduct()->getEbayItem();
        $object->addData($data)->save();

        return $object;
    }

    //########################################
}