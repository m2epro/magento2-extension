<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    /** @var \Ess\M2ePro\Model\Order */
    protected $order;

    /** @var \Ess\M2ePro\Model\Order\Change */
    protected $orderChange;

    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace = null,
        \Ess\M2ePro\Model\Account $account = null,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    ) {
        parent::__construct($helperFactory, $modelFactory, $marketplace, $account, $params);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;

        $this->order = $this->ebayFactory->getObject('Order')->load($params['order_id']);
        $this->orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($params['change_id']);
    }

    //########################################

    public function process()
    {
        parent::process();

        $this->processResponseData();
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        return true;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    abstract protected function processResponseData();

    //########################################
}
