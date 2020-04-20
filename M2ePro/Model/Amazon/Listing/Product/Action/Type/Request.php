<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
 */
abstract class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request
{
    /**
     * @var array
     */
    protected $cachedData = [];

    /**
     * @var array
     */
    protected $dataTypes = [
        'qty',
        'price_regular',
        'price_business',
        'details',
        'images',
    ];

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\AbstractModel[]
     */
    protected $dataBuilders = [];

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
        $this->collectRequestsWarningMessages();

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

    protected function collectRequestsWarningMessages()
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getQtyData()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return [];
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('qty');
        return $dataBuilder->getBuilderData();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularPriceData()
    {
        if (!$this->getConfigurator()->isRegularPriceAllowed()) {
            return [];
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('price_regular');
        return $dataBuilder->getBuilderData();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessPriceData()
    {
        if (!$this->getConfigurator()->isBusinessPriceAllowed()) {
            return [];
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('price_business');
        return $dataBuilder->getBuilderData();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDetailsData()
    {
        if (!$this->getConfigurator()->isDetailsAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('details');
        $data = $dataBuilder->getBuilderData();

        $this->addMetaData('details_data', $data);

        return $data;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getImagesData()
    {
        if (!$this->getConfigurator()->isImagesAllowed()) {
            return [];
        }

        $dataBuilder = $this->getDataBuilder('images');
        $data = $dataBuilder->getBuilderData();

        $this->addMetaData('images_data', $data);

        return $data;
    }

    //########################################

    /**
     * @param $type
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getDataBuilder($type)
    {
        if (!isset($this->dataBuilders[$type])) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\AbstractModel $dataBuilder */
            $dataBuilder = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Action\DataBuilder\\' . ucwords($type, '_')
            );

            $dataBuilder->setParams($this->getParams());
            $dataBuilder->setListingProduct($this->getListingProduct());
            $dataBuilder->setCachedData($this->getCachedData());

            $this->dataBuilders[$type] = $dataBuilder;
        }

        return $this->dataBuilders[$type];
    }

    //########################################
}
