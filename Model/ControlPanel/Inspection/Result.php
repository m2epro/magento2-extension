<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

class Result
{
    /** @var bool */
    private $status;

    /** @var string */
    private $errorMessage;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Issue[] */
    private $issues;

    public function __construct($status, $errorMessage, $issues)
    {
        $this->status = $status;
        $this->errorMessage = $errorMessage;
        $this->issues = $issues;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Issue[]
     */
    public function getIssues()
    {
        return $this->issues;
    }
}
