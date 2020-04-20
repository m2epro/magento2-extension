<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute\Resolver
 */
class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    private $sourceAttributes = [];

    private $sourceAttributesNames = [];

    private $destinationAttributes = [];

    private $destinationAttributesNames = [];

    private $resolvedAttributes = [];

    //########################################

    /**
     * @param $attribute
     * @param array $names
     * @return $this
     */
    public function addSourceAttribute($attribute, array $names)
    {
        if (in_array($attribute, $this->sourceAttributes)) {
            return $this;
        }

        $this->sourceAttributes[] = $attribute;
        $this->sourceAttributesNames[$attribute] = $names;

        return $this;
    }

    /**
     * @param $attribute
     * @param array $names
     * @return $this
     */
    public function addDestinationAttribute($attribute, array $names)
    {
        if (in_array($attribute, $this->destinationAttributes)) {
            return $this;
        }

        $this->destinationAttributes[] = $attribute;
        $this->destinationAttributesNames[$attribute] = $names;

        return $this;
    }

    //########################################

    /**
     * @return $this
     */
    public function resolve()
    {
        if (array_diff($this->sourceAttributes, array_keys($this->resolvedAttributes))) {
            $this->resolvedAttributes = [];
        }

        foreach ($this->sourceAttributes as $sourceAttribute) {
            if (!empty($this->resolvedAttributes[$sourceAttribute]) &&
                in_array($this->resolvedAttributes[$sourceAttribute], $this->destinationAttributes)
            ) {
                continue;
            }

            $this->resolvedAttributes[$sourceAttribute] = null;

            $sourceNames = $this->sourceAttributesNames[$sourceAttribute];

            foreach ($this->destinationAttributes as $destinationAttribute) {
                $destinationNames = $this->destinationAttributesNames[$destinationAttribute];

                if (!empty(array_intersect($sourceNames, $destinationNames)) &&
                    !in_array($destinationAttribute, $this->resolvedAttributes)
                ) {
                    $this->resolvedAttributes[$sourceAttribute] = $destinationAttribute;
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getResolvedAttributes()
    {
        return $this->resolvedAttributes;
    }

    //########################################

    /**
     * @return $this
     */
    public function clearSourceAttributes()
    {
        $this->sourceAttributes = [];
        $this->sourceAttributesNames = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function clearDestinationAttributes()
    {
        $this->destinationAttributes = [];
        $this->destinationAttributesNames = [];

        return $this;
    }

    //########################################
}
