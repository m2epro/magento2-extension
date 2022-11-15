<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Custom;

use Magento\Framework\ObjectManagerInterface;
use Ess\M2ePro\Model\Amazon\Search\Custom;

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
     * @param \Ess\M2ePro\Model\Amazon\Search\Custom\Query $query
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return Custom
     */
    public function createHandler(Query $query, \Ess\M2ePro\Model\Listing\Product $listingProduct): Custom
    {
        return $this->objectManager->create(
            Custom::class,
            [
                'query' => $query,
                'result' => $this->createResult($query),
                'listingProduct' => $listingProduct,
            ]
        );
    }

    /**
     * @param string $value
     *
     * @return \Ess\M2ePro\Model\Amazon\Search\Custom\Query
     */
    public function createQuery(string $value): Query
    {
        return $this->objectManager->create(
            Query::class,
            ['value' => $value]
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Search\Custom\Query $query
     *
     * @return \Ess\M2ePro\Model\Amazon\Search\Custom\Result
     */
    public function createResult(Query $query): Result
    {
        return $this->objectManager->create(
            Result::class,
            ['query' => $query]
        );
    }
}
