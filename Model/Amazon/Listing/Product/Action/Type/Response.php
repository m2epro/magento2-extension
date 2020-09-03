<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

use Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
 */
abstract class Response extends \Ess\M2ePro\Model\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'action_response';

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
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    private $configurator = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected $requestData = null;

    /**
     * @var array
     */
    protected $requestMetaData = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
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
    public function getParams()
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
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $object
     */
    public function setConfigurator(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $object
     */
    public function setRequestData(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $object)
    {
        $this->requestData = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestData()
    {
        return $this->requestData;
    }

    // ---------------------------------------

    public function getRequestMetaData()
    {
        return $this->requestMetaData;
    }

    public function setRequestMetaData($value)
    {
        $this->requestMetaData = $value;
        return $this;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    protected function getAmazonListingProduct()
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
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    protected function getAmazonListing()
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
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    protected function getAmazonMarketplace()
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
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    protected function getAmazonAccount()
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

        if ($this->getRequestData()->hasHandlingTime()) {
            $data['online_handling_time'] = $this->getRequestData()->getHandlingTime();
        }

        if ($this->getRequestData()->hasRestockDate()) {
            $data['online_restock_date'] = $this->getRequestData()->getRestockDate();
        }

        return $data;
    }

    protected function appendRegularPriceValues($data)
    {
        if (!$this->getRequestData()->hasRegularPrice()) {
            return $data;
        }

        $data['online_regular_price'] = (float)$this->getRequestData()->getRegularPrice();

        $data['online_regular_sale_price'] = null;
        $data['online_regular_sale_price_start_date'] = null;
        $data['online_regular_sale_price_end_date'] = null;

        if ($this->getRequestData()->hasRegularSalePrice()) {
            $salePrice = (float)$this->getRequestData()->getRegularSalePrice();

            if ($salePrice > 0) {
                $data['online_regular_sale_price'] = $salePrice;
                $data['online_regular_sale_price_start_date'] = $this->getRequestData()->getRegularSalePriceStartDate();
                $data['online_regular_sale_price_end_date'] = $this->getRequestData()->getRegularSalePriceEndDate();
            } else {
                $data['online_regular_sale_price'] = 0;
            }
        }

        return $data;
    }

    protected function appendBusinessPriceValues($data)
    {
        if (!$this->getRequestData()->hasBusinessPrice()) {
            return $data;
        }

        $data['online_business_price'] = (float)$this->getRequestData()->getBusinessPrice();

        if ($this->getRequestData()->hasBusinessDiscounts()) {
            $businessDiscounts = $this->getRequestData()->getBusinessDiscounts();
            $data['online_business_discounts'] = $this->getHelper('Data')->jsonEncode($businessDiscounts['values']);
        } else {
            $data['online_business_discounts'] = null;
        }

        return $data;
    }

    protected function appendDetailsValues($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['details_data'])) {
            return $data;
        }

        $data['online_details_data'] = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($requestMetadata['details_data']),
            'md5'
        );

        return $data;
    }

    protected function appendImagesValues($data)
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['images_data'])) {
            return $data;
        }

        $data['online_images_data'] = $this->getHelper('Data')->hashString(
            $this->getHelper('Data')->jsonEncode($requestMetadata['images_data']),
            'md5'
        );

        return $data;
    }

    //########################################

    protected function appendGiftSettingsStatus($data)
    {
        if (!$this->getRequestData()->hasGiftWrap() && !$this->getRequestData()->hasGiftMessage()) {
            return $data;
        }

        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (!$this->getRequestData()->getGiftWrap() && !$this->getRequestData()->getGiftMessage()) {
            $data['additional_data']['online_gift_settings_disabled'] = true;
        } else {
            $data['additional_data']['online_gift_settings_disabled'] = false;
        }

        return $data;
    }

    //########################################

    protected function setLastSynchronizationDates()
    {
        if (!$this->getConfigurator()->isQtyAllowed() && !$this->getConfigurator()->isRegularPriceAllowed()) {
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getConfigurator()->isQtyAllowed()) {
            $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        if ($this->getConfigurator()->isRegularPriceAllowed()) {
            $additionalData['last_synchronization_dates']['price'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################

    public function throwRepeatActionInstructions()
    {
        $instructions = [];

        if ($this->getConfigurator()->isQtyAllowed()) {
            $instructions[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => ChangeProcessor::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => 80
            ];
        }

        if ($this->getConfigurator()->isRegularPriceAllowed() || $this->getConfigurator()->isBusinessPriceAllowed()) {
            $instructions[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => ChangeProcessor::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => 80
            ];
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            $instructions[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => ChangeProcessor::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => 60
            ];
        }

        if ($this->getConfigurator()->isImagesAllowed()) {
            $instructions[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => ChangeProcessor::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => 30
            ];
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructions);
    }

    //########################################
}
