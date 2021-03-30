<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping;

use Ess\M2ePro\Model\Ebay\Template\Shipping as Shipping;
use Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated as ShippingCalculated;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Shipping\Builder
 */
class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($activeRecordFactory, $ebayFactory, $helperFactory, $modelFactory);
    }

    //########################################

    public function build($model, array $rawData)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping $model */
        $model = parent::build($model, $rawData);

        if ($this->canSaveCalculatedData()) {
            $calculatedData = $this->prepareCalculatedData($model->getId());
            $this->createCalculated($model->getId(), $calculatedData);
        }

        $servicesData = $this->prepareServicesData($model->getId());
        $this->createServices($model->getId(), $servicesData);

        return $model;
    }

    //########################################

    protected function validate()
    {
        if (empty($this->rawData['marketplace_id'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace ID is empty.');
        }

        if ($this->rawData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_VALUE &&
            empty($this->rawData['country_custom_value']) ||
            $this->rawData['country_mode'] == Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE &&
            empty($this->rawData['country_custom_attribute'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Country is empty.');
        }

        parent::validate();
    }

    //########################################

    protected function prepareData()
    {
        $this->validate();

        $data = parent::prepareData();

        $data['marketplace_id'] = (int)$this->rawData['marketplace_id'];

        $keys = [
            'country_mode',
            'country_custom_value',
            'country_custom_attribute',
            'postal_code_mode',
            'postal_code_custom_attribute',
            'postal_code_custom_value',
            'address_mode',
            'address_custom_attribute',
            'address_custom_value',
            'dispatch_time_mode',
            'dispatch_time_value',
            'dispatch_time_attribute',
            'global_shipping_program',
            'local_shipping_mode',
            'local_shipping_discount_promotional_mode',
            'international_shipping_mode',
            'international_shipping_discount_promotional_mode',
            'cross_border_trade',
        ];

        foreach ($keys as $key) {
            $data[$key] = isset($this->rawData[$key]) ? $this->rawData[$key] : '';
        }

        if (isset($this->rawData['local_shipping_rate_table'])) {
            $data['local_shipping_rate_table'] = $this->getHelper('Data')->jsonEncode(
                $this->rawData['local_shipping_rate_table']
            );
        } else {
            $data['local_shipping_rate_table'] = $this->getHelper('Data')->jsonEncode([]);
        }

        if (isset($this->rawData['international_shipping_rate_table'])) {
            $data['international_shipping_rate_table'] = $this->getHelper('Data')->jsonEncode(
                $this->rawData['international_shipping_rate_table']
            );
        } else {
            $data['international_shipping_rate_table'] = $this->getHelper('Data')->jsonEncode([]);
        }

        if (isset($this->rawData['local_shipping_discount_combined_profile_id'])) {
            $data['local_shipping_discount_combined_profile_id'] = $this->getHelper('Data')->jsonEncode(
                array_diff($this->rawData['local_shipping_discount_combined_profile_id'], [''])
            );
        } else {
            $data['local_shipping_discount_combined_profile_id'] = $this->getHelper('Data')->jsonEncode([]);
        }

        if (isset($this->rawData['international_shipping_discount_combined_profile_id'])) {
            $data['international_shipping_discount_combined_profile_id'] = $this->getHelper('Data')->jsonEncode(
                array_diff($this->rawData['international_shipping_discount_combined_profile_id'], [''])
            );
        } else {
            $data['international_shipping_discount_combined_profile_id']
                = $this->getHelper('Data')->jsonEncode([]);
        }

        if (isset($this->rawData['excluded_locations'])) {
            $data['excluded_locations'] = $this->rawData['excluded_locations'];
        }

        $key = 'cash_on_delivery_cost';
        $data[$key] = (isset($this->rawData[$key]) && $this->rawData[$key] != '') ? $this->rawData[$key] : null;

        $modes = [
            'local_shipping_mode',
            'local_shipping_discount_promotional_mode',
            'international_shipping_mode',
            'international_shipping_discount_promotional_mode',
            'cross_border_trade'
        ];

        foreach ($modes as $mode) {
            $data[$mode] = (int)$data[$mode];
        }

        return $data;
    }

    //########################################

    protected function prepareCalculatedData($templateShippingId)
    {
        $data = ['template_shipping_id' => $templateShippingId];

        $keys = [
            'measurement_system',

            'package_size_mode',
            'package_size_value',
            'package_size_attribute',

            'dimension_mode',
            'dimension_width_value',
            'dimension_length_value',
            'dimension_depth_value',
            'dimension_width_attribute',
            'dimension_length_attribute',
            'dimension_depth_attribute',

            'weight_mode',
            'weight_minor',
            'weight_major',
            'weight_attribute'
        ];

        foreach ($keys as $key) {
            $data[$key] = isset($this->rawData[$key]) ? $this->rawData[$key] : '';
        }

        $nullKeys = [
            'local_handling_cost',
            'international_handling_cost'
        ];

        foreach ($nullKeys as $key) {
            $data[$key] = (isset($this->rawData[$key]) && $this->rawData[$key] != '') ? $this->rawData[$key] : null;
        }

        return $data;
    }

    protected function canSaveCalculatedData()
    {
        if ($this->rawData['local_shipping_mode'] == Shipping::SHIPPING_TYPE_LOCAL ||
            $this->rawData['local_shipping_mode'] == Shipping::SHIPPING_TYPE_FREIGHT) {
            return false;
        }

        return true;
    }

    protected function createCalculated($templateShippingId, array $data)
    {
        $connection = $this->resourceConnection->getConnection();

        $connection->delete(
            $this->activeRecordFactory->getObject('Ebay_Template_Shipping_Calculated')->getResource()->getMainTable(),
            [
                'template_shipping_id = ?' => (int)$templateShippingId
            ]
        );

        if (empty($data)) {
            return;
        }

        $this->activeRecordFactory->getObject('Ebay_Template_Shipping_Calculated')->setData($data)->save();
    }

    //########################################

    protected function prepareServicesData($templateShippingId)
    {
        if (isset($this->rawData['shipping_type']['%i%'])) {
            unset($this->rawData['shipping_type']['%i%']);
        }

        if (isset($this->rawData['cost_mode']['%i%'])) {
            unset($this->rawData['cost_mode']['%i%']);
        }

        if (isset($this->rawData['shipping_priority']['%i%'])) {
            unset($this->rawData['shipping_priority']['%i%']);
        }

        if (isset($this->rawData['shipping_cost_value']['%i%'])) {
            unset($this->rawData['shipping_cost_value']['%i%']);
        }

        if (isset($this->rawData['shipping_cost_additional_value']['%i%'])) {
            unset($this->rawData['shipping_cost_additional_value']['%i%']);
        }

        // ---------------------------------------

        $services = [];
        foreach ($this->rawData['cost_mode'] as $i => $costMode) {
            $locations = [];
            if (isset($this->rawData['shippingLocation'][$i])) {
                foreach ($this->rawData['shippingLocation'][$i] as $location) {
                    $locations[] = $location;
                }
            }

            $shippingType = $this->rawData['shipping_type'][$i] == 'local'
                ? \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::SHIPPING_TYPE_LOCAL
                : \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::SHIPPING_TYPE_INTERNATIONAL;

            if ($costMode == \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE) {
                $cost = isset($this->rawData['shipping_cost_attribute'][$i])
                    ? $this->rawData['shipping_cost_attribute'][$i]
                    : '';

                $costAdditional = isset($this->rawData['shipping_cost_additional_attribute'][$i])
                    ? $this->rawData['shipping_cost_additional_attribute'][$i]
                    : '';
            } else {
                $cost = isset($this->rawData['shipping_cost_value'][$i])
                    ? $this->rawData['shipping_cost_value'][$i]
                    : '';

                $costAdditional = isset($this->rawData['shipping_cost_additional_value'][$i])
                    ? $this->rawData['shipping_cost_additional_value'][$i]
                    : '';
            }

            $services[] = [
                'template_shipping_id'  => $templateShippingId,
                'cost_mode'             => $costMode,
                'cost_value'            => $cost,
                'shipping_value'        => $this->rawData['shipping_service'][$i],
                'shipping_type'         => $shippingType,
                'cost_additional_value' => $costAdditional,
                'priority'              => $this->rawData['shipping_priority'][$i],
                'locations'             => $this->getHelper('Data')->jsonEncode($locations)
            ];
        }

        return $services;
    }

    private function createServices($templateShippingId, array $data)
    {
        $connection = $this->resourceConnection->getConnection();
        $etssTable = $this->activeRecordFactory->getObject('Ebay_Template_Shipping_Service')
            ->getResource()->getMainTable();

        $connection->delete(
            $etssTable,
            [
                'template_shipping_id = ?' => (int)$templateShippingId
            ]
        );

        if (empty($data)) {
            return;
        }

        $connection->insertMultiple(
            $etssTable,
            $data
        );
    }

    //########################################

    protected function isRateTableEnabled(array $rateTableData)
    {
        if (empty($rateTableData)) {
            return false;
        }

        foreach ($rateTableData as $data) {
            if (!empty($data['value'])) {
                return true;
            }
        }

        return false;
    }

    //########################################

    public function getDefaultData()
    {
        return [
            'country_mode' => Shipping::COUNTRY_MODE_CUSTOM_VALUE,
            'country_custom_value' => 'US',
            'country_custom_attribute' => '',
            'postal_code_mode' => Shipping::POSTAL_CODE_MODE_NONE,
            'postal_code_custom_value' => '',
            'postal_code_custom_attribute' => '',
            'address_mode' => Shipping::ADDRESS_MODE_NONE,
            'address_custom_value' => '',
            'address_custom_attribute' => '',

            'dispatch_time_mode' => Shipping::DISPATCH_TIME_MODE_VALUE,
            'dispatch_time_value' => 1,
            'dispatch_time_attribute' => '',
            'cash_on_delivery_cost' => null,
            'global_shipping_program' => 0,
            'cross_border_trade' => Shipping::CROSS_BORDER_TRADE_NONE,
            'excluded_locations' => $this->getHelper('Data')->jsonEncode([]),

            'local_shipping_mode' =>  Shipping::SHIPPING_TYPE_FLAT,
            'local_shipping_discount_promotional_mode' => 0,
            'local_shipping_discount_combined_profile_id' => $this->getHelper('Data')->jsonEncode([]),
            'local_shipping_rate_table_mode' => 0,
            'local_shipping_rate_table' => null,

            'international_shipping_mode' => Shipping::SHIPPING_TYPE_NO_INTERNATIONAL,
            'international_shipping_discount_promotional_mode' => 0,
            'international_shipping_discount_combined_profile_id' => $this->getHelper('Data')->jsonEncode([]),
            'international_shipping_rate_table_mode' => 0,
            'international_shipping_rate_table' => null,

            // CALCULATED SHIPPING
            // ---------------------------------------
            'measurement_system' => ShippingCalculated::MEASUREMENT_SYSTEM_ENGLISH,

            'package_size_mode' => ShippingCalculated::PACKAGE_SIZE_NONE,
            'package_size_value' => '',
            'package_size_attribute' => '',

            'dimension_mode'   => ShippingCalculated::DIMENSION_NONE,
            'dimension_width_value'  => '',
            'dimension_length_value' => '',
            'dimension_depth_value'  => '',
            'dimension_width_attribute'  => '',
            'dimension_length_attribute' => '',
            'dimension_depth_attribute'  => '',

            'weight_mode' => ShippingCalculated::WEIGHT_NONE,
            'weight_minor' => '',
            'weight_major' => '',
            'weight_attribute' => '',

            'local_handling_cost' => null,
            'international_handling_cost' => null,
            // ---------------------------------------

            'services' => []
        ];
    }

    //########################################
}
