<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter;

class RendererFactory
{
    public function createCreatingRenderer(
        string $viewStateKey,
        \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel,
        \Magento\Framework\View\LayoutInterface $layout
    ): Renderer\Creating {
        return $layout->createBlock(Renderer\Creating::class, 'renderer_creating', [
            'viewStateKey' => $viewStateKey,
            'ruleModel' => $ruleModel,
        ]);
    }

    public function createUpdatingRenderer(
        int $updatedEntityId,
        string $viewStateKey,
        \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel,
        \Magento\Framework\View\LayoutInterface $layout
    ): Renderer\Updating {
        return $layout->createBlock(Renderer\Updating::class, 'renderer_updating', [
            'updatedEntityId' => $updatedEntityId,
            'viewStateKey' => $viewStateKey,
            'ruleModel' => $ruleModel,
        ]);
    }

    public function createSelectedRenderer(
        int $selectedRuleId,
        bool $isRuleRecentlyCreated,
        \Magento\Framework\View\LayoutInterface $layout
    ): Renderer\Selected {
        return $layout->createBlock(Renderer\Selected::class, 'renderer_selected', [
            'selectedRuleId' => $selectedRuleId,
            'isRuleRecentlyCreated' => $isRuleRecentlyCreated,
        ]);
    }

    public function createUnselectedRenderer(
        string $ruleModelNick,
        \Magento\Framework\View\LayoutInterface $layout
    ): Renderer\Unselected {
        return $layout->createBlock(Renderer\Unselected::class, 'renderer_unselect', [
            'ruleModelNick' => $ruleModelNick,
        ]);
    }
}
