<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Condition;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Combine
 */
class Combine extends AbstractModel
{
    protected $_logger;
    protected $_useCustomOptions = true;

    protected static $_conditionModels = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        parent::__construct($helperFactory, $modelFactory, $context, $data);

        $this->setType('Magento\Product\Rule\Condition\Combine')
            ->setAggregator('all')
            ->setValue(true)
            ->setConditions([])
            ->setActions([]);

        $this->loadAggregatorOptions();
        if ($options = $this->getAggregatorOptions()) {
            foreach ($options as $aggregator => $dummy) {
                $this->setAggregator($aggregator);
                break;
            }
        }
    }

    //########################################

    public function setStoreId($storeId)
    {
        $this->setData('store_id', $storeId);
        foreach ($this->getConditions() as $condition) {
            $condition->setStoreId($storeId);
        }

        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = [
            [
                'label' => $this->getHelper('Module\Translation')->__('Conditions Combination'),
                'value' => $this->getConditionCombine()
            ]
        ];

        $customAttribute = $this->getCustomOptionsAttributes();
        if ($this->_useCustomOptions && !empty($customAttribute)) {
            $conditions[] = [
                'label' => $this->getCustomLabel(),
                'value' => $this->getCustomOptions()
            ];
        }

        $conditions[] = [
            'label' => $this->getHelper('Module\Translation')->__('Product Attribute'),
            'value' => $this->getProductOptions()
        ];

        return array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
    }

    //########################################

    protected function getConditionCombine()
    {
        return $this->getType();
    }

    // ---------------------------------------

    protected function getCustomLabel()
    {
        return '';
    }

    protected function getCustomOptions()
    {
        return [];
    }

    protected function getCustomOptionsAttributes()
    {
        return [];
    }

    // ---------------------------------------

    protected function getProductOptions()
    {
        $attributes = $this->modelFactory->getObject('Magento_Product_Rule_Condition_Product')->getAttributeOption();
        return  !empty($attributes) ?
                $this->getOptions('Magento\Product\Rule\Condition\Product', $attributes)
                : [];
    }

    // ---------------------------------------

    protected function getOptions($value, array $optionsAttribute, array $params = [])
    {
        $options = [];
        $suffix = (count($params)) ? '|' . implode('|', $params) . '|' : '|';
        foreach ($optionsAttribute as $code => $label) {
            $options[] = [
                'value' => $value . $suffix . $code,
                'label' => $label
            ];
        }

        return $options;
    }

    // ---------------------------------------

    public function setCustomOptionsFlag($flag)
    {
        $this->_useCustomOptions = (bool)$flag;
        return $this;
    }

    //########################################

    public function loadAggregatorOptions()
    {
        $this->setAggregatorOption([
            'all' => __('ALL'),
            'any' => __('ANY'),
        ]);
        return $this;
    }

    public function getAggregatorSelectOptions()
    {
        $opt = [];
        foreach ($this->getAggregatorOption() as $k => $v) {
            $opt[] = ['value'=>$k, 'label'=>$v];
        }
        return $opt;
    }

    public function getAggregatorName()
    {
        return $this->getAggregatorOption($this->getAggregator());
    }

    public function getAggregatorElement()
    {
        if ($this->getAggregator() === null) {
            foreach ($this->getAggregatorOption() as $k => $v) {
                $this->setAggregator($k);
                break;
            }
        }
        return $this->getForm()->addField($this->getPrefix().'__'.$this->getId().'__aggregator', 'select', [
            'name'=>'rule['.$this->getPrefix().']['.$this->getId().'][aggregator]',
            'values'=>$this->getAggregatorSelectOptions(),
            'value'=>$this->getAggregator(),
            'value_name'=>$this->getAggregatorName(),
        ])->setRenderer(
            $this->_layout->getBlockSingleton(\Magento\Rule\Block\Editable::class)
        );
    }

    //########################################

    public function loadValueOptions()
    {
        $this->setValueOption([
            1 => __('TRUE'),
            0 => __('FALSE'),
        ]);
        return $this;
    }

    public function addCondition($condition)
    {
        $condition->setRule($this->getRule());
        $condition->setObject($this->getObject());
        $condition->setPrefix($this->getPrefix());

        $conditions = $this->getConditions();
        $conditions[] = $condition;

        if (!$condition->getId()) {
            $condition->setId($this->getId().'--'.sizeof($conditions));
        }

        $this->setData($this->getPrefix(), $conditions);
        return $this;
    }

    public function getValueElementType()
    {
        return 'select';
    }

    //########################################

    protected function beforeLoadValidate($condition)
    {
        if (empty($condition['attribute'])) {
            return true;
        }

        if (!$this->_useCustomOptions &&
            array_key_exists($condition['attribute'], $this->getCustomOptionsAttributes())) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function loadArray($arr, $key = 'conditions')
    {
        $this->setAggregator(isset($arr['aggregator']) ? $arr['aggregator']
            : (isset($arr['attribute']) ? $arr['attribute'] : null))
            ->setValue(isset($arr['value']) ? $arr['value']
                : (isset($arr['operator']) ? $arr['operator'] : null));

        if (!empty($arr[$key]) && is_array($arr[$key])) {
            foreach ($arr[$key] as $condArr) {
                try {
                    if (!$this->beforeLoadValidate($condArr)) {
                        continue;
                    }

                    $cond = $this->_getNewConditionModelInstance($condArr['type']);
                    if ($cond) {
                        if ($cond instanceof \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Combine) {
                            $cond->setData($this->getPrefix(), []);
                            $cond->setCustomOptionsFlag($this->_useCustomOptions);
                        }

                        $this->addCondition($cond);
                        $cond->loadArray($condArr, $key);
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }
        return $this;
    }

    public function loadXml($xml)
    {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }
        $arr = parent::loadXml($xml);
        foreach ($xml->conditions->children() as $condition) {
            $arr['conditions'] = parent::loadXml($condition);
        }
        $this->loadArray($arr);
        return $this;
    }

    //########################################

    public function asXml($containerKey = 'conditions', $itemKey = 'condition')
    {
        $xml = "<aggregator>".$this->getAggregator()."</aggregator>"
            ."<value>".$this->getValue()."</value>"
            ."<$containerKey>";
        foreach ($this->getConditions() as $condition) {
            $xml .= "<$itemKey>".$condition->asXml()."</$itemKey>";
        }
        $xml .= "</$containerKey>";
        return $xml;
    }

    public function asArray(array $arrAttributes = [])
    {
        $out = parent::asArray();
        $out['aggregator'] = $this->getAggregator();

        foreach ($this->getConditions() as $condition) {
            $out['conditions'][] = $condition->asArray();
        }

        return $out;
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml().
            $this->getHelper('Module\Translation')->__(
                'If %rule% of these Conditions are %value%:',
                $this->getAggregatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
            );

        if ($this->getId() != '1') {
            $html.= $this->getRemoveLinkHtml();
        }
        return $html;
    }

    public function asHtmlRecursive()
    {
        $html = $this->asHtml().
                '<ul id="'.$this->getPrefix().'__'.$this->getId().'__children" class="rule-param-children">';
        foreach ($this->getConditions() as $cond) {
            $html .= '<li>'.$cond->asHtmlRecursive().'</li>';
        }
        $html .= '<li>'.$this->getNewChildElement()->getHtml().'</li></ul>';
        return $html;
    }

    public function asString($format = '')
    {
        $str = $this->getHelper('Module\Translation')->__(
            "If %rule% of these Conditions are %value%:",
            $this->getAggregatorName(),
            $this->getValueName()
        );
        return $str;
    }

    public function asStringRecursive($level = 0)
    {
        $str = parent::asStringRecursive($level);
        foreach ($this->getConditions() as $cond) {
            $str .= "\n".$cond->asStringRecursive($level+1);
        }
        return $str;
    }

    //########################################

    public function getNewChildElement()
    {
        return $this->getForm()->addField($this->getPrefix().'__'.$this->getId().'__new_child', 'select', [
            'name'=>'rule['.$this->getPrefix().']['.$this->getId().'][new_child]',
            'values'=>$this->getNewChildSelectOptions(),
            'value_name'=>$this->getNewChildName(),
        ])->setRenderer(
            $this->_layout->getBlockSingleton(\Magento\Rule\Block\Newchild::class)
        );
    }

    public function validate(\Magento\Framework\DataObject $object)
    {
        if (!$this->getConditions()) {
            return true;
        }

        $all    = $this->getAggregator() === 'all';
        $true   = (bool)$this->getValue();

        foreach ($this->getConditions() as $cond) {
            $validated = $cond->validate($object);

            if ($all && $validated !== $true) {
                return false;
            } elseif (!$all && $validated === $true) {
                return true;
            }
        }
        return $all ? true : false;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }

    public function setJsFormObject($form)
    {
        $this->setData('js_form_object', $form);
        foreach ($this->getConditions() as $condition) {
            $condition->setJsFormObject($form);
        }
        return $this;
    }

    public function getConditions()
    {
        $key = $this->getPrefix() ? $this->getPrefix() : 'conditions';
        return $this->getData($key);
    }

    public function setConditions($conditions)
    {
        $key = $this->getPrefix() ? $this->getPrefix() : 'conditions';
        return $this->setData($key, $conditions);
    }

    public function getConditionModels()
    {
        return self::$_conditionModels;
    }

    //########################################

    protected function _getRecursiveChildSelectOption()
    {
        return ['value' => $this->getType(), 'label' => __('Conditions Combination')];
    }

    protected function _getNewConditionModelInstance($modelClass)
    {
        if (empty($modelClass)) {
            return false;
        }

        if (!array_key_exists($modelClass, self::$_conditionModels)) {
            $model = $this->modelFactory->getObject($modelClass);
            self::$_conditionModels[$modelClass] = $model;
        } else {
            $model = self::$_conditionModels[$modelClass];
        }

        if (!$model) {
            return false;
        }

        $newModel = clone $model;
        return $newModel;
    }

    //########################################
}
