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
        $data = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );

        if ($this->getConfigurator()->isAllAllowed()) {
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

        if (isset($data['additional_data'])) {
            $data['additional_data'] = json_encode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->getListingProduct()->save();

        $this->updateVariationsValues(true);

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
        $data['additional_data'] = json_encode($data['additional_data']);

        $this->getListingProduct()->addData($data)->save();
    }

    //########################################

    /**
     * @return string
     */
    public function getSuccessfulMessage()
    {
        if ($this->getConfigurator()->isAllAllowed()) {
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
        if ($this->getEbayListingProduct()->isListingTypeFixed()) {
            return parent::appendOnlineBidsValue($data);
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

        $params = $this->getConfigurator()->getParams();

        if (!isset($params['replaced_action']) ||
            $params['replaced_action'] != \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
            return $data;
        }

        if (!$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getRequestData()->hasVariations() ||
            !isset($data['online_current_price'])) {
            return $data;
        }

        $data['online_current_price'] = $this->getRequestData()->getVariationPrice(true);

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

    //########################################
}