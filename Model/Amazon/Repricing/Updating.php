<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Repricing\Updating
 */
class Updating extends AbstractModel
{
    private $listingLogFactory;

    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;
    private $translation;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Amazon\Listing\LogFactory $listingLogFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Helper\Module\Translation $translation
    ) {
        parent::__construct($activeRecordFactory, $amazonFactory, $resourceConnection, $helperFactory, $modelFactory);

        $this->listingLogFactory = $listingLogFactory;
        $this->listingLogResource = $listingLogResource;
        $this->translation = $translation;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing
     * @return bool|array
     */
    public function process(array $listingsProductsRepricing)
    {
        $changesData                      = [];
        $updatedListingProductsRepricing  = [];
        $updatedSkus                      = [];

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $changeData = $this->getChangeData($listingProductRepricing);
            if ($changeData && !in_array($changeData['sku'], $updatedSkus, true)) {
                $changesData[] = $changeData;
                $updatedSkus[] = $changeData['sku'];
                $updatedListingProductsRepricing[] = $listingProductRepricing;
            }
        }

        if (!$this->sendData($changesData)) {
            return false;
        }

        $this->updateListingsProductsRepricing($updatedListingProductsRepricing);

        return $updatedSkus;
    }

    private function getChangeData(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing)
    {
        $isDisabled = $listingProductRepricing->isDisabled();

        if ($isDisabled && $listingProductRepricing->isOnlineDisabled()) {
            return false;
        }

        $regularPrice = $listingProductRepricing->getRegularPrice();
        $minPrice     = $listingProductRepricing->getMinPrice();
        $maxPrice     = $listingProductRepricing->getMaxPrice();

        if ($isDisabled   == $listingProductRepricing->getLastUpdatedIsDisabled() &&
            $regularPrice == $listingProductRepricing->getLastUpdatedRegularPrice() &&
            $minPrice     == $listingProductRepricing->getLastUpdatedMinPrice() &&
            $maxPrice     == $listingProductRepricing->getLastUpdatedMaxPrice()
        ) {
            return false;
        }

        if ($regularPrice > $maxPrice) {
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                $this->translation->__(
                    'Item price was not updated. Regular Price must be equal to or lower than the Max Price value.'
                )
            );

            return false;
        }

        if ($regularPrice < $minPrice) {
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                $this->translation->__(
                    'Item price was not updated. Regular Price must be equal to or higher than the Min Price value.'
                )
            );

            return false;
        }

        return [
            'sku' => $listingProductRepricing->getAmazonListingProduct()->getSku(),
            'regular_product_price'   => $regularPrice,
            'minimal_product_price'   => $minPrice,
            'maximal_product_price'   => $maxPrice,
            'is_calculation_disabled' => $isDisabled,
        ];
    }

    private function sendData(array $changesData)
    {
        try {
            $result = $this->getHelper('Component_Amazon_Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE_USER_CHANGES,
                [
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                    'offers'        => $this->getHelper('Data')->jsonEncode($changesData),
                ]
            );
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $this->getSynchronizationLog()->addMessageFromException($e);

            return false;
        }

        $this->processErrorMessages($result['response']);
        return true;
    }

    protected function updateListingsProductsRepricing(array $updatedProducts)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $updatedProduct */
        foreach ($updatedProducts as $updatedProduct) {
            $this->resourceConnection->getConnection()->update(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_listing_product_repricing'),
                [
                    'last_updated_regular_price' => $updatedProduct->getRegularPrice(),
                    'last_updated_min_price'     => $updatedProduct->getMinPrice(),
                    'last_updated_max_price'     => $updatedProduct->getMaxPrice(),
                    'last_updated_is_disabled'   => $updatedProduct->isDisabled(),
                    'update_date'                => $this->getHelper('Data')->getCurrentGmtDate(),
                ],
                ['listing_product_id = ?' => $updatedProduct->getListingProductId()]
            );
        }
    }

    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, $logMessage)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Log $listingLog */
        $listingLog = $this->listingLogFactory->create();

        $listingLog->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $this->listingLogResource->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN,
            $logMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }
}
