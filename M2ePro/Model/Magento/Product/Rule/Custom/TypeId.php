<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Custom\TypeId
 */
class TypeId extends AbstractModel
{
    protected $type;

    //########################################

    public function __construct(
        $filterOperator,
        $filterCondition,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterOperator,
            $filterCondition,
            $localeDate,
            $helperFactory,
            $modelFactory,
            $data
        );
        $this->type = $type;
    }

    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'type_id';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getHelper('Module\Translation')->__('Product Type');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getTypeId();
    }

    //########################################

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $magentoProductTypes = $this->type->getOptionArray();
        $knownTypes = $this->getHelper('Magento\Product')->getOriginKnownTypes();

        $options = [];
        foreach ($magentoProductTypes as $type => $magentoProductTypeLabel) {
            if (!in_array($type, $knownTypes)) {
                continue;
            }

            $options[] = [
                'value' => $type,
                'label' => $magentoProductTypeLabel
            ];
        }

        return $options;
    }

    //########################################
}
