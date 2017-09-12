<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

class Categories extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplate = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $otherCategoryTemplate = NULL;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = array();

        if ($this->getConfigurator()->isGeneralAllowed()) {

            $data = array_merge(
                $data,
                $this->getCategoriesData()
            );

            if ($this->getEbayListing()->isPartsCompatibilityModeEpids()) {

                $motorsType = $this->getHelper('Component\Ebay\Motors')->getEpidsTypeByMarketplace(
                    $this->getMarketplace()->getId()
                );
                $tempData = $this->getMotorsData($motorsType);
                $tempData !== false && $data['motors_epids'] = $tempData;
            }

            if ($this->getEbayListing()->isPartsCompatibilityModeKtypes()) {

                $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
                $tempData = $this->getMotorsData($motorsType);
                $tempData !== false && $data['motors_ktypes'] = $tempData;
            }
        }

        if ($this->getConfigurator()->isSpecificsAllowed()) {
            $data['item_specifics'] = $this->getItemSpecificsData();
        }

        return $data;
    }

    //########################################

    /**
     * @return array
     */
    public function getCategoriesData()
    {
        $data = array(
            'category_main_id' => $this->getCategorySource()->getMainCategory(),
            'category_secondary_id' => 0,
            'store_category_main_id' => 0,
            'store_category_secondary_id' => 0
        );

        if (!is_null($this->getOtherCategoryTemplate())) {
            $data['category_secondary_id'] = $this->getOtherCategorySource()->getSecondaryCategory();
            $data['store_category_main_id'] = $this->getOtherCategorySource()->getStoreCategoryMain();
            $data['store_category_secondary_id'] = $this->getOtherCategorySource()->getStoreCategorySecondary();
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getItemSpecificsData()
    {
        $data = array();

        foreach ($this->getCategoryTemplate()->getSpecifics(true) as $specific) {

            /** @var $specific \Ess\M2ePro\Model\Ebay\Template\Category\Specific */

            $this->searchNotFoundAttributes();

            $tempAttributeLabel = $specific->getSource($this->getMagentoProduct())
                                           ->getLabel();
            $tempAttributeValues = $specific->getSource($this->getMagentoProduct())
                                            ->getValues();

            if (!$this->processNotFoundAttributes('Specifics')) {
                continue;
            }

            $values = array();
            foreach ($tempAttributeValues as $tempAttributeValue) {
                if ($tempAttributeValue == '--') {
                    continue;
                }
                $values[] = $tempAttributeValue;
            }

            $data[] = array(
                'name' => $tempAttributeLabel,
                'value' => $values
            );
        }

        return $data;
    }

    public function getMotorsData($type)
    {
        $attribute = $this->getMotorsAttribute($type);

        if (empty($attribute)) {
            return false;
        }

        $this->searchNotFoundAttributes();

        $rawData = $this->getRawMotorsData($type);

        if (!$this->processNotFoundAttributes('Compatibility')) {
            return false;
        }

        if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
            return $this->getPreparedMotorsEpidsData($rawData);
        }

        if ($this->getMotorsHelper()->isTypeBasedOnKtypes($type)) {
            return $this->getPreparedMotorsKtypesData($rawData);
        }

        return NULL;
    }

    //########################################

    private function getRawMotorsData($type)
    {
        $attributeValue = $this->getMagentoProduct()->getAttributeValue($this->getMotorsAttribute($type));

        if (empty($attributeValue)) {
            return array();
        }

        $motorsData = $this->getMotorsHelper()->parseAttributeValue($attributeValue);

        $motorsData = array_merge(
            $this->prepareRawMotorsItems($motorsData['items'], $type),
            $this->prepareRawMotorsFilters($motorsData['filters'], $type),
            $this->prepareRawMotorsGroups($motorsData['groups'], $type)
        );

        return $this->filterDuplicatedData($motorsData, $type);
    }

    private function filterDuplicatedData($motorsData, $type)
    {
        $uniqueItems = array();
        $uniqueFilters = array();
        $uniqueFiltersInfo = array();

        $itemType = $this->getMotorsHelper()->getIdentifierKey($type);

        foreach ($motorsData as $item) {

            if ($item['type'] === $itemType) {
                $uniqueItems[$item['id']] = $item;
                continue;
            }

            if (!in_array($item['info'], $uniqueFiltersInfo)) {
                $uniqueFilters[] = $item;
                $uniqueFiltersInfo[] = $item['info'];
            }
        }

        return array_merge(
            $uniqueItems,
            $uniqueFilters
        );
    }
    // ---------------------------------------

    private function prepareRawMotorsItems($data, $type)
    {
        if (empty($data)) {
            return array();
        }

        $typeIdentifier = $this->getMotorsHelper()->getIdentifierKey($type);
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getMotorsHelper()->getDictionaryTable($type))
            ->where(
                '`'.$typeIdentifier.'` IN (?)',
                array_keys($data)
            );

        if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
            $select->where('scope = ?', $this->getMotorsHelper()->getEpidsScopeByType($type));
        }

        $queryStmt = $select->query();

        $existedItems = array();
        while ($row = $queryStmt->fetch()) {
            $existedItems[$row[$typeIdentifier]] = $row;
        }

        foreach ($data as $typeId => $dataItem) {

            $data[$typeId]['type'] = $typeIdentifier;
            $data[$typeId]['info'] = isset($existedItems[$typeId]) ? $existedItems[$typeId] : array();
        }

        return $data;
    }

    private function prepareRawMotorsFilters($data, $type)
    {
        if (empty($data)) {
            return array();
        }

        $result = array();
        $typeIdentifier = $this->getMotorsHelper()->getIdentifierKey($type);

        foreach ($data as $filterId) {

            /** @var \Ess\M2ePro\Model\Ebay\Motor\Filter $filter */
            $filter = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Filter', $filterId, NULL, false);

            if (is_null($filter)) {
                $filter = $this->activeRecordFactory->getObject('Ebay\Motor\Filter');
            }

            if ($filter->getType() != $type) {
                continue;
            }

            $conditions = $filter->getConditions();

            $select = $this->resourceConnection->getConnection()
                ->select()
                ->from($this->getMotorsHelper()->getDictionaryTable($type));

            if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
                $select->where('scope = ?', $this->getMotorsHelper()->getEpidsScopeByType($type));
            }

            foreach ($conditions as $key => $value) {

                if ($key != 'year') {
                    $select->where('`'.$key.'` LIKE ?', '%'.$value.'%');
                    continue;
                }

                if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {

                    if (!empty($value['from'])) {
                        $select->where('`year` >= ?', $value['from']);
                    }

                    if (!empty($value['to'])) {
                        $select->where('`year` <= ?', $value['to']);
                    }

                } else {
                    $select->where('from_year <= ?', $value);
                    $select->where('to_year >= ?', $value);
                }
            }

            $filterData = $select->query()->fetchAll();

            if (empty($filterData)) {
                $result[] = array(
                    'id' => $filterId,
                    'type' => 'filter',
                    'note'  => $filter->getNote(),
                    'info'  => array()
                );
                continue;
            }

            if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {

                if ($type == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR) {
                    $filterData = $this->groupEbayMotorsEpidsData($filterData, $conditions);
                }

                foreach ($filterData as $group) {
                    $result[] = array(
                        'id'   => $filterId,
                        'type' => 'filter',
                        'note' => $filter->getNote(),
                        'info' => $group
                    );
                }
                continue;
            }

            foreach ($filterData as $item) {
                $result[] = array(
                    'id'   => $item[$typeIdentifier],
                    'type' => $typeIdentifier,
                    'note' => $filter->getNote(),
                    'info' => $item
                );
            }
        }

        return $result;
    }

    private function prepareRawMotorsGroups($data, $type)
    {
        if (empty($data)) {
            return array();
        }

        $result = array();

        foreach ($data as $groupId) {

            /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
            $group = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId, NULL, false);

            if (is_null($group)) {
                $group = $this->activeRecordFactory->getObject('Ebay\Motor\Group');
            }

            if ($group->getType() != $type) {
                continue;
            }

            if (!$group->getId()) {
                $result[] = array(
                    'id'   => $groupId,
                    'type' => 'group',
                    'note' => $group->getNote(),
                    'info' => array()
                );
                continue;
            }

            if ($group->isModeItem()) {
                $items = $this->prepareRawMotorsItems($group->getItems(), $type);
            } else {
                $items = $this->prepareRawMotorsFilters($group->getFiltersIds(), $type);
            }

            $result = array_merge($result, $items);
        }

        return $result;
    }

    //########################################

    private function getPreparedMotorsEpidsData($data)
    {
        $ebayAttributes = $this->getEbayMotorsEpidsAttributes();

        $preparedData = array();
        $emptySavedItems = array();

        foreach ($data as $item) {

            if (empty($item['info'])) {
                $emptySavedItems[$item['type']][] = $item;
                continue;
            }

            $motorsList = array();
            $motorsData = $this->buildEpidData($item['info']);

            foreach ($motorsData as $key => $value) {

                if ($value == '--') {
                    unset($motorsData[$key]);
                    continue;
                }

                $name = $key;

                foreach ($ebayAttributes as $ebayAttribute) {
                    if ($ebayAttribute['title'] == $key) {
                        $name = $ebayAttribute['ebay_id'];
                        break;
                    }
                }

                $motorsList[] = array(
                    'name'  => $name,
                    'value' => $value
                );
            }

            $preparedData[] = array(
                'epid' => isset($item['info']['epid']) ? $item['info']['epid'] : null,
                'list' => $motorsList,
                'note' => $item['note'],
            );
        }

        if (!empty($emptySavedItems['epid'])) {

            $tempItems = array();
            foreach ($emptySavedItems['epid'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some ePID(s) which were saved in Parts Compatibility Magento Attribute
                have been removed. Their Values were ignored and not sent on eBay',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['filter'])) {

            $tempItems = array();
            foreach ($emptySavedItems['filter'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some ePID(s) Grid Filter(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['group'])) {

            $tempItems = array();
            foreach ($emptySavedItems['group'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some ePID(s) Group(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        return $preparedData;
    }

    private function getPreparedMotorsKtypesData($data)
    {
        $preparedData = array();
        $emptySavedItems = array();

        foreach ($data as $item) {

            if (empty($item['info'])) {
                $emptySavedItems[$item['type']][] = $item;
                continue;
            }

            $preparedData[] = array(
                'ktype' => $item['id'],
                'note' => $item['note'],
            );
        }

        if (!empty($emptySavedItems['ktype'])) {

            $tempItems = array();
            foreach ($emptySavedItems['ktype'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some kTypes(s) which were saved in Parts Compatibility Magento Attribute
                have been removed. Their Values were ignored and not sent on eBay',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['filter'])) {

            $tempItems = array();
            foreach ($emptySavedItems['filter'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some kTypes(s) Grid Filter(s) was removed, that is why its Settings
                were ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['group'])) {

            $tempItems = array();
            foreach ($emptySavedItems['group'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__('
                Some kTypes(s) Group(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        return $preparedData;
    }

    // ---------------------------------------

    private function groupEbayMotorsEpidsData($data, $condition)
    {
        $groupingFields = array_unique(array_merge(
            array('year', 'make', 'model'),
            array_keys($condition)
        ));

        $groups = array();
        foreach ($data as $item) {
            if (empty($groups)) {

                $group = array();
                foreach ($groupingFields as $groupingField) {
                    $group[$groupingField] = $item[$groupingField];
                }

                ksort($group);

                $groups[] = $group;
                continue;
            }

            $newGroup = array();
            foreach ($groupingFields as $groupingField) {
                $newGroup[$groupingField] = $item[$groupingField];
            }

            ksort($newGroup);

            if (!in_array($newGroup, $groups)) {
                $groups[] = $newGroup;
            }
        }

        return $groups;
    }

    private function buildEpidData($resource)
    {
        $motorsData = array();

        if (isset($resource['make'])) {
            $motorsData['Make'] = $resource['make'];
        }

        if (isset($resource['model'])) {
            $motorsData['Model'] = $resource['model'];
        }

        if (isset($resource['year'])) {
            $motorsData['Year'] = $resource['year'];
        }

        if (isset($resource['submodel'])) {
            $motorsData['Submodel'] = $resource['submodel'];
        }

        if (isset($resource['trim'])) {
            $motorsData['Trim'] = $resource['trim'];
        }

        if (isset($resource['engine'])) {
            $motorsData['Engine'] = $resource['engine'];
        }

        return $motorsData;
    }

    private function getEbayMotorsEpidsAttributes()
    {
        $categoryId = $this->getCategorySource()->getMainCategory();
        $categoryData = $this->getEbayMarketplace()->getCategory($categoryId);

        $features = !empty($categoryData['features']) ?
                    (array)$this->getHelper('Data')->jsonDecode($categoryData['features']) : array();

        $attributes = !empty($features['parts_compatibility_attributes']) ?
                      $features['parts_compatibility_attributes'] : array();

        return $attributes;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private function getCategoryTemplate()
    {
        if (is_null($this->categoryTemplate)) {
            $this->categoryTemplate = $this->getListingProduct()
                                           ->getChildObject()
                                           ->getCategoryTemplate();
        }
        return $this->categoryTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private function getOtherCategoryTemplate()
    {
        if (is_null($this->otherCategoryTemplate)) {
            $this->otherCategoryTemplate = $this->getListingProduct()
                                                ->getChildObject()
                                                ->getOtherCategoryTemplate();
        }
        return $this->otherCategoryTemplate;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Helper\Component\Ebay\Motors
     */
    private function getMotorsHelper()
    {
        return $this->getHelper('Component\Ebay\Motors');
    }

    private function getMotorsAttribute($type)
    {
        return $this->getMotorsHelper()->getAttribute($type);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    private function getCategorySource()
    {
        return $this->getEbayListingProduct()->getCategoryTemplateSource();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Source
     */
    private function getOtherCategorySource()
    {
        return $this->getEbayListingProduct()->getOtherCategoryTemplateSource();
    }

    //########################################
}