<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces;

/**
 * Class AbstractModel
 * @package Ess\M2ePro\Model\Walmart\Synchronization\Marketplaces
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Walmart\Synchronization\AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('Marketplaces\\'.$taskPath);
    }

    //########################################
}
