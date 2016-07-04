<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings\ByAsin;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Search\ByAsin\ItemsResponser
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->getObjectByParam('Listing\Product', 'listing_product_id');
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');

        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $logModel->addProductMessage(
            $this->getListingProduct()->getListingId(),
            $this->getListingProduct()->getProductId(),
            $this->getListingProduct()->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            NULL,
            \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN,
            $messageText,
            \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_HIGH
        );

        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        $amazonListingProduct->setData('search_settings_status', null);
        $amazonListingProduct->setData('search_settings_data', null);
        $amazonListingProduct->save();
    }

    //########################################

    protected function processResponseData()
    {
        $responseData = $this->getPreparedResponseData();

        /** @var \Ess\M2ePro\Model\Amazon\Search\Settings $settingsSearch */
        $settingsSearch = $this->modelFactory->getObject('Amazon\Search\Settings');
        $settingsSearch->setListingProduct($this->getListingProduct());
        $settingsSearch->setStep($this->params['step']);
        if (!empty($responseData)) {
            $settingsSearch->setStepData(array(
                'params' => $this->params,
                'result' => $responseData,
            ));
        }

        $settingsSearch->process();
    }

    //########################################
}