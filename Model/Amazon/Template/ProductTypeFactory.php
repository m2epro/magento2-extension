<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

class ProductTypeFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): ProductType
    {
        return $this->objectManager->create(ProductType::class);
    }
}
