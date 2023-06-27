<?php

namespace Ess\M2ePro\Block\Adminhtml\Tag;

class SwitcherFactory
{
    public function create(
        \Magento\Framework\View\LayoutInterface $layout,
        string $label,
        string $componentMode,
        string $controllerName
    ): Switcher {
        return $layout->createBlock(Switcher::class, 'tag_switcher', [
            'label' => $label,
            'componentMode' => $componentMode,
            'controllerName' => $controllerName,
        ]);
    }
}
