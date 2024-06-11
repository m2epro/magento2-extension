<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime;

class ReviseAction extends AbstractRealtime
{
    private \Ess\M2ePro\Model\Ebay\Listing\LogFactory $logFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker $reviseChecker;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\LogFactory $logFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker $reviseChecker,
        \Ess\M2ePro\Model\Ebay\Connector\Item\DispatcherFactory $connectionDispatcherFactory,
        \Ess\M2ePro\Helper\Server\Maintenance $serverHelper,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct($connectionDispatcherFactory, $serverHelper, $lockManagerFactory);
        $this->logFactory = $logFactory;
        $this->reviseChecker = $reviseChecker;
    }

    protected function getAction(): int
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    protected function prepareOrFilterProducts(array $listingsProducts): array
    {
        $result = [];
        foreach ($listingsProducts as $product) {
            $checkerResult = $this->reviseChecker->calculateForManualAction($product);

            if (empty($checkerResult->getConfigurator()->getAllowedDataTypes())) {
                $this->writeLog(
                    $product,
                    'Item(s) were not revised. M2E Pro did not detect any relevant product changes to be updated.',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
                );

                continue;
            }

            if (
                $checkerResult->getConfigurator()->isPriceAllowed()
                && $product->getChildObject()->isProductInPromotion()
            ) {
                $checkerResult->getConfigurator()->disallowPrice();

                $this->writeLog(
                    $product,
                    'Price was not revised because this Item is currently on promotion.',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING
                );
            }

            $product->setActionConfigurator($checkerResult->getConfigurator());

            $result[] = $product;
        }

        return $result;
    }

    private function writeLog(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        string $messageText,
        int $type
    ): void {
        $ebayListingProduct = $listingProduct->getChildObject();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $ebayListingProduct->getId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT,
            $messageText,
            $type,
            []
        );
    }
}
