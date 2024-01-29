<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule;

class ViewStateFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $viewKey): ViewState
    {
        return $this->objectManager->create(ViewState::class, [
            'viewKey' => $viewKey,
        ]);
    }
}
