<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Add;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Add\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    //########################################

    /**
     * @return array
     */
    protected function getRequestData()
    {
        /** @var $marketplaceObject \Ess\M2ePro\Model\Marketplace */

        $marketplaceObject = $this->amazonFactory->getCachedObjectLoaded(
            'Marketplace',
            $this->params['marketplace_id']
        );

        return [
            'title'          => $this->account->getTitle(),
            'merchant_id'    => $this->params['merchant_id'],
            'token'          => $this->params['token'],
            'marketplace_id' => $marketplaceObject->getNativeId(),
        ];
    }

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['account','add','entity'];
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if ((empty($responseData['hash']) || !isset($responseData['info'])) &&
            !$this->getResponse()->getMessages()->hasErrorEntities()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function prepareResponseData()
    {
        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            throw new \Exception($message->getText());
        }

        $this->responseData = $this->getResponse()->getResponseData();
    }

    //########################################
}
