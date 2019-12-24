<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getComponent()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################
}
