<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Condition;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Condition\Product
 */
class Product extends \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Product
{
    //########################################

    protected function getCustomFilters()
    {
        $walmartFilters = [
            'walmart_sku'                  => 'WalmartSku',
            'walmart_gtin'                 => 'WalmartGtin',
            'walmart_upc'                  => 'WalmartUpc',
            'walmart_ean'                  => 'WalmartEan',
            'walmart_isbn'                 => 'WalmartIsbn',
            'walmart_wpid'                 => 'WalmartWpid',
            'walmart_item_id'              => 'WalmartItemId',
            'walmart_online_qty'           => 'WalmartOnlineQty',
            'walmart_online_price'         => 'WalmartOnlinePrice',
            'walmart_start_date'           => 'WalmartStartDate',
            'walmart_end_date'             => 'WalmartEndDate',
            'walmart_status'               => 'WalmartStatus',
            'walmart_details_data_changed' => 'WalmartDetailsDataChanged',
            'walmart_online_price_invalid' => 'WalmartOnlinePriceInvalid',
        ];

        return array_merge_recursive(
            parent::getCustomFilters(),
            $walmartFilters
        );
    }

    /**
     * @param $filterId
     * @param $isReadyToCache
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

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel $model */
        $model = $this->modelFactory->getObject(
            'Walmart\Magento\Product\Rule\Custom\\' . $customFilters[$filterId],
            [
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
        if (is_array($validatedValue) && $this->getAttribute() == 'walmart_online_price') {
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
                        // hack for walmart status
                        if ($this->getAttribute() == 'walmart_status') {
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
                    $result = !empty(array_intersect($validatedValue, (array)$value));
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
