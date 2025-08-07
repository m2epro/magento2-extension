<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Condition;

use Magento\Rule\Model\Condition\ConditionInterface;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Condition\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel implements ConditionInterface
{
    /**
     * Defines which operators will be available for this condition
     * @var string
     */
    protected $_inputType = null;

    /**
     * Default values for possible operator options
     * @var array
     */
    protected $_defaultOperatorOptions = null;

    /**
     * Default combinations of operator options, depending on input type
     * @var array
     */
    protected $_defaultOperatorInputByType = null;

    /**
     * List of input types for values which should be array
     * @var array
     */
    protected $_arrayInputTypes = [];

    protected $_assetRepo;
    protected $_localeDate;
    protected $_layout;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->_assetRepo = $context->getAssetRepository();
        $this->_localeDate = $context->getLocaleDate();
        $this->_layout = $context->getLayout();

        parent::__construct($helperFactory, $modelFactory, $data);

        $this->loadAttributeOptions()->loadOperatorOptions()->loadValueOptions();

        if ($options = $this->getAttributeOptions()) {
            foreach ($options as $attr => $dummy) {
                $this->setAttribute($attr);
                break;
            }
        }
        if ($options = $this->getOperatorOptions()) {
            foreach ($options as $operator => $dummy) {
                $this->setOperator($operator);
                break;
            }
        }
    }

    /**
     * Default operator input by type map getter
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            $this->_defaultOperatorInputByType = [
                'string' => ['==', '!=', '>=', '>', '<=', '<', '{}', '!{}', '()', '!()', '??', '!??'],
                'numeric' => ['==', '!=', '>=', '>', '<=', '<', '()', '!()'],
                'date' => ['==', '>=', '<='],
                'select' => ['==', '!='],
                'boolean' => ['==', '!='],
                'multiselect' => ['{}', '!{}', '()', '!()'],
                'grid' => ['()', '!()'],
            ];
            $this->_arrayInputTypes = ['multiselect', 'grid'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Default operator options getter
     * Provides all possible operator options
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = [
                '==' => __('is'),
                '!=' => __('is not'),
                '>=' => __('equals or greater than'),
                '<=' => __('equals or less than'),
                '>' => __('greater than'),
                '<' => __('less than'),
                '{}' => __('contains'),
                '!{}' => __('does not contain'),
                '()' => __('is one of'),
                '!()' => __('is not one of'),
                '??' => __('is empty'),
                '!??' => __('is not empty'),
            ];
        }

        return $this->_defaultOperatorOptions;
    }

    public function getForm()
    {
        return $this->getRule()->getForm();
    }

    /**
     * @param array $arrAttributes
     *
     * @return array
     */
    public function asArray(array $arrAttributes = [])
    {
        $out = [
            'type' => $this->getType(),
            'attribute' => $this->getAttribute(),
            'operator' => $this->getOperator(),
            'value' => $this->getValue(),
            'is_value_processed' => $this->getIsValueParsed(),
        ];

        return $out;
    }

    public function getTablesToJoin()
    {
        return [];
    }

    public function getBindArgumentValue()
    {
        return $this->getValueParsed();
    }

    public function getMappedSqlField()
    {
        return $this->getAttribute();
    }

    /**
     * @return string
     */
    public function asXml()
    {
        $xml = "<type>" . $this->getType() . "</type>"
            . "<attribute>" . $this->getAttribute() . "</attribute>"
            . "<operator>" . $this->getOperator() . "</operator>"
            . "<value>" . $this->getValue() . "</value>";

        return $xml;
    }

    public function loadArray($arr)
    {
        $this->setType($arr['type']);
        $this->setAttribute(isset($arr['attribute']) ? $arr['attribute'] : false);
        $this->setOperator(isset($arr['operator']) ? $arr['operator'] : false);
        $this->setValue(isset($arr['value']) ? $arr['value'] : false);
        $this->setIsValueParsed(isset($arr['is_value_parsed']) ? $arr['is_value_parsed'] : false);

        return $this;
    }

    public function loadXml($xml)
    {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }
        $arr = (array)$xml;
        $this->loadArray($arr);

        return $this;
    }

    public function loadAttributeOptions()
    {
        return $this;
    }

    public function getAttributeOptions()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAttributeSelectOptions()
    {
        $opt = [];
        foreach ($this->getAttributeOption() as $k => $v) {
            $opt[] = ['value' => $k, 'label' => $v];
        }

        return $opt;
    }

    public function getAttributeName()
    {
        return $this->getAttributeOption()[$this->getAttribute()] ?? '';
    }

    public function loadOperatorOptions()
    {
        $this->setOperatorOption($this->getDefaultOperatorOptions());
        $this->setOperatorByInputType($this->getDefaultOperatorInputByType());

        return $this;
    }

    /**
     * This value will define which operators will be available for this condition.
     * Possible values are: string, numeric, date, select, multiselect, grid, boolean
     * @return string
     */
    public function getInputType()
    {
        if (null === $this->_inputType) {
            return 'string';
        }

        return $this->_inputType;
    }

    /**
     * @return array
     */
    public function getOperatorSelectOptions()
    {
        $type = $this->getInputType();
        $opt = [];
        $operatorByType = $this->getOperatorByInputType();
        foreach ($this->getOperatorOption() as $k => $v) {
            if (!$operatorByType || in_array($k, $operatorByType[$type])) {
                $opt[] = ['value' => $k, 'label' => $v];
            }
        }

        return $opt;
    }

    public function getOperatorName()
    {
        return $this->getOperatorOption($this->getOperator());
    }

    public function loadValueOptions()
    {
        $this->setValueOption([]);

        return $this;
    }

    public function getValueSelectOptions()
    {
        $valueOption = $opt = [];
        if ($this->hasValueOption()) {
            $valueOption = (array)$this->getValueOption();
        }
        foreach ($valueOption as $k => $v) {
            $opt[] = ['value' => $k, 'label' => $v];
        }

        return $opt;
    }

    /**
     * Retrieve parsed value
     * @return array|string|int|float
     */
    public function getValueParsed()
    {
        if (!$this->hasValueParsed()) {
            $value = $this->getData('value');
            if ($this->isArrayOperatorType() && is_string($value)) {
                $value = preg_split('#\s*[,;]\s*#', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
            $this->setValueParsed($value);
        }

        return $this->getData('value_parsed');
    }

    /**
     * Check if value should be array
     * Depends on operator input type
     * @return bool
     */
    public function isArrayOperatorType()
    {
        $op = $this->getOperator();

        return $op === '()' || $op === '!()' || in_array($this->getInputType(), $this->_arrayInputTypes);
    }

    public function getValue()
    {
        return $this->getData('value');
    }

    public function getValueName()
    {
        $value = $this->getValue();
        if ($value === null || '' === $value) {
            return '...';
        }

        $options = $this->getValueSelectOptions();
        $valueArr = [];
        if (!empty($options)) {
            foreach ($options as $o) {
                if (is_array($value)) {
                    if (in_array($o['value'], $value)) {
                        $valueArr[] = $o['label'];
                    }
                } else {
                    if (is_array($o['value'])) {
                        foreach ($o['value'] as $v) {
                            if ($v['value'] == $value) {
                                return $v['label'];
                            }
                        }
                    }
                    if ($o['value'] == $value) {
                        return $o['label'];
                    }
                }
            }
        }
        if (!empty($valueArr)) {
            $value = implode(', ', $valueArr);
        }

        return $value;
    }

    /**
     * Get inherited conditions selectors
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [
            ['value' => '', 'label' => __('Please choose a Condition to add...')],
        ];
    }

    public function getNewChildName()
    {
        return $this->getAddLinkHtml();
    }

    public function asHtml()
    {
        $html = $this->getTypeElementHtml()
            . $this->getAttributeElementHtml()
            . $this->getOperatorElementHtml()
            . $this->getValueElementHtml()
            . $this->getRemoveLinkHtml()
            . $this->getChooserContainerHtml();

        return $html;
    }

    public function asHtmlRecursive()
    {
        $html = $this->asHtml();

        return $html;
    }

    public function getTypeElement()
    {
        return $this->getForm()->addField(
            $this->getPrefix() . '__' . $this->getId() . '__type',
            'hidden',
            [
                'name' => 'rule[' . $this->getPrefix() . '][' . $this->getId() . '][type]',
                'value' => $this->getType(),
                'no_span' => true,
                'class' => 'hidden',
            ]
        );
    }

    public function getTypeElementHtml()
    {
        return $this->getTypeElement()->getHtml();
    }

    public function getAttributeElement()
    {
        if ($this->getAttribute() === null) {
            foreach ($this->getAttributeOption() as $k => $v) {
                $this->setAttribute($k);
                break;
            }
        }

        return $this->getForm()->addField($this->getPrefix() . '__' . $this->getId() . '__attribute', 'select', [
            'name' => 'rule[' . $this->getPrefix() . '][' . $this->getId() . '][attribute]',
            'values' => $this->getAttributeSelectOptions(),
            'value' => $this->getAttribute(),
            'value_name' => $this->getAttributeName(),
        ])->setRenderer(
            $this->_layout->getBlockSingleton(\Magento\Rule\Block\Editable::class)
        );
    }

    public function getAttributeElementHtml()
    {
        return $this->getAttributeElement()->getHtml();
    }

    /**
     * Retrieve Condition Operator element Instance
     * If the operator value is empty - define first available operator value as default
     * @return \Magento\Framework\Data\Form\Element\Select
     */
    public function getOperatorElement()
    {
        $options = $this->getOperatorSelectOptions();
        if ($this->getOperator() === null) {
            foreach ($options as $option) {
                $this->setOperator($option['value']);
                break;
            }
        }

        $elementId = sprintf('%s__%s__operator', $this->getPrefix(), $this->getId());
        $elementName = sprintf('rule[%s][%s][operator]', $this->getPrefix(), $this->getId());
        $element = $this->getForm()->addField($elementId, 'select', [
            'name' => $elementName,
            'values' => $options,
            'value' => $this->getOperator(),
            'value_name' => $this->getOperatorName(),
        ]);
        $element->setRenderer($this->_layout->getBlockSingleton(\Magento\Rule\Block\Editable::class));

        return $element;
    }

    public function getOperatorElementHtml()
    {
        return $this->getOperatorElement()->getHtml();
    }

    /**
     * Value element type will define renderer for condition value element
     * @return string
     * @see Varien_Data_Form_Element
     */
    public function getValueElementType()
    {
        return 'text';
    }

    public function getValueElementRenderer()
    {
        if (strpos($this->getValueElementType(), '/') !== false) {
            return $this->_layout->getBlockSingleton($this->getValueElementType());
        }

        return $this->_layout->getBlockSingleton(\Magento\Rule\Block\Editable::class);
    }

    public function getValueElement()
    {
        $elementParams = [
            'name' => 'rule[' . $this->getPrefix() . '][' . $this->getId() . '][value]',
            'value' => $this->getValue(),
            'values' => $this->getValueSelectOptions(),
            'value_name' => $this->getValueName(),
            'after_element_html' => $this->getValueAfterElementHtml(),
            'explicit_apply' => $this->getExplicitApply(),
        ];
        if ($this->getInputType() == 'date') {
            // date format intentionally hard-coded
            $elementParams['date_format'] = $this->_localeDate->getDateFormat(\IntlDateFormatter::MEDIUM);
            $elementParams['time_format'] = $this->_localeDate->getTimeFormat(\IntlDateFormatter::MEDIUM);
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->getForm();
        $field = $form->addField(
            $this->getPrefix() . '__' . $this->getId() . '__value',
            $this->getValueElementType(),
            $elementParams
        );

        if ($this->getInputType() == 'date') {
            $value = $this->getValue();
            if ($value !== null) {
                $timestamp = \Ess\M2ePro\Helper\Date::parseDateFromLocalFormat(
                    $value,
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM
                );
                $dateTime = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
                $dateTime->setTimestamp($timestamp);
                $field->setValue($dateTime);
            }
        }

        $field->setRenderer($this->getValueElementRenderer());

        return $field;
    }

    public function getValueElementHtml()
    {
        return $this->getValueElement()->getHtml();
    }

    public function getAddLinkHtml()
    {
        $src = $this->_assetRepo->getUrl('images/rule_component_add.gif');
        $html = '<img src="' . $src . '" class="rule-param-add v-middle" alt=""
                                         title="' . __('Add') . '"/>';

        return $html;
    }

    public function getRemoveLinkHtml()
    {
        $src = $this->_assetRepo->getUrl('images/rule_component_remove.gif');
        $html = ' <span class="rule-param">
                     <a href="javascript:void(0)" class="rule-param-remove"
                                                  title="' . __('Remove') . '">
                        <img src="' . $src . '" alt="" class="v-middle" />
                     </a>
                  </span>';

        return $html;
    }

    public function getChooserContainerHtml()
    {
        $url = $this->getValueElementChooserUrl();
        $html = '';
        if ($url) {
            $html = '<div class="rule-chooser" url="' . $url . '"></div>';
        }

        return $html;
    }

    public function asString($format = '')
    {
        $str = $this->getAttributeName() . ' ' . $this->getOperatorName() . ' ' . $this->getValueName();

        return $str;
    }

    public function asStringRecursive($level = 0)
    {
        $str = str_pad('', $level * 3, ' ', STR_PAD_LEFT) . $this->asString();

        return $str;
    }

    /**
     * Validate product attrbute value for condition
     *
     * @param mixed $validatedValue product attribute value
     *
     * @return  bool
     */
    public function validateAttribute($validatedValue)
    {
        if (is_object($validatedValue)) {
            return false;
        }

        /**
         * Condition attribute value
         */
        $value = $this->getValueParsed();

        /**
         * Comparison operator
         */
        $op = $this->getOperatorForValidate();

        // if operator requires array and it is not, or on opposite, return false
        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        $result = false;

        switch ($op) {
            case '==':
            case '!=':
                if (is_array($value)) {
                    if (is_array($validatedValue)) {
                        $result = array_intersect($value, $validatedValue);
                        $result = !empty($result);
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($validatedValue)) {
                        $result = count($validatedValue) == 1 && array_shift($validatedValue) == $value;
                    } else {
                        $result = $this->_compareValues($validatedValue, $value);
                    }
                }
                break;

            case '<=':
            case '>':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = $validatedValue <= $value;
                }
                break;

            case '>=':
            case '<':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = $validatedValue >= $value;
                }
                break;

            case '{}':
            case '!{}':
                if (is_scalar($validatedValue) && is_array($value)) {
                    foreach ($value as $item) {
                        if (stripos($validatedValue, $item) !== false) {
                            $result = true;
                            break;
                        }
                    }
                } elseif (is_array($value)) {
                    if (is_array($validatedValue)) {
                        $result = array_intersect($value, $validatedValue);
                        $result = !empty($result);
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($validatedValue)) {
                        $result = in_array($value, $validatedValue);
                    } else {
                        $result = $this->_compareValues($value, $validatedValue, false);
                    }
                }
                break;

            case '()':
            case '!()':
                if (is_array($validatedValue)) {
                    $result = count(array_intersect($validatedValue, (array)$value)) > 0;
                } else {
                    $value = (array)$value;
                    foreach ($value as $item) {
                        if ($this->_compareValues($validatedValue, $item)) {
                            $result = true;
                            break;
                        }
                    }
                }
                break;
            case '??':
            case '!??':
                $result = empty($validatedValue);
                break;
        }

        if ('!=' == $op || '>' == $op || '<' == $op || '!{}' == $op || '!()' == $op || '!??' == $op) {
            $result = !$result;
        }

        return $result;
    }

    /**
     * Case and type insensitive comparison of values
     *
     * @param string|int|float $validatedValue
     * @param string|int|float $value
     * @param bool $strict
     *
     * @return bool
     */
    protected function _compareValues($validatedValue, $value, $strict = true)
    {
        if ($strict && is_numeric($validatedValue) && is_numeric($value)) {
            return $validatedValue == $value;
        } else {
            $validatedValue = (string)$validatedValue;
            $validatePattern = preg_quote($validatedValue, '~');
            if ($strict) {
                $validatePattern = '^' . $validatePattern . '$';
            }
            try {
                $result = (bool)preg_match('~' . $validatePattern . '~iu', $value);
            } catch (\Exception $e) {
                return false;
            }

            return $result;
        }
    }

    public function validate(\Magento\Framework\DataObject $object)
    {
        return $this->validateAttribute($object->getData($this->getAttribute()));
    }

    /**
     * Retrieve operator for php validation
     * @return string
     */
    public function getOperatorForValidate()
    {
        return $this->getOperator();
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     *
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    // ----------------------------------------

    protected function setAttributeOption(array $attributes): void
    {
        $this->setData('attribute_option', $attributes);
    }

    protected function getAttributeOption(): array
    {
        return $this->getData('attribute_option') ?: [];
    }

    protected function isAttributeExist(): bool
    {
        return array_key_exists($this->getAttribute(), $this->getAttributeOption());
    }

    protected function setAttribute(string $attribute): void
    {
        $this->setData('attribute', $attribute);
    }

    protected function getAttribute()
    {
        return $this->getData('attribute');
    }
}
