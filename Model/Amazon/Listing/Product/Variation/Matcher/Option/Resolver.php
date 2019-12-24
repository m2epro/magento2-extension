<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option\Resolver
 */
class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    private $sourceOption = [];

    private $destinationOptions = [];

    private $matchedAttributes = [];

    private $resolvedGeneralId = null;

    //########################################

    /**
     * @param array $options
     * @return $this
     */
    public function setSourceOption(array $options)
    {
        $this->sourceOption      = $options;
        $this->resolvedGeneralId = null;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setDestinationOptions(array $options)
    {
        $this->destinationOptions = $options;
        $this->resolvedGeneralId  = null;

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
        foreach ($this->destinationOptions as $generalId => $destinationOption) {
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
                $this->resolvedGeneralId = $generalId;
                break;
            }
        }

        return $this;
    }

    public function getResolvedGeneralId()
    {
        return $this->resolvedGeneralId;
    }

    //########################################
}
