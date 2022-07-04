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
}
