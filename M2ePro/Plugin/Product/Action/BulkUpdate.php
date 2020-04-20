<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Product\Action;

use Magento\Catalog\Model\Product\Action as ProductAction;

/**
 * Class \Ess\M2ePro\Plugin\Product\Action\BulkUpdate
 */
class BulkUpdate extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventManager;

    //########################################

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->eventManager = $eventManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * Magento Removed some events (plugins must be used instead): catalog_product_website_update_before
     * Programmed with compatibility with M1 version - just fire corresponding event
     */
    public function aroundUpdateWebsites($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('updateWebsites', $interceptor, $callback, $arguments);
    }

    public function processUpdateWebsites($interceptor, \Closure $callback, array $arguments)
    {
        $productIds = $arguments[0];
        $websiteIds = $arguments[1];
        $type       = $arguments[2];

        $this->eventManager->dispatch(
            'catalog_product_website_update_before',
            [
                'product_ids' => $productIds,
                'website_ids' => $websiteIds,
                'action'      => $type,
            ]
        );

        return $callback(...$arguments);
    }

    //########################################
}
