<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
 */
abstract class Response extends \Ess\M2ePro\Model\AbstractModel
{
    protected $resourceConnection;
    protected $activeRecordFactory;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
     */
    private $configurator = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
     */
    protected $requestData = null;

    /**
     * @var array
     */
    protected $requestMetaData = [];

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    abstract public function processSuccess($params = []);

    //########################################

    public function setParams(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $object
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $object)
    {
        $this->listingProduct = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $object
     */
    public function setConfigurator(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $object
     */
    public function setRequestData(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData $object)
    {
        $this->requestData = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
     */
    protected function getRequestData()
    {
        return $this->requestData;
    }

    // ---------------------------------------

    public function getRequestMetaData($key = null)
    {
        if ($key !== null) {
            return isset($this->requestMetaData[$key]) ? $this->requestMetaData[$key] : null;
        }

        return $this->requestMetaData;
    }

    public function setRequestMetaData($value)
    {
        $this->requestMetaData = $value;
        return $this;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    protected function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    protected function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    protected function getWalmartMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    protected function getWalmartAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //########################################

    protected function appendStatusChangerValue($data)
    {
        if (isset($this->params['status_changer'])) {
            $data['status_changer'] = (int)$this->params['status_changer'];
        }

        return $data;
    }

    // ---------------------------------------

    protected function appendQtyValues($data)
    {
        if ($this->getRequestData()->hasQty()) {
            $data['online_qty'] = (int)$this->getRequestData()->getQty();

            if ((int)$data['online_qty'] > 0) {
                $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
            } else {
                $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
            }
        }

        return $data;
    }

    protected function appendLagTimeValues($data)
    {
        if ($this->getRequestData()->hasLagTime()) {
            $data['online_lag_time'] = $this->getRequestData()->getLagTime();
        }

        return $data;
    }

    protected function appendPriceValues($data)
    {
        if (!$this->getRequestData()->hasPrice()) {
            return $data;
        }

        $data['online_price'] = (float)$this->getRequestData()->getPrice();

        return $data;
    }

    protected function appendPromotionsValues($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['promotions_data'])) {
            return $data;
        }

        $data['online_promotions'] = $this->getHelper('Data')->jsonEncode($requestMetadata['promotions_data']);

        return $data;
    }

    protected function appendDetailsValues($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['details_data'])) {
            return $data;
        }

        $data['online_details_data'] = $this->getHelper('Data')->jsonEncode($requestMetadata['details_data']);

        return $data;
    }

    protected function appendStartDate($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['details_data']['start_date'])) {
            return $data;
        }

        $data['online_start_date'] = $requestMetadata['details_data']['start_date'];

        return $data;
    }

    protected function appendEndDate($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['details_data']['end_date'])) {
            return $data;
        }

        $data['online_end_date'] = $requestMetadata['details_data']['end_date'];

        return $data;
    }

    protected function appendChangedSku($data)
    {
        if (!$this->getRequestData()->getIsNeedSkuUpdate()) {
            return $data;
        }

        $walmartItem = $this->getListingProduct()->getChildObject()->getWalmartItem();
        $walmartItem->setData('sku', $this->getRequestData()->getSku());
        $walmartItem->save();

        $data['sku'] = $this->getRequestData()->getSku();

        return $data;
    }

    protected function appendProductIdsData($data)
    {
        if (!$this->getRequestData()->hasProductIdsData()) {
            return $data;
        }

        $productIdsData = $this->getRequestData()->getProductIdsData();

        foreach ($productIdsData as $productIdData) {
            $data[strtolower($productIdData['type'])] = $productIdData['id'];
        }

        return $data;
    }

    //########################################

    protected function setLastSynchronizationDates()
    {
        if (!$this->getConfigurator()->isQtyAllowed() && !$this->getConfigurator()->isPriceAllowed()) {
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getConfigurator()->isQtyAllowed()) {
            $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $additionalData['last_synchronization_dates']['price'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################
}
