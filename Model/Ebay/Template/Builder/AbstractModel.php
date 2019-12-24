<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Builder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Builder\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
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

    abstract public function build(array $data);

    //########################################

    protected function validate(array $data)
    {
        if (!isset($data['is_custom_template'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy mode is empty.');
        }
    }

    protected function prepareData(array &$data)
    {
        $prepared = [];

        // ---------------------------------------
        if (isset($data['id']) && (int)$data['id'] > 0) {
            $prepared['id'] = (int)$data['id'];
        }

        $prepared['is_custom_template'] = (int)(bool)$data['is_custom_template'];
        $prepared['title'] = $data['title'];
        // ---------------------------------------

        // ---------------------------------------
        unset($data['id']);
        unset($data['is_custom_template']);
        unset($data['title']);
        // ---------------------------------------

        return $prepared;
    }

    //########################################
}
