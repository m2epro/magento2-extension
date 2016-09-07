<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Command\Pending;

abstract class Responser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    private $cachedParamsObjects = array();

    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
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
            return NULL;
        }

        $this->cachedParamsObjects[$idKey] = $this->ebayFactory->getObjectLoaded($model,$this->params[$idKey]);

        return $this->cachedParamsObjects[$idKey];
    }

    // ########################################
}