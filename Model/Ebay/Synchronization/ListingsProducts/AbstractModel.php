<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts;

/**
 * Class AbstractModel
 * @package Ess\M2ePro\Model\Ebay\Synchronization\ListingsProducts
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('ListingsProducts\\'.$taskPath);
    }

    //########################################
}
