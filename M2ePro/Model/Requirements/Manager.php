<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Requirements;

/**
 * Class \Ess\M2ePro\Model\Requirements\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    const CACHE_KEY = 'is_meet_requirements';

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->objectManager = $objectManager;
    }

    //########################################

    public function isMeet()
    {
        $isMeetRequirements = $this->getHelper('Data_Cache_Permanent')->getValue(self::CACHE_KEY);
        if ($isMeetRequirements !== false) {
            return (bool)$isMeetRequirements;
        }

        foreach ($this->getChecks() as $check) {
            if (!($isMeetRequirements = $check->isMeet())) {
                break;
            }
        }

        $this->getHelper('Data_Cache_Permanent')->setValue(self::CACHE_KEY, (int)$isMeetRequirements, [], 60*60);
        return (bool)$isMeetRequirements;
    }

    //########################################

    /**
     * @return Checks\AbstractCheck[]
     */
    public function getChecks()
    {
        $checks = [
            Checks\MemoryLimit::class,
            Checks\ExecutionTime::class,
            Checks\MagentoVersion::class,
            Checks\PhpVersion::class,
        ];

        foreach ($checks as $check) {
            /** @var Checks\AbstractCheck $checkObj */
            $checkObj = $this->objectManager->create($check);
            $checkObj->setRenderer($this->objectManager->create(
                str_replace('Checks', 'Renderer', $check),
                ['checkObject' => $checkObj]
            ));

            yield $checkObj;
        }
    }

    //########################################
}
