<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request
 */
abstract class Request extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Request
{
    /**
     * @var array
     */
    protected $cachedData = [];

    /**
     * @var array
     */
    private $dataTypes = [
        'qty',
        'lagTime',
        'price',
        'promotions',
        'details',
    ];

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel[]
     */
    private $dataBuilders = [];

    //########################################

    public function setCachedData(array $data)
    {
        $this->cachedData = $data;
    }

    /**
     * @return array
     */
    public function getCachedData()
    {
        return $this->cachedData;
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $this->beforeBuildDataEvent();
        $data = $this->getActionData();

        $data = $this->prepareFinalData($data);
        $this->collectDataBuildersWarningMessages();

        return $data;
    }

    //########################################

    protected function beforeBuildDataEvent()
    {
        return null;
    }

    abstract protected function getActionData();

    // ---------------------------------------

    protected function prepareFinalData(array $data)
    {
        return $data;
    }

    protected function collectDataBuildersWarningMessages()
    {
        foreach ($this->dataTypes as $requestType) {
            $messages = $this->getDataBuilder($requestType)->getWarningMessages();

            foreach ($messages as $message) {
                $this->addWarningMessage($message);
            }
        }
    }

    //########################################

    /**
     * @return array
     */
    public function getQtyData()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('qty');
        return $dataBuilder->getRequestData();
    }

    /**
     * @return array
     */
    public function getLagTimeData()
    {
        if (!$this->getConfigurator()->isLagTimeAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('lagTime');
        return $dataBuilder->getRequestData();
    }

    /**
     * @return array
     */
    public function getPriceData()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('price');
        return $dataBuilder->getRequestData();
    }

    /**
     * @return array
     */
    public function getPromotionsData()
    {
        if (!$this->getConfigurator()->isPromotionsAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('promotions');
        $data = $dataBuilder->getRequestData();

        $this->addMetaData('promotions_data', $data);

        return $data;
    }

    /**
     * @return array
     */
    public function getDetailsData()
    {
        if (!$this->getConfigurator()->isDetailsAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('details');
        $data = $dataBuilder->getRequestData();

        $this->addMetaData('details_data', $data);

        return $data;
    }

    //########################################

    /**
     * @param $type
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
     */
    private function getDataBuilder($type)
    {
        if (!isset($this->dataBuilders[$type])) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel $dataBuilder */
            $dataBuilder = $this->modelFactory
                ->getObject('Walmart\Listing\Product\Action\DataBuilder\\' . ucfirst($type));

            $dataBuilder->setParams($this->getParams());
            $dataBuilder->setListingProduct($this->getListingProduct());
            $dataBuilder->setCachedData($this->getCachedData());

            $this->dataBuilders[$type] = $dataBuilder;
        }

        return $this->dataBuilders[$type];
    }

    //########################################
}
