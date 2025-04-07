<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Magento\Order;

class UpdateAddressVatIdService
{
    private \Ess\M2ePro\Model\Magento\Order\UpdaterFactory $orderUpdaterFactory;

    public function __construct(\Ess\M2ePro\Model\Magento\Order\UpdaterFactory $orderUpdaterFactory)
    {
        $this->orderUpdaterFactory = $orderUpdaterFactory;
    }

    public function execute(\Ess\M2ePro\Model\Order $order, string $vatId)
    {
        if (!$this->canUpdateVatId($order)) {
            return;
        }

        $orderUpdater = $this->orderUpdaterFactory->create();
        $orderUpdater->setMagentoOrder($order->getMagentoOrder());
        $orderUpdater->updateShippingAddress(['vat_id' => $vatId]);
        $orderUpdater->updateBillingAddress(['vat_id' => $vatId]);
        $orderUpdater->finishUpdate();
    }

    private function canUpdateVatId(\Ess\M2ePro\Model\Order $order): bool
    {
        $magentoOrder = $order->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        $amazonAccount = $order->getAccount()->getChildObject();
        if (!$amazonAccount instanceof \Ess\M2ePro\Model\Amazon\Account) {
            return false;
        }

        if (!$amazonAccount->isEnabledImportTaxIdInMagentoOrder()) {
            return false;
        }

        return true;
    }
}
