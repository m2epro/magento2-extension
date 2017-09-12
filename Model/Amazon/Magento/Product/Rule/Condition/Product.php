<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Condition;

class Product extends \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Product
{
    //########################################

    protected function getCustomFilters()
    {
        $amazonFilters = array(
            'amazon_sku'               => 'AmazonSku',
            'amazon_general_id'        => 'AmazonGeneralId',
            'amazon_online_qty'        => 'AmazonOnlineQty',
            'amazon_online_price'      => 'AmazonOnlinePrice',
            'amazon_online_sale_price' => 'AmazonOnlineSalePrice',
            'amazon_is_afn_chanel'     => 'AmazonIsAfnChanel',
            'amazon_is_repricing'      => 'AmazonIsRepricing',
            'amazon_status'            => 'AmazonStatus',
            'amazon_general_id_state'  => 'AmazonGeneralIdState'
        );

        return array_merge_recursive(
            parent::getCustomFilters(),
            $amazonFilters
        );
    }

    /**
     * @param $filterId
     * @return \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
     */
    protected function getCustomFilterInstance($filterId, $isReadyToCache = true)
    {
        $parentFilters = parent::getCustomFilters();
        if (isset($parentFilters[$filterId])) {
            return parent::getCustomFilterInstance($filterId, $isReadyToCache);
        }

        $customFilters = $this->getCustomFilters();
        if (!isset($customFilters[$filterId])) {
            return null;
        }

        if (isset($this->_customFiltersCache[$filterId])) {
            return $this->_customFiltersCache[$filterId];
        }

        $model = $this->modelFactory->getObject(
            'Amazon\Magento\Product\Rule\Custom\\'.$customFilters[$filterId], [
                'filterOperator'  => $this->getData('operator'),
                'filterCondition' => $this->getData('value')
            ]
        );

        $isReadyToCache && $this->_customFiltersCache[$filterId] = $model;
        return $model;
    }

    /**
     * If param is array validate each values till first true result
     *
     * @param   mixed $validatedValue product attribute value
     * @return  bool
     */

    public function validateAttribute($validatedValue)
    {
        if (is_array($validatedValue) && $this->getAttribute() == 'amazon_online_price') {
            $result = false;

            foreach ($validatedValue as $value) {
                $result = $this->validateAttribute($value);
                if ($result) {
                    break;
                }
            }

            return $result;
        }

        if (is_object($validatedValue)) {
            return false;
        }

        if ($this->getInputType() == 'date' && !empty($validatedValue) && !is_numeric($validatedValue)) {
            $validatedValue = strtotime($validatedValue);
        }

        /**
         * Condition attribute value
         */
        $value = $this->getValueParsed();

        if ($this->getInputType() == 'date' && !empty($value) && !is_numeric($value)) {
            $value = strtotime($value);
        }

        // Comparison operator
        $op = $this->getOperatorForValidate();

        // if operator requires array and it is not, or on opposite, return false
        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        $result = false;

        switch ($op) {
            case '==': case '!=':
            if (is_array($value)) {
                if (is_array($validatedValue)) {
                    $result = array_intersect($value, $validatedValue);
                    $result = !empty($result);
                } else {
                    return false;
                }
            } else {
                if (is_array($validatedValue)) {

                    // hack for amazon status
                    if ($this->getAttribute() == 'amazon_status') {
                        if ($op == '==') {
                            $result = !empty($validatedValue[$value]);
                        } else {
                            $result = true;
                            foreach ($validatedValue as $status => $childrenCount) {
                                if ($status != $value && !empty($childrenCount)) {
                                    // will be true at the end of this method
                                    $result = false;
                                    break;
                                }
                            }
                        }
                    } else {
                        $result = count($validatedValue) == 1 && array_shift($validatedValue) == $value;
                    }
                } else {
                    $result = $this->_compareValues($validatedValue, $value);
                }
            }
            break;

            case '<=': case '>':
            if (!is_scalar($validatedValue)) {
                return false;
            } else {
                $result = $validatedValue <= $value;
            }
            break;

            case '>=': case '<':
            if (!is_scalar($validatedValue)) {
                return false;
            } else {
                $result = $validatedValue >= $value;
            }
            break;

            case '{}': case '!{}':
            if (is_scalar($validatedValue) && is_array($value)) {
                foreach ($value as $item) {
                    if (stripos($validatedValue,$item)!==false) {
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

            case '()': case '!()':
            if (is_array($validatedValue)) {
                $result = count(array_intersect($validatedValue, (array)$value))>0;
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
        }

        if ('!=' == $op || '>' == $op || '<' == $op || '!{}' == $op || '!()' == $op) {
            $result = !$result;
        }

        return $result;
    }

    //########################################
}