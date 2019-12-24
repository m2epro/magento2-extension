<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Command\Pending;

/**
 * Class \Ess\M2ePro\Model\Translation\Connector\Command\Pending\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    private $cachedParamsObjects = [];

    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($response, $helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function getObjectByParam($model, $idKey)
    {
        if (isset($this->cachedParamsObjects[$idKey])) {
            return $this->cachedParamsObjects[$idKey];
        }

        if (!isset($this->params[$idKey])) {
            return null;
        }

        $this->cachedParamsObjects[$idKey] = $this->ebayFactory->getObjectLoaded(
            $model,
            $this->params[$idKey]
        );

        return $this->cachedParamsObjects[$idKey];
    }

    // ########################################
}
