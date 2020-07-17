<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Synchronization;

use Ess\M2ePro\Model\Template\Synchronization as Synchronization;

/**
 * Class Ess\M2ePro\Model\Amazon\Template\Synchronization\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = array_keys($this->getDefaultData());

        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        $data['title'] = strip_tags($data['title']);

        $data['list_advanced_rules_filters'] = $this->getRuleData(
            \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_ADVANCED_RULES_PREFIX,
            $this->rawData
        );

        $data['relist_advanced_rules_filters'] = $this->getRuleData(
            \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_ADVANCED_RULES_PREFIX,
            $this->rawData
        );

        $data['revise_update_qty'] = 1;

        $data['stop_advanced_rules_filters'] = $this->getRuleData(
            \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX,
            $this->rawData
        );

        return $data;
    }

    protected function getRuleData($rulePrefix, $post)
    {
        if (empty($post['rule'][$rulePrefix])) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => $rulePrefix]
        );

        return $ruleModel->getSerializedFromPost($post);
    }

    public function getDefaultData()
    {
        return [
            'title'               => '',

            // list
            'list_mode'           => 1,
            'list_status_enabled' => 1,
            'list_is_in_stock'    => 1,

            'list_qty_calculated'       => Synchronization::QTY_MODE_YES,
            'list_qty_calculated_value' => '1',

            'list_advanced_rules_mode' => 0,
            'list_advanced_rules_filters' => null,

            // relist
            'relist_mode'              => 1,
            'relist_filter_user_lock'  => 1,
            'relist_status_enabled'    => 1,
            'relist_is_in_stock'       => 1,

            'relist_qty_calculated'       => Synchronization::QTY_MODE_YES,
            'relist_qty_calculated_value' => '1',

            'relist_advanced_rules_mode'               => 0,
            'relist_advanced_rules_filters' => null,

            // revise
            'revise_update_qty'                        => 1,
            'revise_update_qty_max_applied_value_mode' => 1,
            'revise_update_qty_max_applied_value'      => 5,
            'revise_update_price'                      => 1,
            'revise_update_details'                    => 0,
            'revise_update_images'                     => 0,

            // stop
            'stop_mode'                                => 1,

            'stop_status_disabled' => 1,
            'stop_out_off_stock'   => 1,

            'stop_qty_calculated'       => Synchronization::QTY_MODE_YES,
            'stop_qty_calculated_value' => '0',

            'stop_advanced_rules_mode' => 0,
            'stop_advanced_rules_filters' => null
        ];
    }

    //########################################
}
