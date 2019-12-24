<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;
use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat\Save
 */
class Save extends Template
{
    //########################################

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

        // ---------------------------------------
        $keys = [
            'title',
            'marketplace_id',

            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value',

            'price_mode',
            'price_coefficient',
            'price_custom_attribute',

            'map_price_mode',
            'map_price_custom_attribute',

            'price_variation_mode',

            'promotions_mode',

            'sale_time_start_date_mode',
            'sale_time_end_date_mode',

            'sale_time_start_date_custom_attribute',
            'sale_time_end_date_custom_attribute',

            'sale_time_start_date_value',
            'sale_time_end_date_value',

            'item_weight_mode',
            'item_weight_custom_value',
            'item_weight_custom_attribute',

            'price_vat_percent',

            'lag_time_mode',
            'lag_time_value',
            'lag_time_custom_attribute',

            'product_tax_code_mode',
            'product_tax_code_custom_value',
            'product_tax_code_custom_attribute',

            'must_ship_alone_mode',
            'must_ship_alone_value',
            'must_ship_alone_custom_attribute',

            'ships_in_original_packaging_mode',
            'ships_in_original_packaging_value',
            'ships_in_original_packaging_custom_attribute',

            'shipping_override_rule_mode',

            'attributes_mode'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['title'] = strip_tags($data['title']);

        if ($data['sale_time_start_date_value'] === '') {
            $data['sale_time_start_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_start_date_value'] = $this->getHelper('Data')->getDate(
                $data['sale_time_start_date_value'],
                false,
                'Y-m-d 00:00:00'
            );
        }
        if ($data['sale_time_end_date_value'] === '') {
            $data['sale_time_end_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_end_date_value'] = $this->getHelper('Data')->getDate(
                $data['sale_time_end_date_value'],
                false,
                'Y-m-d 00:00:00'
            );
        }

        $data['attributes'] = $this->getHelper('Data')->jsonEncode(
            $this->getComparedData($post, 'attributes_name', 'attributes_value')
        );

        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->walmartFactory->getObject('Template\SellingFormat');

        $oldData = [];

        if ($id) {
            $model->load($id);

            $oldData = array_merge(
                $model->getDataSnapshot(),
                $model->getChildObject()->getDataSnapshot()
            );
        }

        $model->addData($data)->save();
        $model->getChildObject()->addData(array_merge(
            [$model->getResource()->getChildPrimary(Walmart::NICK) => $model->getId()],
            $data
        ));

        $model->save();

        $this->updateServices($post, $model->getId());
        $this->updatePromotions($post, $model->getId());

        $newData = array_merge($model->getDataSnapshot(), $model->getChildObject()->getDataSnapshot());
        $model->getChildObject()->setSynchStatusNeed($newData, $oldData);

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

    // ---------------------------------------

    private function updateServices($data, $templateId)
    {
        $collection = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat_ShippingOverride')
                          ->getCollection()
                          ->addFieldToFilter('template_selling_format_id', (int)$templateId);

        foreach ($collection as $item) {
            $item->delete();
        }

        if (empty($data['shipping_override_rule'])) {
            return;
        }

        $newServices = [];
        foreach ($data['shipping_override_rule'] as $serviceData) {
            $newServices[] = [
                'template_selling_format_id' => $templateId,
                'method'              => $serviceData['method'],
                'is_shipping_allowed' => $serviceData['is_shipping_allowed'],
                'region'              => $serviceData['region'],
                'cost_mode'           => !empty($serviceData['cost_mode']) ? $serviceData['cost_mode'] : 0,
                'cost_value'          => !empty($serviceData['cost_value']) ? $serviceData['cost_value'] : 0,
                'cost_attribute'      => !empty($serviceData['cost_attribute']) ? $serviceData['cost_attribute'] : ''
            ];
        }

        if (empty($newServices)) {
            return;
        }

        $coreRes = $this->resourceConnection;
        $coreRes->getConnection()->insertMultiple(
            $this->getHelper('Module_Database_Structure')
                 ->getTableNameWithPrefix('m2epro_walmart_template_selling_format_shipping_override'),
            $newServices
        );
    }

    private function updatePromotions($data, $templateId)
    {
        $collection = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat_Promotion')
                                                ->getCollection()
                                                ->addFieldToFilter('template_selling_format_id', (int)$templateId);

        foreach ($collection as $item) {
            $item->delete();
        }

        if (empty($data['promotions'])) {
            return;
        }

        $newPromotions = [];
        foreach ($data['promotions'] as $promotionData) {
            if (!empty($promotionData['from_date']['value'])) {
                $startDate = $this->getHelper('Data')->getDate(
                    $promotionData['from_date']['value'],
                    false,
                    'Y-m-d H:i'
                );
            } else {
                $startDate = $this->getHelper('Data')->getCurrentGmtDate(
                    false,
                    'Y-m-d H:i'
                );
            }

            if (!empty($promotionData['to_date']['value'])) {
                $endDate = $this->getHelper('Data')->getDate(
                    $promotionData['to_date']['value'],
                    false,
                    'Y-m-d H:i'
                );
            } else {
                $endDate = $this->getHelper('Data')->getCurrentGmtDate(
                    false,
                    'Y-m-d H:i'
                );
            }

            $newPromotions[] = [
                'template_selling_format_id'   => $templateId,
                'price_mode'                   => $promotionData['price']['mode'],
                'price_attribute'              => $promotionData['price']['attribute'],
                'price_coefficient'            => $promotionData['price']['coefficient'],
                'start_date_mode'              => $promotionData['from_date']['mode'],
                'start_date_attribute'         => $promotionData['from_date']['attribute'],
                'start_date_value'             => $startDate,
                'end_date_mode'                => $promotionData['to_date']['mode'],
                'end_date_attribute'           => $promotionData['to_date']['attribute'],
                'end_date_value'               => $endDate,
                'comparison_price_mode'        => $promotionData['comparison_price']['mode'],
                'comparison_price_attribute'   => $promotionData['comparison_price']['attribute'],
                'comparison_price_coefficient' => $promotionData['comparison_price']['coefficient'],
                'type'                         => $promotionData['type'],
            ];
        }

        if (empty($newPromotions)) {
            return;
        }

        $coreRes = $this->resourceConnection;
        $coreRes->getConnection()->insertMultiple(
            $this->getHelper('Module_Database_Structure')
                 ->getTableNameWithPrefix('m2epro_walmart_template_selling_format_promotion'),
            $newPromotions
        );
    }

    //########################################

    private function getComparedData($data, $keyName, $valueName)
    {
        $result = [];

        if (!isset($data[$keyName]) || !isset($data[$valueName])) {
            return $result;
        }

        $keyData = array_filter($data[$keyName]);
        $valueData = array_filter($data[$valueName]);

        if (count($keyData) !== count($valueData)) {
            return $result;
        }

        foreach ($keyData as $index => $value) {
            $result[] = ['name' => $value, 'value' => $valueData[$index]];
        }

        return $result;
    }

    //########################################
}
