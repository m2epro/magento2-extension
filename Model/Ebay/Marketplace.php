<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const TRANSLATION_SERVICE_NO       = 0;
    const TRANSLATION_SERVICE_YES_TO   = 1;
    const TRANSLATION_SERVICE_YES_FROM = 2;
    const TRANSLATION_SERVICE_YES_BOTH = 3;

    private $info = NULL;

    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFacory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->moduleConfig = $moduleConfig;
        parent::__construct(
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFacory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
        return parent::delete();
    }

    //########################################

    public function getEbayItems($asObjects = false, array $filters = array())
    {
        return $this->getRelatedSimpleItems('Ebay\Item','marketplace_id',$asObjects,$filters);
    }

    //########################################

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function getOriginCountry()
    {
        return $this->getData('origin_country');
    }

    public function getLanguageCode()
    {
        return $this->getData('language_code');
    }

    /**
     * @return int
     */
    public function getTranslationServiceMode()
    {
        return (int)$this->getData('translation_service_mode');
    }

    /**
     * @return bool
     */
    public function isTranslationServiceMode()
    {
        return $this->getTranslationServiceMode() != self::TRANSLATION_SERVICE_NO;
    }

    /**
     * @return bool
     */
    public function isTranslationServiceModeTo()
    {
        return $this->getTranslationServiceMode() == self::TRANSLATION_SERVICE_YES_TO;
    }

    /**
     * @return bool
     */
    public function isTranslationServiceModeFrom()
    {
        return $this->getTranslationServiceMode() == self::TRANSLATION_SERVICE_YES_FROM;
    }

    /**
     * @return bool
     */
    public function isTranslationServiceModeBoth()
    {
        return $this->getTranslationServiceMode() == self::TRANSLATION_SERVICE_YES_BOTH;
    }

    /**
     * @return bool
     */
    public function isMultivariationEnabled()
    {
        return (bool)$this->getData('is_multivariation');
    }

    /**
     * @return bool
     */
    public function isTaxTableEnabled()
    {
        return (bool)(int)$this->getData('is_tax_table');
    }

    /**
     * @return bool
     */
    public function isVatEnabled()
    {
        return (bool)(int)$this->getData('is_vat');
    }

    /**
     * @return bool
     */
    public function isStpEnabled()
    {
        return (bool)(int)$this->getData('is_stp');
    }

    /**
     * @return bool
     */
    public function isStpAdvancedEnabled()
    {
        return (bool)(int)$this->getData('is_stp_advanced');
    }

    /**
     * @return bool
     */
    public function isMapEnabled()
    {
        return (bool)(int)$this->getData('is_map');
    }

    /**
     * @return bool
     */
    public function isLocalShippingRateTableEnabled()
    {
        return (bool)(int)$this->getData('is_local_shipping_rate_table');
    }

    /**
     * @return bool
     */
    public function isInternationalShippingRateTableEnabled()
    {
        return (bool)(int)$this->getData('is_international_shipping_rate_table');
    }

    /**
     * @return bool
     */
    public function isEnglishMeasurementSystemEnabled()
    {
        return (bool)(int)$this->getData('is_english_measurement_system');
    }

    /**
     * @return bool
     */
    public function isMetricMeasurementSystemEnabled()
    {
        return (bool)(int)$this->getData('is_metric_measurement_system');
    }

    /**
     * @return bool
     */
    public function isCashOnDeliveryEnabled()
    {
        return (bool)(int)$this->getData('is_cash_on_delivery');
    }

    /**
     * @return bool
     */
    public function isFreightShippingEnabled()
    {
        return (bool)(int)$this->getData('is_freight_shipping');
    }

    /**
     * @return bool
     */
    public function isCalculatedShippingEnabled()
    {
        return (bool)(int)$this->getData('is_calculated_shipping');
    }

    /**
     * @return bool
     */
    public function isGlobalShippingProgramEnabled()
    {
        return (bool)(int)$this->getData('is_global_shipping_program');
    }

    /**
     * @return bool
     */
    public function isCharityEnabled()
    {
        return (bool)(int)$this->getData('is_charity');
    }

    /**
     * @return bool
     */
    public function isClickAndCollectEnabled()
    {
        return (bool)(int)$this->getData('is_click_and_collect');
    }

    /**
     * @return bool
     */
    public function isInStorePickupEnabled()
    {
        return (bool)(int)$this->getData('is_in_store_pickup');
    }

    /**
     * @return bool
     */
    public function isHolidayReturnEnabled()
    {
        return (bool)(int)$this->getData('is_holiday_return');
    }

    /**
     * @return bool
     */
    public function isEpidEnabled()
    {
        return (bool)(int)$this->getData('is_epid');
    }

    /**
     * @return bool
     */
    public function isKtypeEnabled()
    {
        return (bool)(int)$this->getData('is_ktype');
    }

    /**
     * @return bool
     */
    public function isMultiMotorsEnabled()
    {
        return $this->isEpidEnabled() && $this->isKtypeEnabled();
    }

    //########################################

    /**
     * @param int $categoryId
     * @return array
     */
    public function getCategory($categoryId)
    {
        $connection = $this->getResource()->getConnection();

        $tableCategories = $this->getResource()->getTable('m2epro_ebay_dictionary_category');

        $dbSelect = $connection->select()
            ->from($tableCategories,'*')
            ->where('`marketplace_id` = ?',(int)$this->getId())
            ->where('`category_id` = ?',(int)$categoryId);

        $categories = $connection->fetchAll($dbSelect);

        return count($categories) > 0 ? $categories[0] : array();
    }

    public function getChildCategories($parentId)
    {
        $connection = $this->getResource()->getConnection();

        $tableCategories = $this->getResource()->getTable('m2epro_ebay_dictionary_category');

        $dbSelect = $connection->select()
            ->from($tableCategories,array('category_id','title','is_leaf'))
            ->where('`marketplace_id` = ?',(int)$this->getId())
            ->order(array('title ASC'));

        empty($parentId) ? $dbSelect->where('parent_category_id IS NULL')
                         : $dbSelect->where('parent_category_id = ?', (int)$parentId);

        $categories = $connection->fetchAll($dbSelect);

        return $categories;
    }

    //########################################

    /**
     * @return array|null
     */
    public function getInfo()
    {
        if (!is_null($this->info)) {
            return $this->info;
        }

        $connection = $this->getResource()->getConnection();

        $tableDictMarketplace = $this->getResource()->getTable('m2epro_ebay_dictionary_marketplace');
        $tableDictShipping = $this->getResource()->getTable('m2epro_ebay_dictionary_shipping');

        // table m2epro_ebay_dictionary_marketplace
        // ---------------------------------------
        $dbSelect = $connection->select()
                             ->from($tableDictMarketplace,'*')
                             ->where('`marketplace_id` = ?',(int)$this->getId());
        $data = $connection->fetchRow($dbSelect);
        // ---------------------------------------

        if (!$data) {
            return $this->info = array();
        }

        // table m2epro_ebay_dictionary_shipping
        // ---------------------------------------
        $dbSelect = $connection->select()
                             ->from($tableDictShipping,'*')
                             ->where('`marketplace_id` = ?',(int)$this->getId())
                             ->order(array('title ASC'));
        $shippingMethods = $connection->fetchAll($dbSelect);
        // ---------------------------------------

        if (!$shippingMethods) {
            $shippingMethods = array();
        }

        $categoryShippingMethods = array();
        foreach ($shippingMethods as $shippingMethod) {

            $category = $this->getHelper('Data')->jsonDecode($shippingMethod['category']);

            if (empty($category)) {
                $shippingMethod['data'] = $this->getHelper('Data')->jsonDecode($shippingMethod['data']);
                $categoryShippingMethods['']['methods'][] = $shippingMethod;
                continue;
            }

            if (!isset($categoryShippingMethods[$category['ebay_id']])) {
                $categoryShippingMethods[$category['ebay_id']] = array(
                    'title'   => $category['title'],
                    'methods' => array(),
                );
            }

            $shippingMethod['data'] = $this->getHelper('Data')->jsonDecode($shippingMethod['data']);
            $categoryShippingMethods[$category['ebay_id']]['methods'][] = $shippingMethod;
        }

        // ---------------------------------------

        return $this->info = array(
            'dispatch'                   => $this->getHelper('Data')->jsonDecode($data['dispatch']),
            'packages'                   => $this->getHelper('Data')->jsonDecode($data['packages']),
            'return_policy'              => $this->getHelper('Data')->jsonDecode($data['return_policy']),
            'listing_features'           => $this->getHelper('Data')->jsonDecode($data['listing_features']),
            'payments'                   => $this->getHelper('Data')->jsonDecode($data['payments']),
            'charities'                  => $this->getHelper('Data')->jsonDecode($data['charities']),
            'shipping'                   => $categoryShippingMethods,
            'shipping_locations'         => $this->getHelper('Data')->jsonDecode($data['shipping_locations']),
            'shipping_locations_exclude' => $this->getHelper('Data')->jsonDecode($data['shipping_locations_exclude']),
            'tax_categories'             => $this->getHelper('Data')->jsonDecode($data['tax_categories'])
        );
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDispatchInfo()
    {
        $info = $this->getInfo();
        return isset($info['dispatch']) ? $info['dispatch'] : array();
    }

    /**
     * @return array
     */
    public function getPackageInfo()
    {
        $info = $this->getInfo();
        return isset($info['packages']) ? $info['packages'] : array();
    }

    /**
     * @return array
     */
    public function getReturnPolicyInfo()
    {
        $info = $this->getInfo();
        return isset($info['return_policy']) ? $info['return_policy'] : array();
    }

    /**
     * @return array
     */
    public function getListingFeatureInfo()
    {
        $info = $this->getInfo();
        return isset($info['listing_features']) ? $info['listing_features'] : array();
    }

    /**
     * @return array
     */
    public function getPaymentInfo()
    {
        $info = $this->getInfo();
        return isset($info['payments']) ? $info['payments'] : array();
    }

    /**
     * @return array
     */
    public function getShippingInfo()
    {
        $info = $this->getInfo();
        return isset($info['shipping']) ? $info['shipping'] : array();
    }

    /**
     * @return array
     */
    public function getShippingLocationInfo()
    {
        $info = $this->getInfo();
        return isset($info['shipping_locations']) ? $info['shipping_locations'] : array();
    }

    /**
     * @return array
     */
    public function getShippingLocationExcludeInfo()
    {
        $info = $this->getInfo();
        return isset($info['shipping_locations_exclude']) ? $info['shipping_locations_exclude'] : array();
    }

    /**
     * @return array
     */
    public function getTaxCategoryInfo()
    {
        $info = $this->getInfo();
        return isset($info['tax_categories']) ? $info['tax_categories'] : array();
    }

    /**
     * @return array
     */
    public function getCharitiesInfo()
    {
        $info = $this->getInfo();
        return isset($info['charities']) ? $info['charities'] : array();
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}