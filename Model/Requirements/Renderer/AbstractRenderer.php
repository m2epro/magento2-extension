<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements\Renderer;

/**
 * Class \Ess\M2ePro\Model\Requirements\Renderer\AbstractRenderer
 */
abstract class AbstractRenderer extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Requirements\Checks\AbstractCheck */
    protected $checkObject;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Requirements\Checks\AbstractCheck $checkObject,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->checkObject = $checkObject;
    }

    //########################################

    public function getCheckObject()
    {
        return $this->checkObject;
    }

    //########################################

    abstract public function getTitle();
    abstract public function getMin();
    abstract public function getReal();

    //########################################
}
