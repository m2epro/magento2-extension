<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other;

class Remover
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param int[] $productIds
     * @throws \Throwable
     */
    public function remove(array $productIds): void
    {
        /** @var array<int, \Ess\M2ePro\Model\Listing\Other[]> $groupedProductsByAccount */
        $groupedProductsByAccount = [];

        foreach ($productIds as $id) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            try {
                $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $id);
            } catch (\Throwable $e) {
                continue;
            }

            $accountId = (int)$listingOther->getAccountId();
            $groupedProductsByAccount[$accountId][] = $listingOther;
        }

        foreach ($groupedProductsByAccount as $accountId => $products) {
            $items = [];

            foreach ($products as $product) {
                $items[] = [
                    'id' => (string)$product->getChildObject()->getItemId(),
                    'marketplace' => (int)$product->getMarketplace()->getNativeId()
                ];
            }
            $itemsChunks = array_chunk($items, 1000);

            $account = $product->getAccount();
            $serverHash = $account->getChildObject()->getServerHash();

            foreach ($itemsChunks as $itemsChunk) {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Other\Delete $connectorObj */
                $connectorObj = $this->dispatcher->getCustomConnector(
                    'Ebay_Connector_Other_Delete',
                    [
                        'account' => $serverHash,
                        'items' => $itemsChunk,
                    ]
                );
                $this->dispatcher->process($connectorObj);
            }

            foreach ($products as $product) {
                $this->removeFromListing($product);
            }
        }
    }

    private function removeFromListing(\Ess\M2ePro\Model\Listing\Other $product): void
    {
        if ($product->getProductId() !== null) {
            $product->unmapProduct();
        }

        $product->delete();
    }
}
