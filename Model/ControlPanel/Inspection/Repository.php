<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Model\ControlPanel\Inspection\Repository
 */
class Repository
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Definition[] */
    private $definitions;

    public function __construct(
        \Ess\M2ePro\Model\ControlPanel\Inspection\Repository\DefinitionProvider $definitionProvider
    )
    {
        foreach ($definitionProvider->getDefinitions() as $definition) {
            $this->definitions[$definition->getNick()] = $definition;
        }
    }

    /**
     * @param string $nick
     *
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Definition
     */
    public function getDefinition($nick)
    {
        return $this->definitions[$nick];
    }

    /**
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Definition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }
}
