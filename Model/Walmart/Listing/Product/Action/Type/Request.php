<?php

/**
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
     * LagTime and Qty always should be sent together for Canada(ONLY) Marketplace
     * @return array
     */
    public function getQtyData()
    {
        if (
            $this->getWalmartMarketplace()->isCanada()
            && $this->getConfigurator()->isLagTimeAllowed()
        ) {
            $this->getConfigurator()->allowQty();
        }

        if (!$this->getConfigurator()->isQtyAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('qty');

        return $dataBuilder->getBuilderData();
    }

    /**
     * LagTime and Qty always should be sent together for Canada(ONLY) Marketplace
     * @return array
     */
    public function getLagTimeData()
    {
        if (
            $this->getWalmartMarketplace()->isCanada()
            && $this->getConfigurator()->isQtyAllowed()
        ) {
            $this->getConfigurator()->allowLagTime();
        }

        if (!$this->getConfigurator()->isLagTimeAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('lagTime');

        return $dataBuilder->getBuilderData();
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

        return $dataBuilder->getBuilderData();
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
        $data = $dataBuilder->getBuilderData();

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

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Details $dataBuilder */
        $dataBuilder = $this->getDataBuilder('details');
        $data = $dataBuilder->getBuilderData();

        $this->addMetaData('details_data', $data);

        return $data;
    }

    public function getRepricerData(): array
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return [];
        }

        $isChangedRepricerData = $this->modelFactory
            ->getObjectByClass(\Ess\M2ePro\Model\Walmart\Listing\Product\Repricer\IsChangedRepricerData::class);

        $isStatusChangerUser =
            ($this->getParams()['status_changer'] ?? \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN)
            === \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        if (
            !$isChangedRepricerData->execute($this->getWalmartListingProduct())
            && !$isStatusChangerUser
        ) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Repricer $dataBuilder */
        $dataBuilder = $this->getDataBuilder('repricer');
        $data = $dataBuilder->getBuilderData();

        $this->addMetaData('repricer_data', $data);

        return $data;
    }

    //########################################

    /**
     * @param $type
     *
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
