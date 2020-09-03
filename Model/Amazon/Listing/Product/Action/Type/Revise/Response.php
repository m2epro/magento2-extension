<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty as DataBuilderQty;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Response
 */
class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = [])
    {
        $data = [];

        if ($this->getConfigurator()->isDetailsAllowed() ||
            $this->getConfigurator()->isImagesAllowed()
        ) {
            $data['defected_messages'] = null;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendRegularPriceValues($data);
        $data = $this->appendBusinessPriceValues($data);
        $data = $this->appendGiftSettingsStatus($data);
        $data = $this->appendDetailsValues($data);
        $data = $this->appendImagesValues($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################

    protected function appendQtyValues($data)
    {
        $params = $this->getParams();

        if (!empty($params['switch_to']) && $params['switch_to'] === DataBuilderQty::FULFILLMENT_MODE_AFN) {
            $data['is_afn_channel'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_YES;
            $data['online_qty'] = null;
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;

            return $data;
        }

        if (!empty($params['switch_to']) && $params['switch_to'] === DataBuilderQty::FULFILLMENT_MODE_MFN) {
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
