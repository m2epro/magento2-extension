<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Account\Get;

class AuthInfo extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /** @var string[] */
    private $accountsServerHashes;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $account,
        array $params
    ) {
        parent::__construct($helperFactory, $modelFactory, $account, $params);

        $this->accountsServerHashes = $params['accounts'];
    }

    protected function getRequestData(): array
    {
        return ['accounts' => $this->accountsServerHashes];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['account', 'get', 'authInfo'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['accounts']);
    }

    protected function prepareResponseData(): void
    {
        $accountsInfo = $this->getResponse()->getResponseData()['accounts'];

        $result = [];
        foreach ($this->accountsServerHashes as $hash) {
            if (!isset($accountsInfo[$hash])) {
                continue;
            }

            $isValid = $accountsInfo[$hash]['is_valid'];
            $result[$hash] = $isValid;
        }

        $this->responseData = $result;
    }
}
