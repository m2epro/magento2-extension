<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Synchronization;

use Ess\M2ePro\Model\Ebay\Template\Synchronization as SynchronizationPolicy;

class Builder extends \Ess\M2ePro\Model\Ebay\Template\Builder\AbstractModel
{
    /** @var \Magento\Framework\App\RequestInterface */
    protected $request;

    //########################################

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ){
        parent::__construct($activeRecordFactory, $ebayFactory, $helperFactory, $modelFactory);
        $this->request = $request;
    }

    //########################################

    public function build(array $data)
    {
        if (empty($data)) {
            return NULL;
        }

        $this->validate($data);

        $data = $this->prepareData($data);

        $template = $this->ebayFactory->getObject('Template\Synchronization');

        if (isset($data['id'])) {
            $template->load($data['id']);
            $template->addData($data);
            $template->getChildObject()->addData($data);
        } else {
            $template->setData($data);
        }

        $template->save();

        return $template;
    }

    //########################################

    protected function prepareData(array &$data)
    {
        $prepared = parent::prepareData($data);

        $defaultData = $this->activeRecordFactory->getObject('Ebay\Template\Synchronization')->getDefaultSettings();

        $data = array_replace_recursive($defaultData, $data);

        $prepared = array_merge(
            $prepared,
            $this->prepareListData($data),
            $this->prepareReviseData($data),
            $this->prepareRelistData($data),
            $this->prepareStopData($data)
        );

        return $prepared;
    }

    // ---------------------------------------

    private function prepareListData(array $data)
    {
        $prepared = array();

        if (isset($data['list_mode'])) {
            $prepared['list_mode'] = (int)$data['list_mode'];
        }

        if (isset($data['list_status_enabled'])) {
            $prepared['list_status_enabled'] = (int)$data['list_status_enabled'];
        }

        if (isset($data['list_is_in_stock'])) {
            $prepared['list_is_in_stock'] = (int)$data['list_is_in_stock'];
        }

        if (isset($data['list_qty_magento'])) {
            $prepared['list_qty_magento'] = (int)$data['list_qty_magento'];
        }

        if (isset($data['list_qty_magento_value'])) {
            $prepared['list_qty_magento_value'] = (int)$data['list_qty_magento_value'];
        }

        if (isset($data['list_qty_magento_value_max'])) {
            $prepared['list_qty_magento_value_max'] = (int)$data['list_qty_magento_value_max'];
        }

        if (isset($data['list_qty_calculated'])) {
            $prepared['list_qty_calculated'] = (int)$data['list_qty_calculated'];
        }

        if (isset($data['list_qty_calculated_value'])) {
            $prepared['list_qty_calculated_value'] = (int)$data['list_qty_calculated_value'];
        }

        if (isset($data['list_qty_calculated_value_max'])) {
            $prepared['list_qty_calculated_value_max'] = (int)$data['list_qty_calculated_value_max'];
        }

        // ---------------------------------------

        if (isset($data['list_advanced_rules_mode'])) {
            $prepared['list_advanced_rules_mode'] = (int)$data['list_advanced_rules_mode'];
        }

        $prepared['list_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX
        );

        return $prepared;
    }

    private function prepareReviseData(array $data)
    {
        $prepared = array();

        if (isset($data['revise_update_qty'])) {
            $prepared['revise_update_qty'] = (int)$data['revise_update_qty'];
        }

        $key = 'revise_update_qty_max_applied_value_mode';
        if (isset($data[$key])) {
            $prepared[$key] = (int)$data[$key];
        }

        if (isset($data['revise_update_qty_max_applied_value'])) {
            $prepared['revise_update_qty_max_applied_value'] = (int)$data['revise_update_qty_max_applied_value'];
        }

        if (isset($data['revise_update_price'])) {
            $prepared['revise_update_price'] = (int)$data['revise_update_price'];
        }

        $key = 'revise_update_price_max_allowed_deviation_mode';
        if (isset($data[$key])) {
            $prepared[$key] = (int)$data[$key];
        }

        $key = 'revise_update_price_max_allowed_deviation';
        if (isset($data[$key])) {
            $prepared[$key] = (int)$data[$key];
        }

        if (isset($data['revise_update_title'])) {
            $prepared['revise_update_title'] = (int)$data['revise_update_title'];
        }

        if (isset($data['revise_update_sub_title'])) {
            $prepared['revise_update_sub_title'] = (int)$data['revise_update_sub_title'];
        }

        if (isset($data['revise_update_description'])) {
            $prepared['revise_update_description'] = (int)$data['revise_update_description'];
        }

        if (isset($data['revise_update_images'])) {
            $prepared['revise_update_images'] = (int)$data['revise_update_images'];
        }

        if (isset($data['revise_update_specifics'])) {
            $prepared['revise_update_specifics'] = (int)$data['revise_update_specifics'];
        }

        if (isset($data['revise_update_shipping_services'])) {
            $prepared['revise_update_shipping_services'] = (int)$data['revise_update_shipping_services'];
        }

        // ---------------------------------------

        if (isset($data['revise_change_selling_format_template'])) {
            $prepared['revise_change_selling_format_template'] = (int)$data['revise_change_selling_format_template'];
        }

        if (isset($data['revise_change_description_template'])) {
            $prepared['revise_change_description_template'] = (int)$data['revise_change_description_template'];
        }

        if (isset($data['revise_change_category_template'])) {
            $prepared['revise_change_category_template'] = (int)$data['revise_change_category_template'];
        }

        if (isset($data['revise_change_payment_template'])) {
            $prepared['revise_change_payment_template'] = (int)$data['revise_change_payment_template'];
        }

        if (isset($data['revise_change_shipping_template'])) {
            $prepared['revise_change_shipping_template'] = (int)$data['revise_change_shipping_template'];
        }

        if (isset($data['revise_change_return_policy_template'])) {
            $prepared['revise_change_return_policy_template'] = (int)$data['revise_change_return_policy_template'];
        }

        return $prepared;
    }

    private function prepareRelistData(array $data)
    {
        $prepared = array();

        if (isset($data['relist_mode'])) {
            $prepared['relist_mode'] = (int)$data['relist_mode'];
        }

        if (isset($data['relist_filter_user_lock'])) {
            $prepared['relist_filter_user_lock'] = (int)$data['relist_filter_user_lock'];
        }

        if (isset($data['relist_send_data'])) {
            $prepared['relist_send_data'] = (int)$data['relist_send_data'];
        }

        if (isset($data['relist_status_enabled'])) {
            $prepared['relist_status_enabled'] = (int)$data['relist_status_enabled'];
        }

        if (isset($data['relist_is_in_stock'])) {
            $prepared['relist_is_in_stock'] = (int)$data['relist_is_in_stock'];
        }

        if (isset($data['relist_qty_magento'])) {
            $prepared['relist_qty_magento'] = (int)$data['relist_qty_magento'];
        }

        if (isset($data['relist_qty_magento_value'])) {
            $prepared['relist_qty_magento_value'] = (int)$data['relist_qty_magento_value'];
        }

        if (isset($data['relist_qty_magento_value_max'])) {
            $prepared['relist_qty_magento_value_max'] = (int)$data['relist_qty_magento_value_max'];
        }

        if (isset($data['relist_qty_calculated'])) {
            $prepared['relist_qty_calculated'] = (int)$data['relist_qty_calculated'];
        }

        if (isset($data['relist_qty_calculated_value'])) {
            $prepared['relist_qty_calculated_value'] = (int)$data['relist_qty_calculated_value'];
        }

        if (isset($data['relist_qty_calculated_value_max'])) {
            $prepared['relist_qty_calculated_value_max'] = (int)$data['relist_qty_calculated_value_max'];
        }

        // ---------------------------------------

        if (isset($data['relist_advanced_rules_mode'])) {
            $prepared['relist_advanced_rules_mode'] = (int)$data['relist_advanced_rules_mode'];
        }

        $prepared['relist_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX
        );

        return $prepared;
    }

    private function prepareStopData(array $data)
    {
        $prepared = array();

        if (isset($data['stop_status_disabled'])) {
            $prepared['stop_status_disabled'] = (int)$data['stop_status_disabled'];
        }

        if (isset($data['stop_out_off_stock'])) {
            $prepared['stop_out_off_stock'] = (int)$data['stop_out_off_stock'];
        }

        if (isset($data['stop_qty_magento'])) {
            $prepared['stop_qty_magento'] = (int)$data['stop_qty_magento'];
        }

        if (isset($data['stop_qty_magento_value'])) {
            $prepared['stop_qty_magento_value'] = (int)$data['stop_qty_magento_value'];
        }

        if (isset($data['stop_qty_magento_value_max'])) {
            $prepared['stop_qty_magento_value_max'] = (int)$data['stop_qty_magento_value_max'];
        }

        if (isset($data['stop_qty_calculated'])) {
            $prepared['stop_qty_calculated'] = (int)$data['stop_qty_calculated'];
        }

        if (isset($data['stop_qty_calculated_value'])) {
            $prepared['stop_qty_calculated_value'] = (int)$data['stop_qty_calculated_value'];
        }

        if (isset($data['stop_qty_calculated_value_max'])) {
            $prepared['stop_qty_calculated_value_max'] = (int)$data['stop_qty_calculated_value_max'];
        }

        // ---------------------------------------

        if (isset($data['stop_advanced_rules_mode'])) {
            $prepared['stop_advanced_rules_mode'] = (int)$data['stop_advanced_rules_mode'];
        }

        $prepared['stop_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX
        );

        return $prepared;
    }

    //########################################

    private function getRuleData($rulePrefix)
    {
        $postData = $this->request->getPost()->toArray();

        if (empty($postData['rule'][$rulePrefix])) {
            return null;
        }

        $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
            ['prefix' => $rulePrefix]
        );

        return $ruleModel->getSerializedFromPost($postData);
    }

    //########################################
}