<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template;

class ShippingTemplate extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $messages = array();

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('amazon/listing/product/template/shipping_template/main.phtml');
    }

    //########################################

    /**
     * @param array $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    //########################################

    public function getWarnings()
    {
        /** @var \Magento\Framework\View\Element\Messages $messages */
        $messages = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Messages');

        foreach ($this->getMessages() as $message) {
            $addMethod = 'add'.ucfirst($message['type']);
            $messages->$addMethod($message['text']);

        }
        return $messages->toHtml();
    }

    //########################################
}