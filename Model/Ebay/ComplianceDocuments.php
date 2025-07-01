<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments as ComplianceDocumentsResource;

class ComplianceDocuments extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_UPLOADING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED = 3;

    private const TYPE_CERTIFICATE_OF_ANALYSIS = 'CERTIFICATE_OF_ANALYSIS';
    private const TYPE_CERTIFICATE_OF_CONFORMITY = 'CERTIFICATE_OF_CONFORMITY';
    private const TYPE_DECLARATION_OF_CONFORMITY = 'DECLARATION_OF_CONFORMITY';
    private const TYPE_INSTRUCTIONS_FOR_USE = 'INSTRUCTIONS_FOR_USE';
    private const TYPE_OTHER_SAFETY_DOCUMENTS = 'OTHER_SAFETY_DOCUMENTS';
    private const TYPE_SAFETY_DATA_SHEET = 'SAFETY_DATA_SHEET';
    private const TYPE_TROUBLE_SHOOTING_GUIDE = 'TROUBLE_SHOOTING_GUIDE';
    private const TYPE_USER_GUIDE_OR_MANUAL = 'USER_GUIDE_OR_MANUAL';
    private const TYPE_INSTALLATION_INSTRUCTIONS = 'INSTALLATION_INSTRUCTIONS';

    private const LANGUAGE_ENGLISH = 'ENGLISH';
    private const LANGUAGE_SPANISH = 'SPANISH';
    private const LANGUAGE_ITALIAN = 'ITALIAN';
    private const LANGUAGE_GERMAN = 'GERMAN';
    private const LANGUAGE_POLISH = 'POLISH';
    private const LANGUAGE_DUTCH = 'DUTCH';
    private const LANGUAGE_PORTUGESE = 'PORTUGESE';
    private const LANGUAGE_FRENCH = 'FRENCH';
    private const LANGUAGE_OTHER = 'OTHER';

    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->dataHelper = $dataHelper;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(ComplianceDocumentsResource::class);
    }

    public static function getDocumentTypeNames(): array
    {
        return [
            self::TYPE_CERTIFICATE_OF_ANALYSIS => __('Certificate of Analysis'),
            self::TYPE_CERTIFICATE_OF_CONFORMITY => __('Certificate of Conformity'),
            self::TYPE_DECLARATION_OF_CONFORMITY => __('Declaration of Conformity'),
            self::TYPE_INSTRUCTIONS_FOR_USE => __('Instructions for Use'),
            self::TYPE_USER_GUIDE_OR_MANUAL => __('User guide or manual'),
            self::TYPE_SAFETY_DATA_SHEET => __('Safety data sheet'),
            self::TYPE_INSTALLATION_INSTRUCTIONS => __('Installation instructions'),
            self::TYPE_TROUBLE_SHOOTING_GUIDE => __('Trouble shooting guide'),
            self::TYPE_OTHER_SAFETY_DOCUMENTS => __('Other safety documents'),
        ];
    }

    public static function getDocumentLanguages(): array
    {
        return [
            self::LANGUAGE_ENGLISH => __('English'),
            self::LANGUAGE_SPANISH => __('Spanish'),
            self::LANGUAGE_ITALIAN => __('Italian'),
            self::LANGUAGE_GERMAN => __('German'),
            self::LANGUAGE_POLISH => __('Polish'),
            self::LANGUAGE_DUTCH => __('Dutch'),
            self::LANGUAGE_PORTUGESE => __('Portuguese'),
            self::LANGUAGE_FRENCH => __('French'),
            self::LANGUAGE_OTHER => __('Other'),
        ];
    }

    public function init(int $accountId, string $type, string $url, array $languages): self
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_ACCOUNT_ID, $accountId);
        $this->setData(ComplianceDocumentsResource::COLUMN_HASH, $this->makeHash($type, $url));
        $this->setData(ComplianceDocumentsResource::COLUMN_TYPE, $type);
        $this->setData(ComplianceDocumentsResource::COLUMN_URL, $url);
        $this->setData(ComplianceDocumentsResource::COLUMN_STATUS, self::STATUS_PENDING);
        $this->setLanguages($languages);

        return $this;
    }

    public function getHash(): string
    {
        return (string)$this->getData(ComplianceDocumentsResource::COLUMN_HASH);
    }

    public function getLanguages(): array
    {
        $languages = $this->getData(ComplianceDocumentsResource::COLUMN_LANGUAGES);
        if (empty($languages)) {
            return [];
        }

        return json_decode($languages, true);
    }

    public function getType(): string
    {
        return (string)$this->getData(ComplianceDocumentsResource::COLUMN_TYPE);
    }

    public function getUrl(): string
    {
        return (string)$this->getData(ComplianceDocumentsResource::COLUMN_URL);
    }

    public function getEbayDocumentId(): string
    {
        return (string)$this->getData(ComplianceDocumentsResource::COLUMN_DOCUMENT_ID);
    }

    public function isStatusPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    public function isStatusUploading(): bool
    {
        return $this->getStatus() === self::STATUS_UPLOADING;
    }

    public function isStatusFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAILED;
    }

    public function isUploadedToEbay(): bool
    {
        return !empty($this->getEbayDocumentId());
    }

    public function setStatusPending(): void
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_STATUS, self::STATUS_PENDING);
    }

    public function setStatusUploading(): void
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_STATUS, self::STATUS_UPLOADING);
    }

    public function setStatusSuccess(string $ebayDocumentId): void
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_DOCUMENT_ID, $ebayDocumentId);
        $this->setData(ComplianceDocumentsResource::COLUMN_STATUS, self::STATUS_SUCCESS);
    }

    public function setStatusFailed(string $errorMessage): void
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_ERROR, $errorMessage);
        $this->setData(ComplianceDocumentsResource::COLUMN_STATUS, self::STATUS_FAILED);
    }

    private function makeHash(string $type, string $url): string
    {
        return $this->dataHelper->md5String($type . $url);
    }

    private function getStatus(): int
    {
        return (int)$this->getData(ComplianceDocumentsResource::COLUMN_STATUS);
    }

    private function setLanguages(array $languages)
    {
        $this->setData(ComplianceDocumentsResource::COLUMN_LANGUAGES, json_encode($languages));
    }
}
