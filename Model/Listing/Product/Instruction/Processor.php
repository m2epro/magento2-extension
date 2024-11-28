<?php

namespace Ess\M2ePro\Model\Listing\Product\Instruction;

use Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;

class Processor
{
    private string $component;
    private int $maxListingsProductsCount;
    /** @var HandlerInterface[] */
    private array $handlers = [];
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\CollectionFactory $instructionCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;
    private \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\InputFactory $handlerInputFactory;

    /**
     * @param HandlerInterface[] $handlers
     */
    public function __construct(
        string $component,
        int $maxListingsProductsCount,
        array $handlers,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\CollectionFactory $instructionCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\InputFactory $handlerInputFactory
    ) {
        $this->component = $component;
        $this->maxListingsProductsCount = $maxListingsProductsCount;
        $this->handlers = $handlers;
        $this->instructionResource = $instructionResource;
        $this->instructionCollectionFactory = $instructionCollectionFactory;
        $this->listingProductResource = $listingProductResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->exceptionHelper = $exceptionHelper;
        $this->handlerInputFactory = $handlerInputFactory;
    }

    public function process(): void
    {
        $this->deleteInstructionsOlderThenWeek();
        $this->deleteInstructionsWithoutListingProducts();
        $this->deleteNotValidAmazonProductTypeInstruction();
        $this->deleteNotValidEbayCategorySpecificInstruction();

        $listingsProducts = $this->getNeededListingsProducts();

        $instructions = $this->loadInstructions($listingsProducts);
        if (empty($instructions)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] $listingProductInstructions */
        foreach ($instructions as $listingProductId => $listingProductInstructions) {
            try {
                $handlerInput = $this->handlerInputFactory->create();
                $handlerInput->setListingProduct($listingsProducts[$listingProductId]);
                $handlerInput->setInstructions($listingProductInstructions);

                foreach ($this->handlers as $handler) {
                    $handler->process($handlerInput);

                    if ($handlerInput->getListingProduct()->isDeleted()) {
                        break;
                    }
                }
            } catch (\Throwable $exception) {
                if (!$exception instanceof \Ess\M2ePro\Model\Exception\ProductNotExist) {
                    $this->exceptionHelper->process($exception);
                }
            }

            $this->instructionResource->remove(
                array_keys($listingProductInstructions)
            );
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     *
     * @return list<int, list<int, \Ess\M2ePro\Model\Listing\Product\Instruction>>
     */
    private function loadInstructions(array $listingsProducts): array
    {
        if (empty($listingsProducts)) {
            return [];
        }

        $instructionCollection = $this->instructionCollectionFactory->create();
        $instructionCollection->applySkipUntilFilter();
        $instructionCollection->addFieldToFilter('listing_product_id', array_keys($listingsProducts));

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] $instructions */
        $instructions = $instructionCollection->getItems();

        $instructionsByListingsProducts = [];

        foreach ($instructions as $instruction) {
            $listingProduct = $listingsProducts[$instruction->getListingProductId()];
            $instruction->setListingProduct($listingProduct);

            $instructionsByListingsProducts[$instruction->getListingProductId()][$instruction->getId()] = $instruction;
        }

        return $instructionsByListingsProducts;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function getNeededListingsProducts(): array
    {
        $collection = $this->instructionCollectionFactory->create();
        $collection->applyNonBlockedFilter();
        $collection->applySkipUntilFilter();
        $collection->addFieldToFilter('main_table.component', $this->component);

        $collection->setOrder('MAX(main_table.priority)', 'DESC');
        $collection->setOrder('MIN(main_table.create_date)', 'ASC');

        $collection->getSelect()->limit($this->maxListingsProductsCount);
        $collection->getSelect()->group('main_table.listing_product_id');
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns('main_table.listing_product_id');

        $ids = $collection->getColumnValues('listing_product_id');
        if (empty($ids)) {
            return [];
        }

        $listingsProductsCollection = $this->listingProductCollectionFactory->create();
        $listingsProductsCollection->addFieldToFilter('id', $ids);

        return $listingsProductsCollection->getItems();
    }

    private function deleteInstructionsWithoutListingProducts(): void
    {
        $collection = $this->instructionCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['second_table' => $this->listingProductResource->getMainTable()],
            'main_table.listing_product_id = second_table.id'
        );
        $collection->getSelect()->where('second_table.id IS NULL');
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns('main_table.id');

        $this->instructionResource->remove(
            $collection->getColumnValues('id')
        );
    }

    private function deleteInstructionsOlderThenWeek(): void
    {
        $greaterThenDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $greaterThenDate->modify('-7 day');

        $this->instructionResource
            ->getConnection()
            ->delete(
                $this->instructionResource->getMainTable(),
                ['? > create_date' => $greaterThenDate->format('Y-m-d')]
            );
    }

    private function deleteNotValidAmazonProductTypeInstruction(): void
    {
        $this->instructionResource->deleteByTagErrorCodes([
            \Ess\M2ePro\Model\Amazon\ProductType\AttributesValidator::ERROR_TAG_CODE
        ]);
    }

    private function deleteNotValidEbayCategorySpecificInstruction(): void
    {
        $this->instructionResource->deleteByTagErrorCodes([
            \Ess\M2ePro\Model\Ebay\Category\SpecificValidator::ERROR_TAG_CODE
        ]);
    }
}
