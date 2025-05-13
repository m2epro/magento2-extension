<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Magento\Order\Item;

class UpdateCustomizationDetails
{
    private \Magento\Sales\Api\OrderItemRepositoryInterface $magentoOrderRepository;

    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $magentoOrderRepository
    ) {
        $this->magentoOrderRepository = $magentoOrderRepository;
    }

    public function process(\Ess\M2ePro\Model\Amazon\Order $order): void
    {
        $magentoOrder = $order->getParentObject()->getMagentoOrder();
        if ($magentoOrder === null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order\Item[] $amazonOrderItemsWithCustomByProductId */
        $amazonOrderItemsWithCustomByProductId = [];
        foreach ($order->getParentObject()->getItems() as $item) {
            $productId = $item->getProductId();
            if (empty($productId)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
            $amazonOrderItem = $item->getChildObject();
            if (!$amazonOrderItem->hasCustomizationDetails()) {
                continue;
            }

            $amazonOrderItemsWithCustomByProductId[(int)$productId] = $item->getChildObject();
        }

        /** @var \Magento\Sales\Model\Order\Item $magentoOrderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $magentoOrderItem) {
            $productId = (int)$magentoOrderItem->getProductId();
            if (!isset($amazonOrderItemsWithCustomByProductId[$productId])) {
                continue;
            }

            $amazonOrderItem = $amazonOrderItemsWithCustomByProductId[$productId];
            if ($amazonOrderItem->hasCustomizationDetailsWithTextPrintingType()) {
                $this->processTextPrinting($magentoOrderItem, $amazonOrderItem);
            }
        }
    }

    private function processTextPrinting(
        \Magento\Sales\Model\Order\Item $magentoOrderItem,
        \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem
    ): void {
        $isNeedSave = false;

        $productOptions = $magentoOrderItem->getProductOptions();
        $existingCustomizationLabels = [];
        foreach ($productOptions['additional_options'] ?? [] as $optionData) {
            $label = $optionData['label'] ?? '--';
            $existingCustomizationLabels[$label] = true;
        }

        foreach ($amazonOrderItem->getCustomizationDetailsWithTextPrintingType() as $customization) {
            if (isset($existingCustomizationLabels[$customization->label])) {
                continue;
            }

            $productOptions['additional_options'][] = [
                'label' => $customization->label,
                'value' => $customization->value,
            ];

            $existingCustomizationLabels[$customization->label] = true;

            $isNeedSave = true;
        }

        if ($isNeedSave) {
            $magentoOrderItem->setProductOptions($productOptions);

            $this->magentoOrderRepository->save($magentoOrderItem);
        }
    }
}
