<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface  */
    protected $localeDate;

    protected $filterOperator  = null;
    protected $filterCondition = null;

    //########################################

    public function __construct(
        $filterOperator,
        $filterCondition,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->localeDate      = $localeDate;
        $this->filterOperator  = $filterOperator;
        $this->filterCondition = $filterCondition;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function getAttributeCode();

    abstract public function getLabel();

    abstract public function getValueByProductInstance(\Magento\Catalog\Model\Product $product);

    //########################################

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    //########################################
}
