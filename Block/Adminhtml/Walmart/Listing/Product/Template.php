<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template
 */
abstract class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $messages = [];

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
        $messages = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);

        foreach ($this->getMessages() as $message) {
            $addMethod = 'add'.ucfirst($message['type']);
            $messages->$addMethod($message['text']);
        }
        return $messages->toHtml();
    }

    //########################################
}
