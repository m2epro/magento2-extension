<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Save
 */
class Save extends Account
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: general
        // ---------------------------------------
        $keys = [
            'title',
            'marketplace_id',
            'consumer_id',
            'old_private_key',
            'client_id',
            'client_secret'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: 3rd party listings
        // ---------------------------------------
        $keys = [
            'related_store_id',

            'other_listings_synchronization',
            'other_listings_mapping_mode',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // Mapping
        // ---------------------------------------
        $tempData = [];
        $keys = [
            'mapping_sku_mode',
            'mapping_sku_priority',
            'mapping_sku_attribute',

            'mapping_upc_mode',
            'mapping_upc_priority',
            'mapping_upc_attribute',

            'mapping_gtin_mode',
            'mapping_gtin_priority',
            'mapping_gtin_attribute',

            'mapping_wpid_mode',
            'mapping_wpid_priority',
            'mapping_wpid_attribute',

            'mapping_title_mode',
            'mapping_title_priority',
            'mapping_title_attribute'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $tempData[$key] = $post[$key];
            }
        }

        $mappingSettings = [];

        $temp = [
            \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT,
            \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE,
            \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID,
        ];

        if (isset($tempData['mapping_sku_mode']) && in_array($tempData['mapping_sku_mode'], $temp)) {
            $mappingSettings['sku']['mode']     = (int)$tempData['mapping_sku_mode'];
            $mappingSettings['sku']['priority'] = (int)$tempData['mapping_sku_priority'];

            $temp = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE;
            if ($tempData['mapping_sku_mode'] == $temp) {
                $mappingSettings['sku']['attribute'] = (string)$tempData['mapping_sku_attribute'];
            }
        }

        $temp1 = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE;
        if (isset($tempData['mapping_upc_mode']) && $tempData['mapping_upc_mode'] == $temp1) {
            $mappingSettings['upc']['mode']     = (int)$tempData['mapping_upc_mode'];
            $mappingSettings['upc']['priority'] = (int)$tempData['mapping_upc_priority'];

            $temp = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE;
            if ($tempData['mapping_upc_mode'] == $temp) {
                $mappingSettings['upc']['attribute'] = (string)$tempData['mapping_upc_attribute'];
            }
        }

        $temp1 = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE;
        if (isset($tempData['mapping_gtin_mode']) && $tempData['mapping_gtin_mode'] == $temp1) {
            $mappingSettings['gtin']['mode']     = (int)$tempData['mapping_gtin_mode'];
            $mappingSettings['gtin']['priority'] = (int)$tempData['mapping_gtin_priority'];

            $temp = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE;
            if ($tempData['mapping_gtin_mode'] == $temp) {
                $mappingSettings['gtin']['attribute'] = (string)$tempData['mapping_gtin_attribute'];
            }
        }

        $temp1 = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE;
        if (isset($tempData['mapping_wpid_mode']) && $tempData['mapping_wpid_mode'] == $temp1) {
            $mappingSettings['wpid']['mode']     = (int)$tempData['mapping_wpid_mode'];
            $mappingSettings['wpid']['priority'] = (int)$tempData['mapping_wpid_priority'];

            $temp = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE;
            if ($tempData['mapping_wpid_mode'] == $temp) {
                $mappingSettings['wpid']['attribute'] = (string)$tempData['mapping_wpid_attribute'];
            }
        }

        $temp1 = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT;
        $temp2 = \Ess\M2ePro\Model\Walmart\Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE;
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
        $data['magento_orders_settings'] = [];

        // m2e orders settings
        // ---------------------------------------
        $tempKey = 'listing';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'store_mode',
            'store_id'
        ];
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
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'product_mode',
            'product_tax_class_id',
            'store_id'
        ];
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
            ? $post['magento_orders_settings'][$tempKey] : [];

        $data['magento_orders_settings'][$tempKey]['source'] = $tempSettings['source'];

        $prefixKeys = [
            'mode',
            'prefix',
        ];
        $tempSettings = !empty($tempSettings['prefix']) ? $tempSettings['prefix'] : [];
        foreach ($prefixKeys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey]['prefix'][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // tax settings
        // ---------------------------------------
        $tempKey = 'tax';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode'
        ];
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
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'id',
            'website_id',
            'group_id',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $notificationsKeys = [
            'order_created',
            'invoice_created'
        ];
        $tempSettings = !empty($tempSettings['notifications']) ? $tempSettings['notifications'] : [];
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
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'processing',
            'shipped'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // invoice/shipment settings
        // ---------------------------------------
        $temp = \Ess\M2ePro\Model\Walmart\Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
        $data['magento_orders_settings']['invoice_mode'] = $temp;
        $temp = \Ess\M2ePro\Model\Walmart\Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        $data['magento_orders_settings']['shipment_mode'] = $temp;

        $temp = \Ess\M2ePro\Model\Walmart\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM;
        if (!empty($data['magento_orders_settings']['status_mapping']['mode']) &&
            $data['magento_orders_settings']['status_mapping']['mode'] == $temp) {
            $temp = \Ess\M2ePro\Model\Walmart\Account::MAGENTO_ORDERS_INVOICE_MODE_NO;
            if (!isset($post['magento_orders_settings']['invoice_mode'])) {
                $data['magento_orders_settings']['invoice_mode'] = $temp;
            }
            $temp = \Ess\M2ePro\Model\Walmart\Account::MAGENTO_ORDERS_SHIPMENT_MODE_NO;
            if (!isset($post['magento_orders_settings']['shipment_mode'])) {
                $data['magento_orders_settings']['shipment_mode'] = $temp;
            }
        }
        // ---------------------------------------

        // ---------------------------------------
        $data['magento_orders_settings'] = $this->getHelper('Data')->jsonEncode($data['magento_orders_settings']);
        // ---------------------------------------

        $isEdit = $id !== null;

        // Add or update model
        // ---------------------------------------
        $model = $this->walmartFactory->getObject('Account');
        if ($id === null) {
            $model->setData($data);
        } else {
            $model->load($id);
            $model->addData($data);
            $model->getChildObject()->addData($data);
        }

        $oldData = $model->getOrigData();
        if ($id !== null) {
            $oldData = array_merge($oldData, $model->getChildObject()->getOrigData());
        }

        $id = $model->save()->getId();
        $model->getChildObject()->save();

        try {
            // Add or update server
            // ---------------------------------------

            /** @var $accountObj \Ess\M2ePro\Model\Account */
            $accountObj = $model;

            if (!$accountObj->isSetProcessingLock('server_synchronize')) {

                /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
                $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

                if ($post['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA) {
                    $requestData = [
                        'title'            => $post['title'],
                        'marketplace_id'   => (int)$post['marketplace_id'],
                        'related_store_id' => (int)$post['related_store_id'],
                        'consumer_id'      => $post['consumer_id'],
                        'private_key'      => $post['old_private_key']
                    ];
                } else {
                    $requestData = [
                        'title'            => $post['title'],
                        'marketplace_id'   => (int)$post['marketplace_id'],
                        'related_store_id' => (int)$post['related_store_id'],
                        'consumer_id'      => $post['consumer_id'],
                        'client_id'        => $post['client_id'],
                        'client_secret'    => $post['client_secret'],
                    ];
                }

                if (!$isEdit) {
                    $connectorObj = $dispatcherObject->getConnector(
                        'account',
                        'add',
                        'entityRequester',
                        $requestData,
                        $id
                    );
                    $dispatcherObject->process($connectorObj);
                } else {
                    $requestData = array_diff_assoc($requestData, $oldData);

                    if (!empty($requestData)) {
                        $connectorObj = $dispatcherObject->getConnector(
                            'account',
                            'update',
                            'entityRequester',
                            $requestData,
                            $id
                        );
                        $dispatcherObject->process($connectorObj);
                    }
                }
            }
            // ---------------------------------------
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            // M2ePro_TRANSLATIONS
            // The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%

            $error = 'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%';
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

            return $this->_redirect('*/walmart_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was successfully saved'));

        /** @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $routerParams = [
            'id' => $id,
            '_current' => true
        ];
        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) &&
            $wizardHelper->getStep(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>$routerParams]));
    }

    //########################################
}
