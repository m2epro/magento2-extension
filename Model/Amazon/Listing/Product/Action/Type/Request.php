<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

abstract class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request
{
    /**
     * @var array
     */
    protected $validatorsData = array();

    /**
     * @var array
     */
    private $requestsTypes = array(
        'details',
        'images',
        'price',
        'qty',
        'shippingOverride'
    );

    /**
     * @var array[\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Abstract]
     */
    private $requests = array();

    //########################################

    public function setValidatorsData(array $data)
    {
        $this->validatorsData = $data;
    }

    /**
     * @return array
     */
    public function getValidatorsData()
    {
        return $this->validatorsData;
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

    protected function beforeBuildDataEvent() {}

    abstract protected function getActionData();

    // ---------------------------------------

    protected function prepareFinalData(array $data)
    {
        return $data;
    }

    protected function collectRequestsWarningMessages()
    {
        foreach ($this->requestsTypes as $requestType) {

            $messages = $this->getRequest($requestType)->getWarningMessages();

            foreach ($messages as $message) {
                $this->addWarningMessage($message);
            }
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Details
     */
    public function getRequestDetails()
    {
        return $this->getRequest('details');
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Images
     */
    public function getRequestImages()
    {
        return $this->getRequest('images');
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Price
     */
    public function getRequestPrice()
    {
        return $this->getRequest('price');
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty
     */
    public function getRequestQty()
    {
        return $this->getRequest('qty');
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\ShippingOverride
     */
    public function getRequestShippingOverride()
    {
        return $this->getRequest('shippingOverride');
    }

    //########################################

    /**
     * @param $type
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
     */
    private function getRequest($type)
    {
        if (!isset($this->requests[$type])) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel $request */
            $request = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Request\\'.ucfirst($type));

            $request->setParams($this->getParams());
            $request->setListingProduct($this->getListingProduct());
            $request->setConfigurator($this->getConfigurator());
            $request->setValidatorsData($this->getValidatorsData());

            $this->requests[$type] = $request;
        }

        return $this->requests[$type];
    }

    //########################################
}