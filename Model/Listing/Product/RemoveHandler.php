<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\RemoveHandler
 */
class RemoveHandler extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
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

    //########################################

    public function process()
    {
        $this->eventBeforeProcess();

        if (!$this->listingProduct->isNotListed()) {
            $this->listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)->save();
        }

        $this->listingProduct->delete();
        $this->listingProduct->isDeleted(true);

        $this->eventAfterProcess();
    }

    //########################################

    protected function eventBeforeProcess()
    {
        return null;
    }

    protected function eventAfterProcess()
    {
        return null;
    }

    //########################################
}
