<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

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
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);

        $data = array();
        $keys = array(
            'buyer_name',
            'buyer_email'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $order->getChildObject()->setData('buyer_name', $data['buyer_name']);
        $order->getChildObject()->setData('buyer_email', $data['buyer_email']);

        $data = array();
        $keys = array(
            'county',
            'country_code',
            'state',
            'city',
            'postal_code',
            'recipient_name',
            'phone',
            'street'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        $oldShippingAddress = $order->getChildObject()->getSettings('shipping_address');
        $data['recipient_name'] = !empty($oldShippingAddress['recipient_name'])
            ? $oldShippingAddress['recipient_name'] : null;

        $order->getChildObject()->setSettings('shipping_address', $data);
        $order->save();

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setJsonContent([
            'success' => true,
            'html' => $this->createBlock('Amazon\Order\Edit\ShippingAddress')->toHtml()
        ]);

        return $this->getResult();
    }
}