<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

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
            'street',
            'city',
            'country_code',
            'state',
            'postal_code',
            'phone'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $shippingDetails = $order->getChildObject()->getShippingDetails();
        $shippingDetails['address'] = $data;

        $order->getChildObject()->setData('shipping_details', $this->getHelper('Data')->jsonEncode($shippingDetails));
        $order->save();

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setJsonContent([
            'success' => true,
            'html' => $this->createBlock('Ebay\Order\Edit\ShippingAddress')->toHtml()
        ]);

        return $this->getResult();
    }
}