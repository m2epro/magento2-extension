<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class Save extends Order
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->globalData = $globalData;
    }

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

        /** @var \Ess\M2ePro\Model\Order $order */
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
            'recipient_name',
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

        $this->globalData->setValue('order', $order);

        $this->setJsonContent([
            'success' => true,
            'html' => $this->getLayout()
                           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Order\Edit\ShippingAddress::class)
                           ->toHtml()
        ]);

        return $this->getResult();
    }
}
