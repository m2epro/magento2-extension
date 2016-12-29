<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Stop;

class Response extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
{
    //########################################

    public function processSuccess(array $response, array $responseParams = array())
    {
        $data = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
        );

        $data = $this->appendStatusChangerValue($data, $responseParams);

        $data = $this->appendItemFeesValues($data, $response);
        $data = $this->appendStartDateEndDateValues($data, $response);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->getListingProduct()->save();

        $this->updateVariationsValues(false);
    }

    public function processAlreadyStopped(array $response, array $responseParams = array())
    {
        $responseParams['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
        $this->processSuccess($response,$responseParams);
    }

    //########################################

    protected function appendItemFeesValues($data, $response)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        $data['additional_data']['ebay_item_fees'] = array();

        return $data;
    }

    // ---------------------------------------

    protected function updateVariationsValues($saveQtySold)
    {
        $variations = $this->getListingProduct()->getVariations(true);

        foreach ($variations as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            $data = array(
                'add' => 0
            );

            if ($variation->getChildObject()->isListed()) {
                $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
            }

            $variation->addData($data)->save();
        }
    }

    //########################################
}