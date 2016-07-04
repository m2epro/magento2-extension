<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

class Custom extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $query = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = (string)$query;
        return $this;
    }

    //########################################

    public function process()
    {
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon\Search\Custom\\'.ucfirst($this->getSearchMethod()).'\Requester',
            $this->getConnectorParams(), $this->listingProduct->getAccount()
        );

        $dispatcherObject->process($connectorObj);
        return $this->prepareResult($connectorObj->getPreparedResponseData());
    }

    //########################################

    private function getConnectorParams()
    {
        $searchMethod = $this->getSearchMethod();

        /** @var \Ess\M2ePro\Model\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $isModifyChildToSimple = !$amazonListingProduct->getVariationManager()->isRelationParentType();

        $params = array(
            'variation_bad_parent_modify_child_to_simple' => $isModifyChildToSimple,
        );

        if ($searchMethod == 'byQuery') {
            $params['query'] = $this->query;
        } else {
            $params['query'] = $this->getStrippedQuery();
        }

        if ($searchMethod == 'byIdentifier') {
            $params['query_type'] = $this->getIdentifierType();
        }

        return $params;
    }

    private function getSearchMethod()
    {
        $validationHelper = $this->getHelper('Data');
        $amazonHelper     = $this->getHelper('Component\Amazon');
        $strippedQuery    = $this->getStrippedQuery();

        if ($amazonHelper->isASIN($strippedQuery)) {
            return 'byAsin';
        }

        if ($validationHelper->isEAN($strippedQuery) ||
            $validationHelper->isUPC($strippedQuery) ||
            $validationHelper->isISBN($strippedQuery)
        ) {
            return 'byIdentifier';
        }

        return 'byQuery';
    }

    private function getIdentifierType()
    {
        $query = $this->getStrippedQuery();

        $validationHelper = $this->getHelper('Data');

        return ($this->getHelper('Component\Amazon')->isASIN($query) ? 'ASIN' :
               ($validationHelper->isISBN($query)                    ? 'ISBN' :
               ($validationHelper->isUPC($query)                     ? 'UPC'  :
               ($validationHelper->isEAN($query)                     ? 'EAN'  : false))));
    }

    private function prepareResult($searchData)
    {
        $connectorParams = $this->getConnectorParams();

        if ($this->getSearchMethod() == 'byQuery') {
            $type = 'string';
        } else {
            $type = $this->getIdentifierType();
        }

        if ($searchData !== false && $this->getSearchMethod() == 'byAsin') {
            if (is_null($searchData)) {
                $searchData = array();
            } else {
                $searchData = array($searchData);
            }
        }

        return array(
            'type'  => $type,
            'value' => $connectorParams['query'],
            'data'  => $searchData,
        );
    }

    private function getStrippedQuery()
    {
        return str_replace('-', '', $this->query);
    }

    //########################################
}