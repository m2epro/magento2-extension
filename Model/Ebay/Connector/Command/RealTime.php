<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Command;

abstract class RealTime extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace|null
     */
    protected $marketplace = NULL;

    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = NULL;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
        $this->marketplace = (!is_null($marketplace->getId())) ? $marketplace : NULL;
        $this->account = (!is_null($account->getId())) ? $account : NULL;

        parent::__construct($helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function buildRequestInstance()
    {
        $request = parent::buildRequestInstance();

        $requestData = $request->getData();

        if (!is_null($this->marketplace)) {
            $requestData['marketplace'] = $this->marketplace->getNativeId();
        }
        if (!is_null($this->account)) {
            $requestData['account'] = $this->account->getChildObject()->getServerHash();
        }

        $request->setData($requestData);

        return $request;
    }

    // ########################################
}