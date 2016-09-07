<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product;

abstract class AbstractProduct extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $productFactory;
    /**
     * @var null|\Magento\Catalog\Model\Product
     */
    private $product = NULL;

    /**
     * @var null|int
     */
    private $productId = NULL;
    /**
     * @var null|int
     */
    private $storeId = NULL;

    /**
     * @var null|\Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = NULL;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->productFactory = $productFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        $product = $this->getEvent()->getProduct();

        if (!($product instanceof \Magento\Catalog\Model\Product)) {
            throw new \Ess\M2ePro\Model\Exception('Product event doesn\'t have correct Product instance.');
        }

        $this->product = $product;

        $this->productId = (int)$this->product->getId();
        $this->storeId = (int)$this->product->getData('store_id');
    }

    //########################################

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

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function reloadProduct()
    {
        if ($this->getProductId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('To reload Product instance product_id should be
                greater than 0.');
        }

        $this->product = $this->productFactory->create()
                                              ->setStoreId($this->getStoreId())
                                              ->load($this->getProductId());

        return $this->getProduct();
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getProductId()
    {
        return (int)$this->productId;
    }

    /**
     * @return int
     */
    protected function getStoreId()
    {
        return (int)$this->storeId;
    }

    //########################################

    /**
     * @return bool
     */
    protected function isAdminDefaultStoreId()
    {
        return $this->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getMagentoProduct()
    {
        if (!empty($this->magentoProduct)) {
            return $this->magentoProduct;
        }

        if ($this->getProductId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('To load Magento Product instance product_id should be
                greater than 0.');
        }

        return $this->magentoProduct = $this->modelFactory->getObject('Magento\Product')
                                                          ->setProduct($this->getProduct());
    }

    //########################################
}