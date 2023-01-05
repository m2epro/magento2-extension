<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing\Product;

abstract class Listing
{
    public const INSTRUCTION_TYPE_STOP            = 'auto_actions_stop';
    public const INSTRUCTION_TYPE_STOP_AND_REMOVE = 'auto_actions_stop_and_remove';

    public const INSTRUCTION_INITIATOR = 'auto_actions';

    /** @var \Ess\M2ePro\Model\Listing*/
    protected $listing;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    protected $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->listing = $listing;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->exceptionHelper = $exceptionHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param int $deletingMode
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function deleteProduct(\Magento\Catalog\Model\Product $product, int $deletingMode): void
    {
        if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE) {
            return;
        }

        $listingsProducts = $this->getListing()->getProducts(true, ['product_id' => (int)$product->getId()]);

        if (count($listingsProducts) <= 0) {
            return;
        }

        foreach ($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return;
            }

            if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP && !$listingProduct->isStoppable()) {
                continue;
            }

            try {
                $instructionType = self::INSTRUCTION_TYPE_STOP;

                if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE) {
                    $instructionType = self::INSTRUCTION_TYPE_STOP_AND_REMOVE;
                }

                $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
                $instruction->setData(
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component'          => $listingProduct->getComponentMode(),
                        'type'               => $instructionType,
                        'initiator'          => self::INSTRUCTION_INITIATOR,
                        'priority'           => $listingProduct->isStoppable() ? 60 : 0,
                    ]
                );
                $instruction->save();
            } catch (\Exception $exception) {
                $this->exceptionHelper->process($exception);
            }
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
     *
     * @return mixed
     */
    abstract public function addProductByCategoryGroup(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
    );

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return mixed
     */
    abstract public function addProductByGlobalListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    );

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return mixed
     */
    abstract public function addProductByWebsiteListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    );

    /**
     * @param Product $listingProduct
     * @throws Logic
     */
    protected function logAddedToMagentoProduct(Product $listingProduct)
    {
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getListing()->getComponentMode());
        $tempLog->addProductMessage(
            $this->getListing()->getId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_MAGENTO,
            'Product was Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing(): \Ess\M2ePro\Model\Listing
    {
        return $this->listing;
    }
}
