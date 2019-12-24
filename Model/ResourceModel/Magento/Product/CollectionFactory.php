<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Magento\Product;

use Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product\Collection as MSICollection;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory
 */
class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory
    ) {
        $this->objectManager = $objectManager;
        $this->helperFactory = $helperFactory;
    }

    //########################################

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection
     */
    public function create(array $data = [])
    {
        return $this->helperFactory->getObject('Magento')->isMSISupportingVersion()
            ? $this->objectManager->create(MSICollection::class, $data)
            : $this->objectManager->create(Collection::class, $data);
    }

    //########################################
}