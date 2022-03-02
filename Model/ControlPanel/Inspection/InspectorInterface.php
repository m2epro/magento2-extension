<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

interface InspectorInterface
{
    /**
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Issue[]
     */
    public function process();
}
