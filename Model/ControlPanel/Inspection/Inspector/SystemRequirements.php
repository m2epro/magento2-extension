<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\Requirements\Manager as RequirementsManager;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class SystemRequirements implements InspectorInterface
{
    /** @var RequirementsManager */
    private $requirementsManager;

    /** @var IssueFactory */
    private $issueFactory;

    public function __construct(
        RequirementsManager $requirementsManager,
        IssueFactory $issueFactory
    ) {
        $this->requirementsManager = $requirementsManager;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];

        foreach ($this->requirementsManager->getChecks() as $check) {
            /**@var \Ess\M2ePro\Model\Requirements\Checks\AbstractCheck $check */
            if ($check->isMeet()) {
                continue;
            }

            $issues[] = $this->issueFactory->create(
                $check->getRenderer()->getTitle(),
                <<<HTML
<pre>
Minimum: {$check->getMin()}
Configuration: {$check->getReal()}
</pre>
HTML
            );
        }

        return $issues;
    }

    //########################################
}
