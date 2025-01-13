<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr;

class Provider
{
    public const ATTR_MANUFACTURER_COMPANY_NAME = 'Manufacturer Company Name';
    public const ATTR_MANUFACTURER_STREET_1 = 'Manufacturer Street 1';
    public const ATTR_MANUFACTURER_STREET_2 = 'Manufacturer Street 2';
    public const ATTR_MANUFACTURER_CITY_NAME = 'Manufacturer City Name';
    public const ATTR_MANUFACTURER_STATE_OR_PROVINCE = 'Manufacturer State or Province';
    public const ATTR_MANUFACTURER_POSTAL_CODE = 'Manufacturer Postal Code';
    public const ATTR_MANUFACTURER_COUNTRY_CODE = 'Manufacturer Country Code';
    public const ATTR_MANUFACTURER_EMAIL = 'Manufacturer Email';
    public const ATTR_MANUFACTURER_PHONE = 'Manufacturer Phone';

    public const ATTR_RESPONSIBLE_PERSON_COMPANY_NAME = 'Responsible Person Company Name';
    public const ATTR_RESPONSIBLE_PERSON_STREET_1 = 'Responsible Person Street 1';
    public const ATTR_RESPONSIBLE_PERSON_STREET_2 = 'Responsible Person Street 2';
    public const ATTR_RESPONSIBLE_PERSON_CITY_NAME = 'Responsible Person City Name';
    public const ATTR_RESPONSIBLE_PERSON_STATE_OR_PROVINCE = 'Responsible Person State or Province';
    public const ATTR_RESPONSIBLE_PERSON_COUNTRY_CODE = 'Responsible Person Country Code';
    public const ATTR_RESPONSIBLE_PERSON_POSTAL_CODE = 'Responsible Person Postal Code';
    public const ATTR_RESPONSIBLE_PERSON_EMAIL = 'Responsible Person Email';
    public const ATTR_RESPONSIBLE_PERSON_PHONE = 'Responsible Person Phone';
    public const ATTR_RESPONSIBLE_PERSON_CODE_TYPES = 'Responsible Person Code Types';

    public const ATTR_PRODUCT_SAFETY_STATEMENTS = 'Product Safety Statements';
    public const ATTR_PRODUCT_SAFETY_PICTOGRAMS = 'Product Safety Pictograms';

    private const ATTRIBUTES = [
        [
            'title' => self::ATTR_MANUFACTURER_COMPANY_NAME,
            'code' => self::ATTR_MANUFACTURER_COMPANY_NAME
        ],
        [
            'title' => self::ATTR_MANUFACTURER_STREET_1,
            'code' => self::ATTR_MANUFACTURER_STREET_1
        ],
        [
            'title' => self::ATTR_MANUFACTURER_STREET_2,
            'code' => self::ATTR_MANUFACTURER_STREET_2
        ],
        [
            'title' => self::ATTR_MANUFACTURER_CITY_NAME,
            'code' => self::ATTR_MANUFACTURER_CITY_NAME]
        ,
        [
            'title' => self::ATTR_MANUFACTURER_STATE_OR_PROVINCE,
            'code' => self::ATTR_MANUFACTURER_STATE_OR_PROVINCE
        ],
        [
            'title' => self::ATTR_MANUFACTURER_POSTAL_CODE,
            'code' => self::ATTR_MANUFACTURER_POSTAL_CODE
        ],
        [
            'title' => self::ATTR_MANUFACTURER_COUNTRY_CODE,
            'code' => self::ATTR_MANUFACTURER_COUNTRY_CODE
        ],
        [
            'title' => self::ATTR_MANUFACTURER_EMAIL,
            'code' => self::ATTR_MANUFACTURER_EMAIL
        ],
        [
            'title' => self::ATTR_MANUFACTURER_PHONE,
            'code' => self::ATTR_MANUFACTURER_PHONE
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_COMPANY_NAME,
            'code' => self::ATTR_RESPONSIBLE_PERSON_COMPANY_NAME
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_STREET_1,
            'code' => self::ATTR_RESPONSIBLE_PERSON_STREET_1
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_STREET_2,
            'code' => self::ATTR_RESPONSIBLE_PERSON_STREET_2
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_CITY_NAME,
            'code' => self::ATTR_RESPONSIBLE_PERSON_CITY_NAME
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_STATE_OR_PROVINCE,
            'code' => self::ATTR_RESPONSIBLE_PERSON_STATE_OR_PROVINCE
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_COUNTRY_CODE,
            'code' => self::ATTR_RESPONSIBLE_PERSON_COUNTRY_CODE
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_POSTAL_CODE,
            'code' => self::ATTR_RESPONSIBLE_PERSON_POSTAL_CODE
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_EMAIL,
            'code' => self::ATTR_RESPONSIBLE_PERSON_EMAIL
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_PHONE,
            'code' => self::ATTR_RESPONSIBLE_PERSON_PHONE
        ],
        [
            'title' => self::ATTR_RESPONSIBLE_PERSON_CODE_TYPES,
            'code' => self::ATTR_RESPONSIBLE_PERSON_CODE_TYPES
        ],
        [
            'title' => self::ATTR_PRODUCT_SAFETY_STATEMENTS,
            'code' => self::ATTR_PRODUCT_SAFETY_STATEMENTS
        ],
        [
            'title' => self::ATTR_PRODUCT_SAFETY_PICTOGRAMS,
            'code' => self::ATTR_PRODUCT_SAFETY_PICTOGRAMS
        ],
    ];

    private \Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository;

    public function __construct(\Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository)
    {
        $this->attributeMappingRepository = $attributeMappingRepository;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    public function getAll(): array
    {
        return $this->retrieve(false);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    public function getConfigured(): array
    {
        return $this->retrieve(true);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    private function retrieve(bool $onlyConfigured): array
    {
        $existedByCode = $this->getExistedMappingGroupedByCode();

        $result = [];
        foreach (self::ATTRIBUTES as ['title' => $channelTitle, 'code' => $channelCode]) {
            $mappingId = null;
            $mode = 0;
            $value = null;
            if (isset($existedByCode[$channelCode])) {
                $mappingId = $existedByCode[$channelCode]->getId();
                $mode = $existedByCode[$channelCode]->getValueMode();
                $value = $existedByCode[$channelCode]->getValue();
            }

            if (
                $mappingId === null
                && $onlyConfigured
            ) {
                continue;
            }

            $result[] = new \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair(
                $mappingId,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE,
                $mode,
                $channelTitle,
                $channelCode,
                $value
            );
        }

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\AttributeMapping\Pair[]
     */
    private function getExistedMappingGroupedByCode(): array
    {
        $result = [];

        $existed = $this->attributeMappingRepository->findByComponentAndType(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE
        );
        foreach ($existed as $pair) {
            $result[$pair->getChannelAttributeCode()] = $pair;
        }

        return $result;
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public static function getAllAttributesCodes(): array
    {
        return array_column(self::ATTRIBUTES, 'code');
    }

    public static function getAttributeTitle(string $code): ?string
    {
        foreach (self::ATTRIBUTES as ['title' => $channelTitle, 'code' => $channelCode]) {
            if ($code !== $channelCode) {
                continue;
            }

            return $channelTitle;
        }

        return null;
    }
}
