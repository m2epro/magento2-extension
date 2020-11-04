<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings\ByAsin;

/**
 * Class \Ess\M2ePro\Model\Amazon\Search\Settings\ByAsin\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Search\ByAsin\ItemsResponser
{
    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getListingProduct()
    {
        return $this->amazonFactory->getObjectLoaded('Listing\Product', $this->params['listing_product_id']);
    }

    //########################################

    /**
     * @param $messageText
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $listingProduct = $this->getListingProduct();

        $logModel->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN,
            $messageText,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );

        $amazonListingProduct = $listingProduct->getChildObject();

        $amazonListingProduct->setData('search_settings_status', null);
        $amazonListingProduct->setData('search_settings_data', null);
        $amazonListingProduct->save();
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        $responseData = $this->getPreparedResponseData();

        /** @var \Ess\M2ePro\Model\Amazon\Search\Settings $settingsSearch */
        $settingsSearch = $this->modelFactory->getObject('Amazon_Search_Settings');
        $settingsSearch->setListingProduct($this->getListingProduct());
        $settingsSearch->setStep($this->params['step']);
        if (!empty($responseData)) {
            $settingsSearch->setStepData([
                'params' => $this->params,
                'result' => $responseData,
            ]);
        }

        $settingsSearch->process();
    }

    //########################################
}
