<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask\MagentoProducts;

abstract class AbstractModel extends \Ess\M2ePro\Model\Synchronization\GlobalTask\AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractGlobal::MAGENTO_PRODUCTS;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('MagentoProducts\\'.$taskPath);
    }

    //########################################
}