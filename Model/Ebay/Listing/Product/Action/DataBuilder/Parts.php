<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Categories
 */
class Parts extends AbstractModel
{
    protected $resourceConnection;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        $data = [];

        if ($this->getEbayListing()->isPartsCompatibilityModeEpids()) {
            $motorsType = $this->getHelper('Component_Ebay_Motors')->getEpidsTypeByMarketplace(
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

        return $data;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        $attributeValue = '';

        if ($this->getEbayListing()->isPartsCompatibilityModeEpids()) {
            $motorsType = $this->getHelper('Component_Ebay_Motors')->getEpidsTypeByMarketplace(
                $this->getMarketplace()->getId()
            );

            $attributeValue = $this->getMagentoProduct()->getAttributeValue($this->getMotorsAttribute($motorsType));
        } elseif ($this->getEbayListing()->isPartsCompatibilityModeKtypes()) {
            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;

            $attributeValue = $this->getMagentoProduct()->getAttributeValue($this->getMotorsAttribute($motorsType));
        }

        return $attributeValue ?
            $this->getHelper('Data')->hashString($attributeValue, 'md5') : null;
    }

    public function getMotorsData($type)
    {
        $attribute = $this->getMotorsAttribute($type);

        if (empty($attribute)) {
            return false;
        }

        $this->searchNotFoundAttributes();

        $rawData = $this->getRawMotorsData($type);

        $attributes = $this->getMagentoProduct()->getNotFoundAttributes();
        if (!empty($attributes)) {
            return [];
        }

        if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
            return $this->getPreparedMotorsEpidsData($rawData);
        }

        if ($this->getMotorsHelper()->isTypeBasedOnKtypes($type)) {
            return $this->getPreparedMotorsKtypesData($rawData);
        }

        return null;
    }

    //########################################

    protected function getRawMotorsData($type)
    {
        $attributeValue = $this->getMagentoProduct()->getAttributeValue($this->getMotorsAttribute($type));

        if (empty($attributeValue)) {
            return [];
        }

        $motorsData = $this->getMotorsHelper()->parseAttributeValue($attributeValue);

        $motorsData = array_merge(
            $this->prepareRawMotorsItems($motorsData['items'], $type),
            $this->prepareRawMotorsFilters($motorsData['filters'], $type),
            $this->prepareRawMotorsGroups($motorsData['groups'], $type)
        );

        return $this->filterDuplicatedData($motorsData, $type);
    }

    protected function filterDuplicatedData($motorsData, $type)
    {
        $uniqueItems = [];
        $uniqueFilters = [];
        $uniqueFiltersInfo = [];

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

    protected function prepareRawMotorsItems($data, $type)
    {
        if (empty($data)) {
            return [];
        }

        $typeIdentifier = $this->getMotorsHelper()->getIdentifierKey($type);
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getMotorsHelper()->getDictionaryTable($type))
            ->where(
                '`' . $typeIdentifier . '` IN (?)',
                array_keys($data)
            );

        if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
            $select->where('scope = ?', $this->getMotorsHelper()->getEpidsScopeByType($type));
        }

        $queryStmt = $select->query();

        $existedItems = [];
        while ($row = $queryStmt->fetch()) {
            $existedItems[$row[$typeIdentifier]] = $row;
        }

        foreach ($data as $typeId => $dataItem) {
            $data[$typeId]['type'] = $typeIdentifier;
            $data[$typeId]['info'] = isset($existedItems[$typeId]) ? $existedItems[$typeId] : [];
        }

