<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Option;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Option\Resolver
 */
class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    private $sourceOption = [];

    private $destinationOptions = [];

    private $matchedAttributes = [];

    private $resolvedOption = null;

    //########################################

    /**
     * @param array $options
     * @return $this
     */
    public function setSourceOption(array $options)
    {
        $this->sourceOption = $options;
        $this->resolvedOption = null;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setDestinationOptions(array $options)
    {
        $this->destinationOptions = $options;
        $this->resolvedOption = null;

        return $this;
    }

    // ---------------------------------------

    /**
     * @param array $matchedAttributes
     * @return $this
     */
    public function setMatchedAttributes(array $matchedAttributes)
    {
        $this->matchedAttributes = $matchedAttributes;
        return $this;
    }

    //########################################

    /**
     * @return $this
     */
    public function resolve()
    {
        foreach ($this->destinationOptions as $destinationOption) {
            if (count($this->sourceOption) != count($destinationOption)) {
                continue;
            }

            $isResolved = false;

            foreach ($destinationOption as $destinationAttribute => $destinationOptionNames) {
                $sourceAttribute = array_search($destinationAttribute, $this->matchedAttributes);
                $sourceOptionNames = $this->sourceOption[$sourceAttribute];

                if (!empty(array_intersect((array)$sourceOptionNames, (array)$destinationOptionNames))) {
                    $isResolved = true;
                    continue;
                }

                $isResolved = false;
                break;
            }

            if ($isResolved) {
                $this->resolvedOption = $destinationOption;
                break;
            }
        }

        return $this;
    }

    public function getResolvedOption()
    {
        return $this->resolvedOption;
    }

    //########################################
}
