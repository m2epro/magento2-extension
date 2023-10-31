<?php

namespace Ess\M2ePro\Model\Listing\Product\Instruction;

use Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;

class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var string */
    private $component;
    /** @var int */
    private $maxListingsProductsCount;
    /** @var HandlerInterface[] */
    private $handlers = [];
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instructionResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\CollectionFactory */
    private $instructionCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\InputFactory */
    private $handlerInputFactory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig */
    private $blockingErrorConfig;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\CollectionFactory $instructionCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\InputFactory $handlerInputFactory,
        \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig $blockingErrorConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->instructionResource = $instructionResource;
        $this->instructionCollectionFactory = $instructionCollectionFactory;
        $this->listingProductResource = $listingProductResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->exceptionHelper = $exceptionHelper;
        $this->handlerInputFactory = $handlerInputFactory;
        $this->blockingErrorConfig = $blockingErrorConfig;
    }

    public function setComponent(string $component): self
    {
        $this->component = $component;

        return $this;
    }

    public function setMaxListingsProductsCount(int $count): self
    {
        $this->maxListingsProductsCount = $count;

        return $this;
    }

    public function registerHandler(HandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
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
                $this->exceptionHelper->process($exception);
            }

            $this->instructionResource->remove(
                array_keys($listingProductInstructions)
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     *
     * @return \Ess\M2ePro\Model\Listing\Product\Instruction[]|[]
     */
    protected function loadInstructions(array $listingsProducts)
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
     * @return array
     */
    protected function getNeededListingsProducts()
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

    protected function deleteInstructionsWithoutListingProducts(): void
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

    public function deleteInstructionsOlderThenWeek(): void
    {
        $greaterThenDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $greaterThenDate->modify('-7 day');

        $productInstructionResource = $this->instructionResource;

        $productInstructionResource
            ->getConnection()
            ->delete(
                $productInstructionResource->getMainTable(),
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
