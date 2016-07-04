<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option;

class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    private $sourceOption = array();

    private $destinationOptions = array();

    private $matchedAttributes = array();

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

                if (count(array_intersect((array)$sourceOptionNames, (array)$destinationOptionNames)) > 0) {
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