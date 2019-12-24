<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Inventory;

use Ess\M2ePro\Model\Magento\Product\Inventory;
use Ess\M2ePro\Model\MSI\Magento\Product\Inventory as MSIInventory;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Inventory\Factory
 */
class Factory extends \Ess\M2ePro\Model\AbstractModel
{
    private $objectManager;

    //########################################

    /**
     * Factory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Ess\M2ePro\Model\AbstractModel|AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject(\Magento\Catalog\Model\Product $product)
    {
        $object = $this->objectManager->get($this->isMsiMode($product) ? MSIInventory::class : Inventory::class);
        $object->setProduct($product);
        return $object;
    }

    //########################################

    private function isMsiMode(\Magento\Catalog\Model\Product $product)
    {
        if (!$this->getHelper('Magento')->isMSISupportingVersion()) {
            return false;
        }

        if (interface_exists(IsSourceItemManagementAllowedForProductTypeInterface::class)) {
            $isSourceItemManagementAllowedForProductType = $this->objectManager->get(
                IsSourceItemManagementAllowedForProductTypeInterface::class
            );
            return $isSourceItemManagementAllowedForProductType->execute($product->getTypeId());
        }

        return true;
    }

    //########################################
}