<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;
use Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Requirements\Manager as RequirementsManager;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;

class SystemRequirements extends AbstractInspection implements InspectorInterface
{
    /** @var RequirementsManager */
    protected $requirementsManager;

    public function __construct(
        Factory $resultFactory,
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        FormKey $formKey,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        RequirementsManager $requirementsManager,
        array $_params = []
    ) {
        $this->requirementsManager = $requirementsManager;

        parent::__construct(
            $resultFactory,
            $helperFactory,
            $modelFactory,
            $urlBuilder,
            $resourceConnection,
            $formKey,
            $parentFactory,
            $activeRecordFactory,
            $_params
        );
    }

    //########################################

    public function getTitle()
    {
        return 'System Requirements';
    }

    public function getDescription()
    {
        $html = '';
        foreach ($this->requirementsManager->getChecks() as $check) {
            $html .= "- {$check->getRenderer()->getTitle()}: {$check->getRenderer()->getMin()}<br>";
        }

        return $html;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
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

            $issues[] = $this->resultFactory->createError(
                $this,
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
