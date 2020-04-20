<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

/**
 * Class \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\AbstractMode
 */
abstract class AbstractMode extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var null|\Magento\Catalog\Model\Product
     */
    private $product = null;

    protected $activeRecordFactory;
    protected $parentFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getProduct()
    {
        if (!($this->product instanceof \Magento\Catalog\Model\Product)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "Product" should be set first.');
        }

        return $this->product;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
     */
    protected function getListingObject(\Ess\M2ePro\Model\Listing $listing)
    {
        $componentMode = ucfirst($listing->getComponentMode());

        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Listing $object */
        $object = $this->modelFactory->getObject($componentMode.'\Listing\Auto\Actions\Listing');

        $object->setListing($listing);

        return $object;
    }

    //########################################
}
