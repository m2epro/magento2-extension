<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command\Delete
 */
class Delete extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResource;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Magento\Catalog\Model\ResourceModel\Product $productResource
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->productResource = $productResource;
    }

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array ...$arguments
     * @return mixed
     */
    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    protected function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems */
        $sourceItems = $arguments[0];

        $result = $callback(...$arguments);

        foreach ($sourceItems as $sourceItem) {

            $affected = $this->msiAffectedProducts->getAffectedProductsBySourceAndSku(
                $sourceItem->getSourceCode(), $sourceItem->getSku()
            );

            if (empty($affected)) {
                continue;
            }

            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $this->productResource->getIdBySku($sourceItem->getSku()),
                \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
            );

            foreach ($affected as $listingProduct) {
                $this->logListingProductMessage($listingProduct, $sourceItem);
            }
        }

        return $result;
    }

    //########################################

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem
    ){
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
            $this->getHelper('Module\Log')->encodeDescription(
                'The "%source%" Source was unassigned from product.',
                ['!source' => $sourceItem->getSourceCode()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
