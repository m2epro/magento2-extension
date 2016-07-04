<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute;

class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    private $sourceAttributes = array();

    private $sourceAttributesNames = array();

    private $destinationAttributes = array();

    private $destinationAttributesNames = array();

    private $resolvedAttributes = array();

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
            $this->resolvedAttributes = array();
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

                if (count(array_intersect($sourceNames, $destinationNames)) > 0 &&
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
        $this->sourceAttributes = array();
        $this->sourceAttributesNames = array();

        return $this;
    }

    /**
     * @return $this
     */
    public function clearDestinationAttributes()
    {
        $this->destinationAttributes = array();
        $this->destinationAttributesNames = array();

        return $this;
    }

    //########################################
}