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
        $data = array();
        // ---------------------------------------

        // tab: list
        // ---------------------------------------
        $keys = array(
            'title',
            'list_mode',
            'list_status_enabled',
            'list_is_in_stock',
            'list_qty_magento',
            'list_qty_magento_value',
            'list_qty_magento_value_max',
            'list_qty_calculated',
            'list_qty_calculated_value',
            'list_qty_calculated_value_max'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['title'] = strip_tags($data['title']);

        // ---------------------------------------

        // tab: revise
        // ---------------------------------------
        $keys = array(
            'revise_update_qty',
            'revise_update_qty_max_applied_value_mode',
            'revise_update_qty_max_applied_value',
            'revise_update_price',
            'revise_update_price_max_allowed_deviation_mode',
            'revise_update_price_max_allowed_deviation',
            'revise_update_promotions',
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: relist
        // ---------------------------------------
        $keys = array(
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
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: stop
        // ---------------------------------------
        $keys = array(
            'stop_mode',
            'stop_status_disabled',
            'stop_out_off_stock',
            'stop_qty_magento',
            'stop_qty_magento_value',
            'stop_qty_magento_value_max',
            'stop_qty_calculated',
            'stop_qty_calculated_value',
            'stop_qty_calculated_value_max'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
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
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/walmart_template/index', array(), array(
            'edit' => array(
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ),
        )));
    }
}