        return $data;
    }

    protected function prepareRawMotorsFilters($filterIds, $type)
    {
        if (empty($filterIds)) {
            return [];
        }

        $result = [];
        $typeIdentifier = $this->getMotorsHelper()->getIdentifierKey($type);

        $motorFilterCollection = $this->activeRecordFactory->getObject('Ebay_Motor_Filter')->getCollection();
        $motorFilterCollection->addFieldToFilter('id', ['in' => $filterIds]);

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Filter $filter */
        foreach ($motorFilterCollection->getItems() as $filter) {
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
                    $select->where('`' . $key . '` LIKE ?', '%' . $value . '%');
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
                $result[] = [
                    'id'   => $filter->getId(),
                    'type' => 'filter',
                    'note' => $filter->getNote(),
                    'info' => []
                ];
                continue;
            }

            if ($this->getMotorsHelper()->isTypeBasedOnEpids($type)) {
                if ($type == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR) {
                    $filterData = $this->groupEbayMotorsEpidsData($filterData, $conditions);
                }

                foreach ($filterData as $group) {
                    $result[] = [
                        'id'   => $filter->getId(),
                        'type' => 'filter',
                        'note' => $filter->getNote(),
                        'info' => $group
                    ];
                }

                continue;
            }

            foreach ($filterData as $item) {
                if (isset($item[$typeIdentifier])) {
                    $result[] = [
                        'id' => $item[$typeIdentifier],
                        'type' => $typeIdentifier,
                        'note' => $filter->getNote(),
                        'info' => $item
                    ];
                }
            }
        }

        return $result;
    }

    protected function prepareRawMotorsGroups($groupIds, $type)
    {
        if (empty($groupIds)) {
            return [];
        }

        $result = [];

        $motorGroupCollection = $this->activeRecordFactory->getObject('Ebay_Motor_Group')->getCollection();
        $motorGroupCollection->addFieldToFilter('id', ['in' => $groupIds]);

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
        foreach ($motorGroupCollection->getItems() as $group) {
            if ($group->getType() != $type) {
                continue;
            }

            if ($group->isModeItem()) {
                $items = $this->prepareRawMotorsItems($group->getItems(), $type);
            } else {
                $items = $this->prepareRawMotorsFilters($group->getFiltersIds(), $type);
            }

            // @codingStandardsIgnoreLine
            $result = array_merge($result, $items);
        }

        return $result;
    }

    //########################################

    protected function getPreparedMotorsEpidsData($data)
    {
        $ebayAttributes = $this->getEbayMotorsEpidsAttributes();

        $preparedData = [];
        $emptySavedItems = [];

        foreach ($data as $item) {
            if (empty($item['info'])) {
                $emptySavedItems[$item['type']][] = $item;
                continue;
            }

            $motorsList = [];
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

                $motorsList[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }

            $preparedData[] = [
                'epid' => isset($item['info']['epid']) ? $item['info']['epid'] : null,
                'list' => $motorsList,
                'note' => $item['note'],
            ];
        }

        if (!empty($emptySavedItems['epid'])) {
            $tempItems = [];
            foreach ($emptySavedItems['epid'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some ePID(s) which were saved in Parts Compatibility Magento Attribute
                have been removed. Their Values were ignored and not sent on eBay',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['filter'])) {
            $tempItems = [];
            foreach ($emptySavedItems['filter'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some ePID(s) Grid Filter(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['group'])) {
            $tempItems = [];
            foreach ($emptySavedItems['group'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some ePID(s) Group(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        return $preparedData;
    }

    protected function getPreparedMotorsKtypesData($data)
    {
        $preparedData = [];
        $emptySavedItems = [];

        foreach ($data as $item) {
            if (empty($item['info'])) {
                $emptySavedItems[$item['type']][] = $item;
                continue;
            }

            $preparedData[] = [
                'ktype' => $item['id'],
                'note' => $item['note'],
            ];
        }

        if (!empty($emptySavedItems['ktype'])) {
            $tempItems = [];
            foreach ($emptySavedItems['ktype'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some kTypes(s) which were saved in Parts Compatibility Magento Attribute
                have been removed. Their Values were ignored and not sent on eBay',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['filter'])) {
            $tempItems = [];
            foreach ($emptySavedItems['filter'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some kTypes(s) Grid Filter(s) was removed, that is why its Settings
                were ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        if (!empty($emptySavedItems['group'])) {
            $tempItems = [];
            foreach ($emptySavedItems['group'] as $tempItem) {
                $tempItems[] = $tempItem['id'];
            }

            $msg = $this->getHelper('Module\Translation')->__(
                'Some kTypes(s) Group(s) was removed, that is why its Settings were
                ignored and can not be applied',
                implode(', ', $tempItems)
            );
            $this->addWarningMessage($msg);
        }

        return $preparedData;
    }

    // ---------------------------------------

    protected function groupEbayMotorsEpidsData($data, $condition)
    {
        $groupingFields = array_unique(
            array_merge(
                ['year', 'make', 'model'],
                array_keys($condition)
            )
        );

        $groups = [];
        foreach ($data as $item) {
            if (empty($groups)) {
                $group = [];
                foreach ($groupingFields as $groupingField) {
                    $group[$groupingField] = $item[$groupingField];
                }

                ksort($group);

                $groups[] = $group;
                continue;
            }

            $newGroup = [];
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

    protected function buildEpidData($resource)
    {
        $motorsData = [];

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

        if (isset($resource['street_name'])) {
            $motorsData['StreetName'] = $resource['street_name'];
        }

        return $motorsData;
    }

    protected function getEbayMotorsEpidsAttributes()
    {
        $categoryId = $this->getCategorySource()->getCategoryId();
        $categoryData = $this->getEbayMarketplace()->getCategory($categoryId);

        $features = !empty($categoryData['features']) ?
            (array)$this->getHelper('Data')->jsonDecode($categoryData['features']) : [];

        $attributes = !empty($features['parts_compatibility_attributes']) ?
            $features['parts_compatibility_attributes'] : [];

        return $attributes;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Helper\Component\Ebay\Motors
     */
    protected function getMotorsHelper()
    {
        return $this->getHelper('Component_Ebay_Motors');
    }

    protected function getMotorsAttribute($type)
    {
        return $this->getMotorsHelper()->getAttribute($type);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    protected function getCategorySource()
    {
        return $this->getEbayListingProduct()->getCategoryTemplateSource();
    }
}
