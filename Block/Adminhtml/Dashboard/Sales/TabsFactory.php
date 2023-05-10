<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Sales;

class TabsFactory
{
    public function createTabs(
        Tabs\Item $amountsTabItem,
        Tabs\Item $qtyTabItem,
        \Magento\Framework\View\LayoutInterface $layout
    ): Tabs {
        /** @var Tabs $tabs */
        $tabs = $layout->createBlock(Tabs::class, 'dashboard_sales_tabs', [
            'amountsTabItem' => $amountsTabItem,
            'qtyTabItem' => $qtyTabItem,
        ]);

        return $tabs;
    }

    public function createTabItem(
        string $label,
        \Ess\M2ePro\Model\Dashboard\Sales\PointSet $pointSet,
        \Magento\Framework\View\LayoutInterface $layout
    ): Tabs\Item {
        $name = sprintf('dashboard_sales_tabs_%s_tab', strtolower($label));

        /** @var Tabs\Item $tab */
        $tab = $layout->createBlock(Tabs\Item::class, $name, [
            'label' => $label,
            'pointSet' => $pointSet,
        ]);

        return $tab;
    }
}
