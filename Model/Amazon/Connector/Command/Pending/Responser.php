<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Command\Pending;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    private $cachedParamsObjects = [];

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($response, $helperFactory, $modelFactory, $params);
    }

    //########################################

    protected function getObjectByParam($model, $idKey)
    {
        if (isset($this->cachedParamsObjects[$idKey])) {
            return $this->cachedParamsObjects[$idKey];
        }

        if (!isset($this->params[$idKey])) {
            return null;
        }

        $this->cachedParamsObjects[$idKey] = $this->amazonFactory->getObjectLoaded($model, $this->params[$idKey]);

        return $this->cachedParamsObjects[$idKey];
    }

    //########################################
}
