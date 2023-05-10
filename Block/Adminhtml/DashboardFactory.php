<?php

namespace Ess\M2ePro\Block\Adminhtml;

class DashboardFactory
{
    public function create(
        string $activeComponentNick,
        \Magento\Framework\View\LayoutInterface $layout,
        \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface $salesCalculator,
        \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface $productsCalculator,
        \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface $shipmentsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments\UrlStorageInterface $shipmentsUrlStorage,
        \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface $errorsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors\UrlStorageInterface $errorsUrlStorage = null
    ): Dashboard {
        $allowedNicks = [
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
        ];

        if (!in_array($activeComponentNick, $allowedNicks)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Invalid component nick');
        }

        /** @var Dashboard\ComponentTabs $componentTabs */
        $componentTabs = $layout->createBlock(Dashboard\ComponentTabs::class);
        $componentTabs->setActiveComponentNick($activeComponentNick);

        /** @var Dashboard\Sales $sales */
        $sales = $layout->createBlock(Dashboard\Sales::class, 'dashboard_sales', [
            'calculator' => $salesCalculator,
        ]);

        /** @var Dashboard\Products $products */
        $products = $layout->createBlock(Dashboard\Products::class, 'dashboard_products', [
            'calculator' => $productsCalculator,
        ]);

        /** @var Dashboard\Shipments $shipments */
        $shipments = $layout->createBlock(Dashboard\Shipments::class, 'dashboard_shipments', [
            'calculator' => $shipmentsCalculator,
            'urlStorage' => $shipmentsUrlStorage,
        ]);

        /** @var Dashboard\Errors $errors */
        $errors = $layout->createBlock(Dashboard\Errors::class, 'dashboard_errors', [
            'calculator' => $errorsCalculator,
        ]);

        if ($errorsUrlStorage) {
            $errors->setUrlStorage($errorsUrlStorage);
        }

        /** @var Dashboard $dashboard */
        $dashboard = $layout->createBlock(Dashboard::class, 'dashboard', [
            'componentTabs' => $componentTabs,
            'sales' => $sales,
            'products' => $products,
            'shipments' => $shipments,
            'errors' => $errors,
        ]);

        return $dashboard;
    }
}
