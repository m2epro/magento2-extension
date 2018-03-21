<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\StockItem;

class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;
    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;
    /** @var \Ess\M2ePro\Model\Factory  */
    protected $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ){
        $this->objectManager  = $objectManager;
        $this->helperFactory  = $helperFactory;
        $this->modelFactory   = $modelFactory;
    }

    //########################################

    /**
     * @param int $productId
     * @param int $scopeId
     * @return \Magento\CatalogInventory\Model\Stock\Item
     */
    public function create($productId, $scopeId)
    {
        if (version_compare($this->helperFactory->getObject('Magento')->getVersion(), '2.2.0', '<')) {
            return $this->objectManager
                        ->get('\Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory')
                        ->create();
        }

        return $this->objectManager
                    ->get('\Magento\CatalogInventory\Model\StockRegistryStorage')
                    ->getStockItem($productId, $scopeId);
    }

    //########################################
}