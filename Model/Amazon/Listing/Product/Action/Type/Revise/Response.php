<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty as RequestQty;

class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = array())
    {
        $data = array();

        if ($this->getConfigurator()->isDefaultMode()) {
            $data['synch_status'] = \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK;
            $data['synch_reasons'] = NULL;
        }

        if ($this->getConfigurator()->isDetailsAllowed() || $this->getConfigurator()->isImagesAllowed()) {
            $data['defected_messages'] = null;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendRegularPriceValues($data);
        $data = $this->appendBusinessPriceValues($data);

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

        $sequenceString = '';

        if ($this->getConfigurator()->isQtyAllowed()) {

            $params = $this->getParams();

            if (!empty($params['switch_to']) &&
                $params['switch_to'] ===
                    \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN) {

                // M2ePro\TRANSLATIONS
                // Item was successfully switched to AFN
                return 'Item was successfully switched to AFN';
            }

            if (!empty($params['switch_to']) &&
                $params['switch_to'] ===
                    \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN) {

                // M2ePro\TRANSLATIONS
                // Item was successfully switched to MFN
                return 'Item was successfully switched to MFN';
            }

            // M2ePro\TRANSLATIONS
            // QTY
            $sequenceString .= 'QTY,';
        }

        if ($this->getConfigurator()->isRegularPriceAllowed()) {
            // M2ePro\TRANSLATIONS
            // Price
            $sequenceString .= 'Price,';
        }

        if ($this->getConfigurator()->isBusinessPriceAllowed()) {
            // M2ePro_TRANSLATIONS
            // Business Price
            $sequenceString .= 'Business Price,';
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            // M2ePro\TRANSLATIONS
            // details
            $sequenceString .= 'details,';
        }

        if ($this->getConfigurator()->isImagesAllowed()) {
            // M2ePro\TRANSLATIONS
            // images
            $sequenceString .= 'images,';
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

    protected function appendQtyValues($data)
    {
        $params = $this->getParams();

        if (!empty($params['switch_to']) &&
            $params['switch_to'] === RequestQty::FULFILLMENT_MODE_AFN) {

            $data['is_afn_channel'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_YES;
            $data['online_qty'] = null;
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;

            return $data;
        }

        if (!empty($params['switch_to']) &&
            $params['switch_to'] === RequestQty::FULFILLMENT_MODE_MFN) {

            $data['is_afn_channel'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_NO;
        }

        return parent::appendQtyValues($data);
    }

    // ---------------------------------------

    protected function setLastSynchronizationDates()
    {
        parent::setLastSynchronizationDates();

        $params = $this->getParams();
        if (!isset($params['switch_to'])) {
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        $additionalData['last_synchronization_dates']['fulfillment_switching']
                = $this->getHelper('Data')->getCurrentGmtDate();

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################
}