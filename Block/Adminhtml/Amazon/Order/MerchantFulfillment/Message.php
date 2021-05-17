<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Message
 */
class Message extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonOrderMerchantFulfillmentMessage');
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'messages_form',
                ]
            ]
        );

        $message = '';

        switch ($this->getData('message')) {
            case 'marketplaceError':
                $message = $this->__(
                    <<<HTML
This Order was Created on Amazon Marketplace where Amazon's Shipping Services are not available.
Currently, you can <strong>use this Tool</strong> for Orders purchased on <strong>Amazon UK</strong>,
<strong>US</strong> and <strong>DE</strong>.
HTML
                );
                break;
            case 'fbaError':
                $message = $this->__(
                    <<<HTML
            Amazon's Shipping Services can not be applied to FBA Orders. The delivery of purchased <strong>FBA Items</strong>
            is managed by <strong>Amazon Fulfillment Center</strong>.
HTML
                );
                break;
            case 'statusError':
                $message = $this->__(
                    <<<HTML
            Amazon's Shipping Service can not be used for the Orders with Status Pending, Shipped and Canceled. It can be
            applied only to <strong>Amazon Orders</strong> with <strong>Unshipped Status</strong>.
HTML
                );
                break;
            case 'markAsShipped':
                $message = $this->__(
                    <<<HTML
You cannot mark this Order as Shipped because this is the Amazon Prime Order. You should use 
<a href="#" onclick="AmazonOrderMerchantFulfillmentObj.useMerchantFulfillmentAction()">Amazon's Shipping Services</a> 
feature instead.
HTML
                );
                break;
        }

        $form->addField(
            'error_message',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type'    => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                        'content' => $message
                    ]
                ]
            ]
        );

        $this->setForm($form);

        return $this;
    }

    //########################################
}
