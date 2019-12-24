<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise;

use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Request as ReviseRequest;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Response
 */
class Response extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = [])
    {
        $data = [];

        if ($this->getConfigurator()->isDefaultMode()) {
            $data['synch_status'] = \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK;
            $data['synch_reasons'] = null;
        }

        if ($this->getRequestData()->getIsNeedProductIdUpdate()) {
            $data['wpid'] = $params['wpid'];
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $data['is_online_price_invalid'] = 0;
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            $data['is_details_data_changed'] = 0;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);
        $data = $this->appendPriceValues($data);
        $data = $this->appendPromotionsValues($data);
        $data = $this->appendDetailsValues($data);
        $data = $this->appendStartDate($data);
        $data = $this->appendEndDate($data);
        $data = $this->appendChangedSku($data);
        $data = $this->appendProductIdsData($data);

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
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

        $sequenceStrings = [];
        $isPlural = false;

        if ($this->getConfigurator()->isQtyAllowed()) {
            // M2ePro\TRANSLATIONS
            // QTY
            $sequenceStrings[] = 'QTY';
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            // M2ePro\TRANSLATIONS
            // Price
            $sequenceStrings[] = 'Price';
        }

        if ($this->getConfigurator()->isPromotionsAllowed()) {
            // M2ePro\TRANSLATIONS
            // Promotions
            $sequenceStrings[] = 'Promotions';
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            if ($this->getRequestData()->getIsNeedSkuUpdate()) {
                // M2ePro\TRANSLATIONS
                // SKU
                $sequenceStrings[] = 'SKU';
            }

            if ($this->getRequestData()->getIsNeedProductIdUpdate()) {
                $idsMetadata = $this->getRequestMetaData(ReviseRequest::PRODUCT_ID_UPDATE_METADATA_KEY);
                !empty($idsMetadata) && $sequenceStrings[] = strtoupper($idsMetadata['type']);
            }

            // M2ePro\TRANSLATIONS
            // Details
            $sequenceStrings[] = 'Details';
            $isPlural = true;
        }

        if (empty($sequenceStrings)) {
            // M2ePro\TRANSLATIONS
            // Item was successfully Revised
            return 'Item was successfully Revised';
        }

        if (count($sequenceStrings) == 1) {
            $verb = 'was';
            if ($isPlural) {
                $verb = 'were';
            }

            return ucfirst($sequenceStrings[0]) . ' ' . $verb . ' successfully Revised';
        }

        // M2ePro\TRANSLATIONS
        // was successfully Revised
        return ucfirst(implode(', ', $sequenceStrings)) . ' were successfully Revised';
    }

    //########################################
}
