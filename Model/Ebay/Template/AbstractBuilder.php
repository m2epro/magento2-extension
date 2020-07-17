<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
 */
abstract class AbstractBuilder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    protected $activeRecordFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function validate()
    {
        if (!isset($this->rawData['is_custom_template'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy mode is empty.');
        }
    }

    protected function prepareData()
    {
        $data = [];

        // ---------------------------------------
        if (isset($this->rawData['id']) && (int)$this->rawData['id'] > 0) {
            $data['id'] = (int)$this->rawData['id'];
        }

        $data['is_custom_template'] = (int)(bool)$this->rawData['is_custom_template'];
        $data['title'] = $this->rawData['title'];
        // ---------------------------------------

        // ---------------------------------------
        unset($this->rawData['id']);
        unset($this->rawData['is_custom_template']);
        unset($this->rawData['title']);
        // ---------------------------------------

        return $data;
    }

    //########################################
}
