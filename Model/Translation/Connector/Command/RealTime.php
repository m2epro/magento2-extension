<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Command;

/**
 * Class \Ess\M2ePro\Model\Translation\Connector\Command\RealTime
 */
abstract class RealTime extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account,
        array $params
    ) {
        $this->account = ($account->getId() !== null) ? $account : null;
        parent::__construct($helperFactory, $modelFactory, $params);
    }

    //########################################

    protected function buildRequestInstance()
    {
        $request = parent::buildRequestInstance();

        $requestData = $request->getData();
        if ($this->account !== null) {
            $requestData['account'] = $this->account->getChildObject()->getTranslationHash();
        }
        $request->setData($requestData);

        return $request;
    }

    //########################################
}
