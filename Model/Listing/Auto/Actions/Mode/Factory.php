<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

use Ess\M2ePro\Model\Listing\Auto\Actions\CategoryMode;
use Ess\M2ePro\Model\Listing\Auto\Actions\GlobalMode;
use Ess\M2ePro\Model\Listing\Auto\Actions\WebsiteMode;

class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\CategoryMode
     */
    public function createCategoryMode(\Magento\Catalog\Model\Product $product): CategoryMode
    {
        return $this->objectManager->create(
            CategoryMode::class,
            ['magentoProduct' => $product]
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\GlobalMode
     */
    public function createGlobalMode(\Magento\Catalog\Model\Product $product): GlobalMode
    {
        return $this->objectManager->create(
            GlobalMode::class,
            ['magentoProduct' => $product]
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\WebsiteMode
     */
    public function createWebsiteMode(\Magento\Catalog\Model\Product $product): WebsiteMode
    {
        return $this->objectManager->create(
            WebsiteMode::class,
            ['magentoProduct' => $product]
        );
    }
}
