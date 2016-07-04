<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\ListAction;

class SingleRequester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Single\Requester
{
    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        parent::setListingProduct($listingProduct);

        $this->listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK);
        $this->listingProduct->setData('synch_reasons', null);

        $additionalData = $this->listingProduct->getAdditionalData();

        unset($additionalData['synch_template_list_rules_note']);

        if (isset($additionalData['add_to_schedule'])) {
            unset($additionalData['add_to_schedule']);
        }

        $this->listingProduct->setSettings('additional_data', $additionalData);

        $this->listingProduct->save();
    }

    //########################################

    protected function getCommand()
    {
        return array('item','add','single');
    }

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    //########################################

    protected function getRequestData()
    {
        $this->getRequestObject()->resetVariations();
        return parent::getRequestData();
    }

    //########################################
}