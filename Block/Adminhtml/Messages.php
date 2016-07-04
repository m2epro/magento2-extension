<?php

namespace Ess\M2ePro\Block\Adminhtml;

class Messages extends \Magento\Framework\View\Element\Messages
{
    protected function _beforeToHtml()
    {
        $messages = $this->messageManager->getMessages(
            true, \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
        );

        if ($messages->getCount() < 2) {
            $this->addMessages($messages);
            return parent::_beforeToHtml();
        }

        $hashes = [];
        $uniqueMessages = $this->collectionFactory->create();

        foreach ($messages->getItems() as $message) {
            $hash = crc32($message->getText());

            if (!in_array($hash, $hashes)) {
                $hashes[] = $hash;
                $uniqueMessages->addMessage($message);
            }
        }

        $this->addMessages($uniqueMessages);

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return
            '<div id="globalMessages">'
            . parent::_toHtml()
            . '</div>';
    }
}