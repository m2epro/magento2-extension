<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function getComponent()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
