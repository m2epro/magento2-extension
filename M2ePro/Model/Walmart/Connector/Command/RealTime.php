<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Command;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
 */
abstract class RealTime extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = null;

    //########################################

    /**
     * Items constructor.
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\Account|NULL $account
     * @param array $params
     */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $account,
        array $params
    ) {
        $this->account = $account;
        parent::__construct($helperFactory, $modelFactory, $params);
    }

    //########################################

    protected function buildRequestInstance()
    {
        $request = parent::buildRequestInstance();

        $requestData = $request->getData();
        if ($this->account !== null) {
            $requestData['account'] = $this->account->getChildObject()->getServerHash();
        }
        $request->setData($requestData);

        return $request;
    }

    //########################################
}
