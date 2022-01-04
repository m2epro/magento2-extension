<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Instruction;

use \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Instruction\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    protected $component = null;

    protected $maxListingsProductsCount = null;

    /** @var HandlerInterface[] */
    protected $handlers = [];

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setComponent($component)
    {
        $this->component = $component;
        return $this;
    }

    public function setMaxListingsProductsCount($count)
    {
        $this->maxListingsProductsCount = $count;
        return $this;
    }

    //########################################

    public function registerHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }

    //########################################

    public function process()
    {
        $this->deleteInstructionsWithoutListingProducts();

        $listingsProducts = $this->getNeededListingsProducts();

        $instructions = $this->loadInstructions($listingsProducts);
        if (empty($instructions)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] $listingProductInstructions */
        foreach ($instructions as $listingProductId => $listingProductInstructions) {
            try {
                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $handlerInput */
                $handlerInput = $this->modelFactory->getObject('Listing_Product_Instruction_Handler_Input');
                $handlerInput->setListingProduct($listingsProducts[$listingProductId]);
                $handlerInput->setInstructions($listingProductInstructions);

                foreach ($this->handlers as $handler) {
                    $handler->process($handlerInput);

                    if ($handlerInput->getListingProduct()->isDeleted()) {
                        break;
                    }
                }
            } catch (\Exception $exception) {
                $this->helperFactory->getObject('Module\Exception')->process($exception);
            }

            $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->remove(
                array_keys($listingProductInstructions)
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     * @return \Ess\M2ePro\Model\Listing\Product\Instruction[]|[]
     */
    protected function loadInstructions(array $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $instructionCollection */
        $instructionCollection = $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getCollection();
        $instructionCollection->applySkipUntilFilter();
        $instructionCollection->addFieldToFilter('listing_product_id', array_keys($listingsProducts));

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] $instructions */
        $instructions = $instructionCollection->getItems();

        $instructionsByListingsProducts = [];

        foreach ($instructions as $instruction) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
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
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getCollection();
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

        $listingsProductsCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', $ids);

        return $listingsProductsCollection->getItems();
    }

    //########################################

    protected function deleteInstructionsWithoutListingProducts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getCollection();
        $collection->getSelect()->joinLeft(
            ['second_table' => $this->activeRecordFactory->getObject('Listing_Product')->getResource()->getMainTable()],
            'main_table.listing_product_id = second_table.id'
        );
        $collection->getSelect()->where('second_table.id IS NULL');
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns('main_table.id');

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->remove(
            $collection->getColumnValues('id')
        );
    }

    //########################################
}
