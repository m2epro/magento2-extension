<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input */
    protected $input = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setInput(Input $input)
    {
        $this->input = $input;
        return $this;
    }

    //########################################

    abstract public function isAllowed();

    abstract public function process(array $params = []);

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getScheduledActionManager()
    {
        return $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');
    }

    protected function setPropertiesForRecheck(array $properties)
    {
        if (empty($properties)) {
            return;
        }

        $additionalData = $this->input->getListingProduct()->getAdditionalData();

        $existedProperties = [];
        if (!empty($additionalData['recheck_properties'])) {
            $existedProperties = $additionalData['recheck_properties'];
        }

        $properties = array_unique(array_merge($existedProperties, $properties));

        $additionalData['recheck_properties'] = $properties;
        $this->input->getListingProduct()->setSettings('additional_data', $additionalData)->save();
    }

    //########################################
}
