<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Listing;

use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function create(\Ess\M2ePro\Model\Listing $listing): \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
    {
        if ($listing->isComponentModeEbay()) {
            return $this->createObject(
                \Ess\M2ePro\Model\Ebay\Listing\Auto\Actions\Listing::class,
                $listing
            );
        }

        if ($listing->isComponentModeAmazon()) {
            return $this->createObject(
                \Ess\M2ePro\Model\Amazon\Listing\Auto\Actions\Listing::class,
                $listing
            );
        }

        if ($listing->isComponentModeWalmart()) {
            return $this->createObject(
                \Ess\M2ePro\Model\Walmart\Listing\Auto\Actions\Listing::class,
                $listing
            );
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unresolved component mode');
    }

    /**
     * @param string $className
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
     */
    private function createObject(
        string $className,
        \Ess\M2ePro\Model\Listing $listing
    ): \Ess\M2ePro\Model\Listing\Auto\Actions\Listing {
        return $this->objectManager->create(
            $className,
            ['listing' => $listing]
        );
    }
}
