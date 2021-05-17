<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;
use Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment as MerchantFulfillment;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\GetShippingServices
 */
class GetShippingServices extends Order
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        if ($orderId === null) {
            return $this->getResponse()->setBody('You should specify order ID');
        }

        $post = $this->getRequest()->getPost()->toArray();

        if (empty($post)) {
            return $this->getResponse()->setBody('You should specify POST data');
        }

        $fulfillmentCachedFields = [
            'package_dimension_source',
            'package_dimension_measure',
            'package_dimension_length',
            'package_dimension_width',
            'package_dimension_height',
            'package_dimension_length_custom_attribute',
            'package_dimension_width_custom_attribute',
            'package_dimension_height_custom_attribute',
            'package_weight_source',
            'package_weight_custom_value',
            'package_weight_custom_attribute',
            'package_weight_measure',
            'ship_from_address_name',
            'ship_from_address_email',
            'ship_from_address_phone',
            'ship_from_address_country',
            'ship_from_address_region_state',
            'ship_from_address_postal_code',
            'ship_from_address_city',
            'ship_from_address_address_line_1',
            'ship_from_address_address_line_2',
            'delivery_experience',
            'carrier_will_pickup'
        ];

        $fulfillmentCachedData = array_intersect_key($post, array_flip($fulfillmentCachedFields));

        $this->getHelper('Data_Cache_Permanent')->setValue(
            'amazon_merchant_fulfillment_data',
            $fulfillmentCachedData,
            ['amazon', 'merchant_fulfillment']
        );

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded(
            'Order',
            $orderId
        );

        $orderItems = $order->getItemsCollection()->getItems();
        $preparedOrderItems = [];
        foreach ($orderItems as $parentOrderItem) {
            $orderItem = $parentOrderItem->getChildObject();
            $preparedOrderItems[] = [
                'id'  => $orderItem->getAmazonOrderItemId(),
                'qty' => $orderItem->getQtyPurchased()
            ];
        }

        $preparedPackageData = [];
        $isVirtualPredefinedPackage = false;
        if (isset($post['package_dimension_predefined']) &&
            strpos($post['package_dimension_predefined'], MerchantFulfillment::VIRTUAL_PREDEFINED_PACKAGE) !== false) {
            $isVirtualPredefinedPackage = true;
        }

        if ($post['package_dimension_source'] == MerchantFulfillment::DIMENSION_SOURCE_PREDEFINED &&
            !$isVirtualPredefinedPackage) {
            $preparedPackageData['predefined_dimensions'] = $post['package_dimension_predefined'];
        } elseif ($post['package_dimension_source'] == MerchantFulfillment::DIMENSION_SOURCE_CUSTOM ||
            ($post['package_dimension_source'] == MerchantFulfillment::DIMENSION_SOURCE_PREDEFINED &&
                $isVirtualPredefinedPackage)) {
            $preparedPackageData['dimensions'] = [];
            $preparedPackageData['dimensions']['length'] = $post['package_dimension_length'];
            $preparedPackageData['dimensions']['width'] = $post['package_dimension_width'];
            $preparedPackageData['dimensions']['height'] = $post['package_dimension_height'];
            $preparedPackageData['dimensions']['unit_of_measure'] = $post['package_dimension_measure'];
        } elseif ($post['package_dimension_source'] == MerchantFulfillment::DIMENSION_SOURCE_CUSTOM_ATTRIBUTE &&
            $order->getItemsCollection()->count() === 1) {

            /** @var \Ess\M2ePro\Model\Order\Item $item */
            $item = $order->getItemsCollection()->getFirstItem();

            $preparedPackageData['dimensions'] = [];
            $preparedPackageData['dimensions']['length'] = $item->getMagentoProduct()->getAttributeValue(
                $post['package_dimension_length_custom_attribute']
            );
            $preparedPackageData['dimensions']['width'] = $item->getMagentoProduct()->getAttributeValue(
                $post['package_dimension_width_custom_attribute']
            );
            $preparedPackageData['dimensions']['height'] = $item->getMagentoProduct()->getAttributeValue(
                $post['package_dimension_height_custom_attribute']
            );
            $preparedPackageData['dimensions']['unit_of_measure'] = $post['package_dimension_measure'];
        }

        if ($post['package_weight_source'] == MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_VALUE) {
            $preparedPackageData['weight'] = [
                'value'           => $post['package_weight_custom_value'],
                'unit_of_measure' => $post['package_weight_measure']
            ];
        } elseif ($post['package_weight_source'] == MerchantFulfillment::WEIGHT_SOURCE_CUSTOM_ATTRIBUTE) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */
            $item = $order->getItemsCollection()->getFirstItem();

            $preparedPackageData['weight'] = [
                'value'           => $item->getMagentoProduct()->getAttributeValue(
                    $post['package_weight_custom_attribute']
                ),
                'unit_of_measure' => $post['package_weight_measure']
            ];
        }

        $preparedShipmentData = [];
        $preparedShipmentData['info'] = [
            'name'  => $post['ship_from_address_name'],
            'email' => $post['ship_from_address_email'],
            'phone' => $post['ship_from_address_phone'],
        ];
        $preparedShipmentData['physical'] = [
            'country'     => $post['ship_from_address_country'],
            'city'        => $post['ship_from_address_city'],
            'postal_code' => $post['ship_from_address_postal_code'],
            'address_1'   => $post['ship_from_address_address_line_1'],
        ];

        if ($post['ship_from_address_region_state']) {
            $preparedShipmentData['physical']['region_state'] = $post['ship_from_address_region_state'];
        }

        if ($post['ship_from_address_address_line_2']) {
            $preparedShipmentData['physical']['address_2'] = $post['ship_from_address_address_line_2'];
        }

        $requestData = [
            'order_id'                    => $order->getChildObject()->getAmazonOrderId(),
            'order_items'                 => $preparedOrderItems,
            'package'                     => $preparedPackageData,
            'shipment_location'           => $preparedShipmentData,
            'delivery_confirmation_level' => $post['delivery_experience'],
            'carrier_pickup'              => $post['carrier_will_pickup']
        ];

        if ($post['must_arrive_by_date']) {
            $mustArriveByDateTimestamp = strtotime($post['must_arrive_by_date']);
            $mustArriveByDate = new \DateTime();
            $mustArriveByDate->setTimestamp($mustArriveByDateTimestamp);
            $requestData['arrive_by_date'] = $mustArriveByDate->format(DATE_ISO8601);
        }

        if ($post['declared_value']) {
            $requestData['declared_value']['amount'] = $post['declared_value'];
            $requestData['declared_value']['currency_code'] = $order->getChildObject()->getCurrency();
        }

        $this->getHelper('Data_Session')->setValue('fulfillment_request_data', $requestData);

        $popup = $this->createBlock('Amazon_Order_MerchantFulfillment_ShippingServices');

        try {
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'shipment',
                'get',
                'offers',
                $requestData,
                null,
                $order->getAccount()
            );

            $dispatcherObject->process($connectorObj);

            $responseData = $connectorObj->getResponseData();
            $popup->setData('shipping_services', $responseData);

            foreach ($connectorObj->getResponse()->getMessages() as $message) {
                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */

                if ($message->isError()) {
                    $popup->setData('error_message', $message->getText());
                    break;
                }
            }
        } catch (\Exception $exception) {
            $popup->setData('error_message', $exception->getMessage());
        }

        $this->setAjaxContent($popup);

        return $this->getResult();
    }
}
