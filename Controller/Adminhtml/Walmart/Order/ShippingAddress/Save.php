<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order\ShippingAddress\Save
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
        $order = $this->walmartFactory->getObjectLoaded('Order', (int)$id);

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
            'county',
            'country_code',
            'state',
            'city',
            'postal_code',
            'recipient_name',
            'phone',
            'street'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if (isset($data['street']) && is_array($data['street'])) {
            $data['street'] = array_filter($data['street']);
        }

        $oldShippingAddress = $order->getChildObject()->getSettings('shipping_address');
        if (empty($data['recipient_name'])) {
            $data['recipient_name'] = !empty($oldShippingAddress['recipient_name'])
                ? $oldShippingAddress['recipient_name'] : null;
        }

        $order->getChildObject()->setSettings('shipping_address', $data);
        $order->save();

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setJsonContent([
            'success' => true,
            'html' => $this->createBlock('Walmart_Order_Edit_ShippingAddress')->toHtml()
        ]);

        return $this->getResult();
    }
}
