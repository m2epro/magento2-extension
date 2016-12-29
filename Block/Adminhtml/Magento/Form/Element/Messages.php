<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Message\MessageInterface;

class Messages extends AbstractElement
{
    protected $layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        array $data = []
    )
    {
        $this->layout = $layout;

        $this->setType('hidden');
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getElementHtml()
    {
        $messages = $this->getData('messages');

        if (empty($messages)) {
            return '';
        }

        $block = $this->layout->createBlock('Magento\Framework\View\Element\Messages');

        foreach ($messages as $message) {
            switch ($message['type']) {
                case MessageInterface::TYPE_ERROR:
                    $block->addError($message['content']);
                    break;
                case MessageInterface::TYPE_NOTICE:
                    $block->addNotice($message['content']);
                    break;
                case MessageInterface::TYPE_SUCCESS:
                    $block->addSuccess($message['content']);
                    break;
                case MessageInterface::TYPE_WARNING:
                    $block->addWarning($message['content']);
                    break;
            }
        }

        return <<<HTML
<div id="{$this->getHtmlId()}" style="{$this->getStyle()}" class="{$this->getClass()}">
    {$block->toHtml()}
</div>
HTML;
    }
}