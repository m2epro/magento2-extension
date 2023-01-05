<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

class RemoveHandler extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;

    /**
     * @deprecated use factory
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct): self
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    public function process(): void
    {
        $this->eventBeforeProcess();

        if (!$this->listingProduct->isNotListed()) {
            $this->listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)->save();
        }

        $this->listingProduct->delete();
        $this->listingProduct->isDeleted(true);

        $this->eventAfterProcess();
    }

    protected function eventBeforeProcess(): void
    {
    }

    protected function eventAfterProcess(): void
    {
    }
}
