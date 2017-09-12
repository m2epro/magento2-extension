<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Save extends Account
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = array();
        // ---------------------------------------

        // tab: general
        // ---------------------------------------
        $keys = array(
            'title',
            'marketplace_id',
            'merchant_id',
            'token',
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: 3rd party listings
        // ---------------------------------------
        $keys = array(
            'related_store_id',

            'other_listings_synchronization',
            'other_listings_mapping_mode',
            'other_listings_move_mode'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // Mapping
        // ---------------------------------------
        $tempData = array();
        $keys = array(
            'mapping_general_id_mode',
            'mapping_general_id_priority',
            'mapping_general_id_attribute',

            'mapping_sku_mode',
            'mapping_sku_priority',
            'mapping_sku_attribute',

            'mapping_title_mode',
            'mapping_title_priority',
            'mapping_title_attribute'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $tempData[$key] = $post[$key];
            }
        }

        $mappingSettings = array();

        $temp = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;
        if (isset($tempData['mapping_general_id_mode']) &&
            $tempData['mapping_general_id_mode'] == $temp) {
            $mappingSettings['general_id']['mode'] = (int)$tempData['mapping_general_id_mode'];
            $mappingSettings['general_id']['priority'] = (int)$tempData['mapping_general_id_priority'];
            $mappingSettings['general_id']['attribute'] = (string)$tempData['mapping_general_id_attribute'];
        }

        $temp1 = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT;
        $temp2 = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE;
        $temp3 = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID;
        if (isset($tempData['mapping_sku_mode']) &&
            ($tempData['mapping_sku_mode'] == $temp1 ||
                $tempData['mapping_sku_mode'] == $temp2 ||
                $tempData['mapping_sku_mode'] == $temp3)) {
            $mappingSettings['sku']['mode'] = (int)$tempData['mapping_sku_mode'];
            $mappingSettings['sku']['priority'] = (int)$tempData['mapping_sku_priority'];

            $temp = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE;
            if ($tempData['mapping_sku_mode'] == $temp) {
                $mappingSettings['sku']['attribute'] = (string)$tempData['mapping_sku_attribute'];
            }
        }

        $temp1 = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT;
        $temp2 = \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE;
        if (isset($tempData['mapping_title_mode']) &&
            ($tempData['mapping_title_mode'] == $temp1 ||
                $tempData['mapping_title_mode'] == $temp2)) {
            $mappingSettings['title']['mode'] = (int)$tempData['mapping_title_mode'];
            $mappingSettings['title']['priority'] = (int)$tempData['mapping_title_priority'];
            $mappingSettings['title']['attribute'] = (string)$tempData['mapping_title_attribute'];
        }

        $data['other_listings_mapping_settings'] = $this->getHelper('Data')->jsonEncode($mappingSettings);
        // ---------------------------------------

        // tab: orders
        // ---------------------------------------
        $data['magento_orders_settings'] = array();

        // m2e orders settings
        // ---------------------------------------
        $tempKey = 'listing';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'store_mode',
            'store_id'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // 3rd party orders settings
        // ---------------------------------------
        $tempKey = 'listing_other';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'product_mode',
            'product_tax_class_id',
            'store_id'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // order number settings
        // ---------------------------------------
        $tempKey = 'number';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $data['magento_orders_settings'][$tempKey]['source'] = $tempSettings['source'];

        $prefixKeys = array(
            'mode',
            'prefix',
        );
        $tempSettings = !empty($tempSettings['prefix']) ? $tempSettings['prefix'] : array();
        foreach ($prefixKeys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey]['prefix'][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // qty reservation
        // ---------------------------------------
        $tempKey = 'qty_reservation';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'days',
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // refund & cancellation
        // ---------------------------------------
        $tempKey = 'refund_and_cancellation';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'refund_mode',
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // fba
        // ---------------------------------------
        $tempKey = 'fba';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'stock_mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // tax settings
        // ---------------------------------------
        $tempKey = 'tax';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // customer settings
        // ---------------------------------------
        $tempKey = 'customer';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'id',
            'website_id',
            'group_id',
            'billing_address_mode',
//            'subscription_mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $notificationsKeys = array(
//            'customer_created',
            'order_created',
            'invoice_created'
        );
        $tempSettings = !empty($tempSettings['notifications']) ? $tempSettings['notifications'] : array();
        foreach ($notificationsKeys as $key) {
            if (in_array($key, $tempSettings)) {
                $data['magento_orders_settings'][$tempKey]['notifications'][$key] = true;
            }
        }
        // ---------------------------------------

        // status mapping settings
        // ---------------------------------------
        $tempKey = 'status_mapping';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'processing',
            'shipped'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // invoice/shipment settings
        // ---------------------------------------
        $temp = \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
        $data['magento_orders_settings']['invoice_mode'] = $temp;
        $temp = \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        $data['magento_orders_settings']['shipment_mode'] = $temp;

        $temp = \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM;
        if (!empty($data['magento_orders_settings']['status_mapping']['mode']) &&
            $data['magento_orders_settings']['status_mapping']['mode'] == $temp) {

            $temp = \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_INVOICE_MODE_NO;
            if (!isset($post['magento_orders_settings']['invoice_mode'])) {
                $data['magento_orders_settings']['invoice_mode'] = $temp;
            }
            $temp = \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_SHIPMENT_MODE_NO;
            if (!isset($post['magento_orders_settings']['shipment_mode'])) {
                $data['magento_orders_settings']['shipment_mode'] = $temp;
            }
        }
        // ---------------------------------------

        // ---------------------------------------
        $data['magento_orders_settings'] = $this->getHelper('Data')->jsonEncode($data['magento_orders_settings']);
        // ---------------------------------------

        $isEdit = !is_null($id);

        // tab: shipping settings
        // ---------------------------------------
        $keys = array(
            'shipping_mode'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: vat calculation service
        // ---------------------------------------
        $keys = array(
            'is_vat_calculation_service_enabled',
            'is_magento_invoice_creation_disabled',
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if (empty($data['is_vat_calculation_service_enabled'])) {
            $data['is_magento_invoice_creation_disabled'] = false;
        }
        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->amazonFactory->getObject('Account');
        if (is_null($id)) {
            $model->setData($data);
        } else {
            $model->load($id);
            $model->addData($data);
            $model->getChildObject()->addData($data);
        }

        $oldData = $model->getOrigData();
        if (!is_null($id)) {
            $oldData = array_merge($oldData, $model->getChildObject()->getOrigData());
        }
        $id = $model->save()->getId();
        // ---------------------------------------

        $model->getChildObject()->setSetting('other_listings_move_settings',
            array('synch'),
            $post['other_listings_move_synch']);
        $model->getChildObject()->save();

        // Repricing
        // ---------------------------------------
        if (!empty($post['repricing']) && $model->getChildObject()->isRepricing()) {

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $repricingModel */
            $repricingModel = $model->getChildObject()->getRepricing();

            $repricingOldData = $repricingModel->getData();

            $repricingModel->addData($post['repricing']);
            $repricingModel->save();

            $repricingNewData = $repricingModel->getData();

            $repricingModel->setProcessRequired($repricingNewData, $repricingOldData);
        }
        // ---------------------------------------

        try {

            // Add or update server
            // ---------------------------------------

            /** @var $accountObj \Ess\M2ePro\Model\Account */
            $accountObj = $model;

            if (!$accountObj->isSetProcessingLock('server_synchronize')) {

                /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
                $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');

                if (!$isEdit) {

                    $params = array(
                        'title'            => $post['title'],
                        'marketplace_id'   => (int)$post['marketplace_id'],
                        'merchant_id'      => $post['merchant_id'],
                        'token'            => $post['token'],
                        'related_store_id' => (int)$post['related_store_id']
                    );

                    $connectorObj = $dispatcherObject->getConnector('account', 'add' ,'entityRequester',
                        $params, $id);
                    $dispatcherObject->process($connectorObj);

                } else {

                    $newData = array(
                        'title'            => $post['title'],
                        'marketplace_id'   => (int)$post['marketplace_id'],
                        'merchant_id'      => $post['merchant_id'],
                        'token'            => $post['token'],
                        'related_store_id' => (int)$post['related_store_id']
                    );

                    $params = array_diff_assoc($newData, $oldData);

                    if (!empty($params)) {
                        $connectorObj = $dispatcherObject->getConnector('account', 'update' ,'entityRequester',
                            $params, $id);
                        $dispatcherObject->process($connectorObj);
                    }
                }
            }
            // ---------------------------------------

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);

            // M2ePro_TRANSLATIONS
            // The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%

            $error = 'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $model->delete();

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $error
                ]);
                return $this->getResult();
            }

            $this->messageManager->addError($error);

            return $this->_redirect('*/amazon_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was successfully saved'));

        /* @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $routerParams = array(
            'id' => $id,
            '_current' => true
        );
        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) &&
            $wizardHelper->getStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list',array(),array('edit'=>$routerParams)));
    }
}