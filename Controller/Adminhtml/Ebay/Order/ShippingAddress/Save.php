<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ShippingAddress\Save
 */
class Save extends Order
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->setJsonContent([
                'success' => false
            ]);
            return $this->getResult();
        }

        $id = $this->getRequest()->getParam('id', false);

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $data = [];
        $keys = [
            'buyer_name',
            'buyer_email'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $order->getChildObject()->setData('buyer_name', $data['buyer_name']);
        $order->getChildObject()->setData('buyer_email', $data['buyer_email']);

        $data = [];
        $keys = [
            'street',
            'city',
            'country_code',
            'state',
            'postal_code',
            'phone'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if (isset($data['street']) && is_array($data['street'])) {
            $data['street'] = array_filter($data['street']);
        }

        $shippingDetails = $order->getChildObject()->getShippingDetails();
        $shippingDetails['address'] = $data;

        $order->getChildObject()->setData('shipping_details', $this->getHelper('Data')->jsonEncode($shippingDetails));
        $order->save();

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setJsonContent([
            'success' => true,
            'html' => $this->createBlock('Ebay_Order_Edit_ShippingAddress')->toHtml()
        ]);

        return $this->getResult();
    }
}
