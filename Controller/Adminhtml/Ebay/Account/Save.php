<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Model\Ebay\Account as Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
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

        // tab: general
        // ---------------------------------------
        $keys = [
            'title',
            'mode',
            'token_session',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: 3rd party
        // ---------------------------------------
        $keys = [
            'other_listings_synchronization',
            'other_listings_mapping_mode'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $marketplacesIds = $this->activeRecordFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->getColumnValues('id');

        $marketplacesData = [];
        foreach ($marketplacesIds as $marketplaceId) {
            $marketplacesData[$marketplaceId]['related_store_id'] = isset($post['related_store_id_' . $marketplaceId])
                ? (int)$post['related_store_id_' . $marketplaceId]
                : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $data['marketplaces_data'] = $this->getHelper('Data')->jsonEncode($marketplacesData);
        // ---------------------------------------

        // Mapping
        // ---------------------------------------
        $tempData = [];
        $keys = [
            'mapping_sku_mode',
            'mapping_sku_priority',
            'mapping_sku_attribute',

            'mapping_title_mode',
            'mapping_title_priority',
            'mapping_title_attribute',

            'mapping_item_id_mode',
            'mapping_item_id_priority',
            'mapping_item_id_attribute'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $tempData[$key] = $post[$key];
            }
        }

        $mappingSettings = [];

        if (isset($tempData['mapping_sku_mode']) &&
            ($tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT ||
             $tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE ||
             $tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID)
        ) {
            $mappingSettings['sku']['mode'] = (int)$tempData['mapping_sku_mode'];
            $mappingSettings['sku']['priority'] = (int)$tempData['mapping_sku_priority'];

            if ($tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE) {
                $mappingSettings['sku']['attribute'] = (string)$tempData['mapping_sku_attribute'];
            }
        }

        if (isset($tempData['mapping_title_mode']) &&
            ($tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT ||
             $tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE)
        ) {
            $mappingSettings['title']['mode'] = (int)$tempData['mapping_title_mode'];
            $mappingSettings['title']['priority'] = (int)$tempData['mapping_title_priority'];
            $mappingSettings['title']['attribute'] = (string)$tempData['mapping_title_attribute'];
        }

        if (isset($tempData['mapping_item_id_mode']) &&
            $tempData['mapping_item_id_mode'] == Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE
        ) {
            $mappingSettings['item_id']['mode'] = (int)$tempData['mapping_item_id_mode'];
            $mappingSettings['item_id']['priority'] = (int)$tempData['mapping_item_id_priority'];
            $mappingSettings['item_id']['attribute'] = (string)$tempData['mapping_item_id_attribute'];
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

        if (!empty($tempSettings['source'])) {
            $data['magento_orders_settings'][$tempKey]['source'] = $tempSettings['source'];
        }

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

        // creation settings
        // ---------------------------------------
        $tempKey = 'creation';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'reservation_days'
        ];
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
//            'subscription_mode'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $notificationsKeys = [
//            'customer_created',
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
            'new',
            'paid',
            'shipped'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // qty reservation
        // ---------------------------------------
        $tempKey = 'qty_reservation';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'days',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // In Store Pickup statuses
        // ---------------------------------------
        $tempKey = 'in_store_pickup_statuses';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'ready_for_pickup',
            'picked_up',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        // ---------------------------------------

        // invoice/shipment settings
        // ---------------------------------------
        $temp = \Ess\M2ePro\Model\Ebay\Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
        $data['magento_orders_settings']['invoice_mode'] = $temp;
        $temp = \Ess\M2ePro\Model\Ebay\Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        $data['magento_orders_settings']['shipment_mode'] = $temp;

        $temp = \Ess\M2ePro\Model\Ebay\Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM;
        if (!empty($data['magento_orders_settings']['status_mapping']['mode']) &&
            $data['magento_orders_settings']['status_mapping']['mode'] == $temp) {
            if (!isset($post['magento_orders_settings']['invoice_mode'])) {
                $temp = \Ess\M2ePro\Model\Ebay\Account::MAGENTO_ORDERS_INVOICE_MODE_NO;
                $data['magento_orders_settings']['invoice_mode'] = $temp;
            }
            if (!isset($post['magento_orders_settings']['shipment_mode'])) {
                $temp = \Ess\M2ePro\Model\Ebay\Account::MAGENTO_ORDERS_SHIPMENT_MODE_NO;
                $data['magento_orders_settings']['shipment_mode'] = $temp;
            }
        }
        // ---------------------------------------

        // ---------------------------------------
        $data['magento_orders_settings'] = $this->getHelper('Data')->jsonEncode($data['magento_orders_settings']);
        // ---------------------------------------

        // tab: feedbacks
        // ---------------------------------------
        $keys = [
            'feedbacks_receive',
            'feedbacks_auto_response',
            'feedbacks_auto_response_only_positive'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        // tab: My Stores
        // ---------------------------------------
        if (isset($post['pickup_store_mode'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode(['bopis' => $post['pickup_store_mode']]);
        }
        // ---------------------------------------

        try {
            $data = $this->sendDataToServer($id, $data);
            $id = $this->updateAccount($id, $data);
        } catch (\Exception $exception) {
            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $exception->getMessage()
                ]);
                return $this->getResult();
            }

            $this->messageManager->addError($exception->getMessage());

            return $this->_redirect('*/ebay_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was successfully saved'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl(
            'list',
            [],
            [
                'edit' => [
                    'id'                => $id,
                    'update_ebay_store' => null,
                    '_current'          => true
                ]
            ]
        ));
    }
}
