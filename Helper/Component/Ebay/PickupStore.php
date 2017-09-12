<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay;

class PickupStore extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $activeRecordFactory;
    protected $messageManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->messageManager = $messageManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isFeatureEnabled()
    {
        return (int)$this->modelFactory->getObject('Config\Manager\Module')
                                       ->getGroupValue('/ebay/in_store_pickup/', 'mode');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getEnabledAccounts()
    {
        /** @var \Ess\M2ePro\Model\Account[] $accounts */
        $accounts = $this->activeRecordFactory->getObject('Account')->getCollection()
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        $enabledAccounts = [];
        foreach ($accounts as $account) {
            if ($account->getChildObject()->isPickupStoreEnabled()) {
                $enabledAccounts[] = $account;
            }
        }

        return $enabledAccounts;
    }

    //########################################

    public function convertMarketplaceToCountry($marketplace)
    {
        $countries = $this->getHelper('Magento')->getCountries();

        foreach ($countries as $country) {
            if (!empty($country['value']) &&
                $country['value'] == strtoupper($marketplace['origin_country'])
            ) {
                return $country;
            }
        }

        return false;
    }

    //########################################

    public function createPickupStore($data, $accountId)
    {
        if (!$this->validateRequiredFields($data)) {
            $this->messageManager->addErrorMessage(
                $this->getHelper('Module\Translation')->__('Validation error. You must fill all required fields.')
            );
            return false;
        }

        try {

            $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'store','add','entity', $this->prepareData($data),
                NULL, NULL, $accountId
            );

            $dispatcherObject->process($connectorObj);

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);
            $error = 'The New Store has not been created. <br/>Reason: %error_message%';
            $error = $this->getHelper('Module\Translation')->__($error, $exception->getMessage());

            $this->messageManager->addErrorMessage($error);

            return false;
        }

        return true;
    }

    public function deletePickupStore($locationId, $accountId)
    {
        try {

            $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'store', 'delete', 'entity', array(
                    'location_id' => $locationId
                ), NULL, NULL, $accountId
            );

            $dispatcherObject->process($connectorObj);

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);
            $error = 'The Store has not been deleted. Reason: %error_message%';
            $error = $this->getHelper('Module\Translation')->__($error, $exception->getMessage());

            $this->messageManager->addErrorMessage($error);

            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function validateRequiredFields(array $data)
    {
        $requiredFields = array(
            'name', 'location_id', 'account_id', 'marketplace_id',
            'phone', 'postal_code', 'utc_offset',
            'country', 'region', 'city', 'address_1',
            'business_hours'
        );

        foreach ($requiredFields as $requiredField) {
            if (empty($data[$requiredField])) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------

    protected function prepareData(array $data)
    {
        $requestData = array();
        $requestData['location_id'] = $data['location_id'];
        $requestData['location'] = $this->getLocationData($data);
        $requestData['info'] = $this->getInfoData($data);
        $requestData['working'] = $this->getWorkingHoursData($data);

        return $requestData;
    }

    protected function getLocationData(array $data)
    {
        $physical = array(
            'country' => $data['country'],
            'city' => $data['city'],
            'region' => $data['region'],
            'postal_code' => $data['postal_code'],
            'address_1' => $data['address_1']
        );

        if (!empty($data['address_second'])) {
            $physical['address_2'] = $data['address_2'];
        }

        $geoData = array(
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'utc_offset' => $data['utc_offset']
        );

        return array('physical' => $physical, 'geo_data' => $geoData);
    }

    protected function getInfoData(array $data)
    {
        $info = array();

        $info['name'] = $data['name'];
        $info['phone'] = $data['phone'];

        if (!empty($data['url'])) {
            $info['url'] = $data['url'];
        }

        return $info;
    }

    protected function getWorkingHoursData(array $data)
    {
        $weekHours = $this->getHelper('Data')->jsonDecode($data['business_hours']);
        $weekValues = array(
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7
        );

        $parsedWeekHours = array();
        foreach ($weekHours['week_days'] as $weekDay) {
            if (!isset($weekHours['week_settings'][$weekDay])) {
                continue;
            }

            $parsedWeekHours[$weekValues[$weekDay]] = $weekHours['week_settings'][$weekDay];
        }

        $holidaysHours = $this->getHelper('Data')->jsonDecode($data['special_hours']);
        return array(
            'week' => $parsedWeekHours,
            'holidays' => $holidaysHours['date_settings']
        );
    }

    //########################################
}