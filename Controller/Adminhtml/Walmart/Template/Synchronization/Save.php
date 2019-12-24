<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Walmart\Template\Synchronization as SynchronizationPolicy;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Synchronization\Save
 */
class Save extends Template
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: list
        // ---------------------------------------
        $keys = [
            'title',
            'list_mode',
            'list_status_enabled',
            'list_is_in_stock',
            'list_qty_magento',
            'list_qty_magento_value',
            'list_qty_magento_value_max',
            'list_qty_calculated',
            'list_qty_calculated_value',
            'list_qty_calculated_value_max',
            'list_advanced_rules_mode'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['title'] = strip_tags($data['title']);
        $data['list_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX
        );

        // ---------------------------------------

        // tab: revise
        // ---------------------------------------
        $keys = [
            'revise_update_qty',
            'revise_update_qty_max_applied_value_mode',
            'revise_update_qty_max_applied_value',
            'revise_update_price',
            'revise_update_price_max_allowed_deviation_mode',
            'revise_update_price_max_allowed_deviation',
            'revise_update_promotions',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: relist
        // ---------------------------------------
        $keys = [
            'relist_mode',
            'relist_filter_user_lock',
            'relist_status_enabled',
            'relist_is_in_stock',
            'relist_qty_magento',
            'relist_qty_magento_value',
            'relist_qty_magento_value_max',
            'relist_qty_calculated',
            'relist_qty_calculated_value',
            'relist_qty_calculated_value_max',
            'relist_advanced_rules_mode'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['relist_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX
        );
        // ---------------------------------------

        // tab: stop
        // ---------------------------------------
        $keys = [
            'stop_mode',
            'stop_status_disabled',
            'stop_out_off_stock',
            'stop_qty_magento',
            'stop_qty_magento_value',
            'stop_qty_magento_value_max',
            'stop_qty_calculated',
            'stop_qty_calculated_value',
            'stop_qty_calculated_value_max',
            'stop_advanced_rules_mode'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['stop_advanced_rules_filters'] = $this->getRuleData(
            SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX
        );
        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->walmartFactory->getObject('Template\Synchronization');

        if ($id) {
            $model->load($id);
        }

        $model->addData($data)->save();
        $model->getChildObject()->addData(array_merge(
            [$model->getResource()->getChildPrimary(Walmart::NICK) => $model->getId()],
            $data
        ));

        $model->save();

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $model->getId();
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/walmart_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }

    private function getRuleData($rulePrefix)
    {
        $postData = $this->getRequest()->getPost()->toArray();

        if (empty($postData['rule'][$rulePrefix])) {
            return null;
        }

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => $rulePrefix]
        );

        return $ruleModel->getSerializedFromPost($postData);
    }
}
