<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay;

class Configuration
{
    public const UPLOAD_IMAGES_MODE_AUTO = 1;
    public const UPLOAD_IMAGES_MODE_SELF = 2;
    public const UPLOAD_IMAGES_MODE_EPS  = 3;

    public const PRODUCT_IDENTIFIER_MODE_NONE             = 0;
    public const PRODUCT_IDENTIFIER_MODE_DOES_NOT_APPLY   = 1;
    public const PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE = 2;

    private const CONFIG_GROUP = '/ebay/configuration/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(\Ess\M2ePro\Model\Config\Manager $config)
    {
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getFeedbackNotificationMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_mode'
        );
    }

    /**
     * @return bool
     */
    public function isEnableFeedbackNotificationMode(): bool
    {
        return $this->getFeedbackNotificationMode() == 1;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setFeedbackNotificationLastCheck($value)
    {
        $this->config->setGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_last_check',
            $value
        );

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getFeedbackNotificationLastCheck()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_last_check'
        );
    }

    /**
     * @return int
     */
    public function getPreventItemDuplicatesMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'prevent_item_duplicates_mode'
        );
    }

    /**
     * @return bool
     */
    public function isEnablePreventItemDuplicatesMode(): bool
    {
        return $this->getPreventItemDuplicatesMode() == 1;
    }

    /**
     * @return int
     */
    public function getUploadImagesMode(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'upload_images_mode'
        );
    }

    /**
     * @return bool
     */
    public function isAutoUploadImagesMode(): bool
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_AUTO;
    }

    /**
     * @return bool
     */
    public function isSelfUploadImagesMode(): bool
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_SELF;
    }

    /**
     * @return bool
     */
    public function isEpsUploadImagesMode(): bool
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_EPS;
    }

    /**
     * @return mixed|null
     */
    public function getUkEpidsAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'uk_epids_attribute'
        );
    }

    /**
     * @return mixed|null
     */
    public function getDeEpidsAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'de_epids_attribute'
        );
    }

    /**
     * @return mixed|null
     */
    public function getAuEpidsAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'au_epids_attribute'
        );
    }

    /**
     * @return mixed|null
     */
    public function getItEpidsAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'it_epids_attribute'
        );
    }

    /**
     * @return mixed|null
     */
    public function getMotorsEpidsAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'motors_epids_attribute'
        );
    }

    /**
     * @return mixed|null
     */
    public function getKTypesAttribute()
    {
        return $this->config->getGroupValue(
            self::CONFIG_GROUP,
            'ktypes_attribute'
        );
    }

    //----------------------------------------

    public function isUpcModeNone()
    {
        return $this->isProductIdModeNone('upc');
    }

    public function isUpcModeDoesNotApply()
    {
        return $this->isProductIdModeDoesNotApply('upc');
    }

    public function isUpcModeCustomAttribute()
    {
        return $this->isProductIdModeCustomAttribute('upc');
    }

    public function getUpcCustomAttribute()
    {
        return $this->getProductIdAttribute('upc');
    }

    //----------------------------------------

    public function isEanModeNone()
    {
        return $this->isProductIdModeNone('ean');
    }

    public function isEanModeDoesNotApply()
    {
        return $this->isProductIdModeDoesNotApply('ean');
    }

    public function isEanModeCustomAttribute()
    {
        return $this->isProductIdModeCustomAttribute('ean');
    }

    public function getEanCustomAttribute()
    {
        return $this->getProductIdAttribute('ean');
    }

    //----------------------------------------

    public function isIsbnModeNone()
    {
        return $this->isProductIdModeNone('isbn');
    }

    public function isIsbnModeDoesNotApply()
    {
        return $this->isProductIdModeDoesNotApply('isbn');
    }

    public function isIsbnModeCustomAttribute()
    {
        return $this->isProductIdModeCustomAttribute('isbn');
    }

    public function getIsbnCustomAttribute()
    {
        return $this->getProductIdAttribute('isbn');
    }

    //----------------------------------------

    public function isEpidModeNone()
    {
        return $this->isProductIdModeNone('epid');
    }

    public function isEpidModeDoesNotApply()
    {
        return $this->isProductIdModeDoesNotApply('epid');
    }

    public function isEpidModeCustomAttribute()
    {
        return $this->isProductIdModeCustomAttribute('epid');
    }

    public function getEpidCustomAttribute()
    {
        return $this->getProductIdAttribute('epid');
    }

    //----------------------------------------

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isProductIdModeNone($identifier)
    {
        return $this->getProductIdMode($identifier) == self::PRODUCT_IDENTIFIER_MODE_NONE;
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isProductIdModeDoesNotApply($identifier)
    {
        return $this->getProductIdMode($identifier) == self::PRODUCT_IDENTIFIER_MODE_DOES_NOT_APPLY;
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isProductIdModeCustomAttribute($identifier)
    {
        return $this->getProductIdMode($identifier) == self::PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE;
    }

    //----------------------------------------

    public function setProductIdMode($identifier, $mode)
    {
        $this->validateProductId($identifier);
        $this->config->setGroupValue(self::CONFIG_GROUP, $identifier . '_mode', $mode);
    }

    public function getProductIdMode($identifier)
    {
        $this->validateProductId($identifier);
        return (int)$this->config->getGroupValue(self::CONFIG_GROUP, $identifier . '_mode');
    }

    //----------------------------------------

    public function setProductIdAttribute($identifier, $attribute)
    {
        $this->validateProductId($identifier);
        $this->config->setGroupValue(self::CONFIG_GROUP, $identifier . '_custom_attribute', $attribute);
    }

    /**
     * @return string|null
     */
    public function getProductIdAttribute($identifier)
    {
        $this->validateProductId($identifier);
        if (!$this->isProductIdModeCustomAttribute($identifier)) {
            return null;
        }

        $attribute = $this->config->getGroupValue(self::CONFIG_GROUP, $identifier . '_custom_attribute');

        if (!$attribute || trim($attribute) === '') {
            return null;
        }

        return $attribute;
    }

    // ----------------------------------------

    private function validateProductId($identifier)
    {
        if (!in_array($identifier, ['isbn', 'epid', 'upc', 'ean'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Unknown product identifier '$identifier'");
        }
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getViewTemplateSellingFormatShowTaxCategory(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'view_template_selling_format_show_tax_category'
        );
    }

    /**
     * @return int
     */
    public function getVariationMpnCanBeChanged(): int
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'variation_mpn_can_be_changed'
        );
    }

    // ----------------------------------------

    /**
     * @param array $values
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setConfigValues(array $values): void
    {
        $this->setProductIdsConfigValues($values);

        if (isset($values['feedback_notification_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'feedback_notification_mode',
                $values['feedback_notification_mode']
            );
        }

        if (isset($values['feedback_notification_last_check'])) {
            $this->setFeedbackNotificationLastCheck($values['feedback_notification_last_check']);
        }

        if (isset($values['prevent_item_duplicates_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'prevent_item_duplicates_mode',
                $values['prevent_item_duplicates_mode']
            );
        }

        if (isset($values['upload_images_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'upload_images_mode',
                $values['upload_images_mode']
            );
        }

        //----------------------------------------

        $motorsAttributes = [];

        if (isset($values['uk_epids_attribute'])) {
            $motorsAttributes[] = $values['uk_epids_attribute'];
        }

        if (isset($values['de_epids_attribute'])) {
            $motorsAttributes[] = $values['de_epids_attribute'];
        }

        if (isset($values['au_epids_attribute'])) {
            $motorsAttributes[] = $values['au_epids_attribute'];
        }

        if (isset($values['it_epids_attribute'])) {
            $motorsAttributes[] = $values['it_epids_attribute'];
        }

        if (isset($values['motors_epids_attribute'])) {
            $motorsAttributes[] = $values['motors_epids_attribute'];
        }

        if (isset($values['ktypes_attribute'])) {
            $motorsAttributes[] = $values['ktypes_attribute'];
        }

        if (count(array_filter($motorsAttributes)) !== count(array_unique(array_filter($motorsAttributes)))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors Attributes can not be the same.');
        }

        if (isset($values['uk_epids_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'uk_epids_attribute',
                $values['uk_epids_attribute']
            );
        }

        if (isset($values['de_epids_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'de_epids_attribute',
                $values['de_epids_attribute']
            );
        }

        if (isset($values['au_epids_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'au_epids_attribute',
                $values['au_epids_attribute']
            );
        }

        if (isset($values['it_epids_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'it_epids_attribute',
                $values['it_epids_attribute']
            );
        }

        if (isset($values['motors_epids_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'motors_epids_attribute',
                $values['motors_epids_attribute']
            );
        }

        if (isset($values['ktypes_attribute'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'ktypes_attribute',
                $values['ktypes_attribute']
            );
        }
    }

    public function setProductIdsConfigValues(array $values): void
    {
        $identifiersKeys = ['isbn', 'epid', 'upc', 'ean'];

        foreach ($identifiersKeys as $idKey) {
            if (isset($values[$idKey . '_mode'])) {
                $this->setProductIdMode($idKey, $values[$idKey . '_mode']);
            }

            if (isset($values[$idKey . '_custom_attribute'])) {
                $this->setProductIdAttribute($idKey, $values[$idKey . '_custom_attribute']);
            }
        }
    }
}
