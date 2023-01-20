<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime;

use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Result;

abstract class AbstractRealtime extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\AbstractManual
{
    /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\DispatcherFactory */
    private $connectionDispatcherFactory;
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Item\DispatcherFactory $connectionDispatcherFactory,
        \Ess\M2ePro\Helper\Server\Maintenance $serverHelper,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct($lockManagerFactory);
        $this->connectionDispatcherFactory = $connectionDispatcherFactory;
        $this->serverHelper = $serverHelper;
    }

    public function isAllowed(): bool
    {
        return !$this->serverHelper->isNow();
    }

    protected function processListingsProducts(array $listingsProducts, array $params): Result
    {
        $params['logs_action_id'] = $this->getLogActionId();
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
        $params['is_realtime'] = true;

        $result = (int)$this->connectionDispatcherFactory
            ->create()
            ->process($this->getAction(), $listingsProducts, $params);

        if ($result === \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            return Result::createError($this->getLogActionId());
        }

        if ($result === \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            return Result::createWarning($this->getLogActionId());
        }

        return Result::createSuccess($this->getLogActionId());
    }
}
