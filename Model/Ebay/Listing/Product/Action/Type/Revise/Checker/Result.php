<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker;

class Result
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
    private $configurator;
    /** @var array */
    private $tags;

    public function __construct(\Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator, array $tags)
    {
        $this->configurator = $configurator;
        $this->tags = $tags;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
     */
    public function getConfigurator(): \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
    {
        return $this->configurator;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
