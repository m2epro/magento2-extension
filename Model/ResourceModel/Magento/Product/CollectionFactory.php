<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Magento\Product;

use Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product\Collection as MSICollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Magento $magentoHelper
    ) {
        $this->objectManager = $objectManager;
        $this->magentoHelper = $magentoHelper;
    }

    /**
     * @param array $data
     * @return \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection
     */
    public function create(array $data = [])
    {
        return $this->magentoHelper->isMSISupportingVersion()
            ? $this->objectManager->create(MSICollection::class, $data)
            : $this->objectManager->create(Collection::class, $data);
    }
}
