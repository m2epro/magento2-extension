<?php

namespace Ess\M2ePro\Model\Ebay\Template\Description;

use Ess\M2ePro\Model\Ebay\Template\Description as Description;
use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description as Resource;

class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
    private \Magento\Framework\Filesystem\DriverPool $driverPool;
    private \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($activeRecordFactory, $ebayFactory, $helperFactory, $modelFactory);

        $this->driverPool = $driverPool;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->dataHelper = $dataHelper;
    }

    protected function prepareData()
    {
        $data = parent::prepareData();

        $defaultData = $this->getDefaultData();

        $data = $this->dataHelper->arrayReplaceRecursive($defaultData, $data);

        if (isset($this->rawData['title_mode'])) {
            $data['title_mode'] = (int)$this->rawData['title_mode'];
        }

        if (isset($this->rawData['title_template'])) {
            $data['title_template'] = $this->rawData['title_template'];
        }

        if (isset($this->rawData['subtitle_mode'])) {
            $data['subtitle_mode'] = (int)$this->rawData['subtitle_mode'];
        }

        if (isset($this->rawData['subtitle_template'])) {
            $data['subtitle_template'] = $this->rawData['subtitle_template'];
        }

        if (isset($this->rawData['description_mode'])) {
            $data['description_mode'] = (int)$this->rawData['description_mode'];
        }

        if (isset($this->rawData['description_template'])) {
            $data['description_template'] = $this->rawData['description_template'];
        }

        // region Condition
        if (isset($this->rawData['condition_mode'])) {
            $data['condition_mode'] = (int)$this->rawData['condition_mode'];
        }

        if (isset($this->rawData['condition_value'])) {
            $data[Resource::COLUMN_CONDITION_VALUE] = (int)$this->rawData['condition_value'];
        }

        if (isset($this->rawData['condition_attribute'])) {
            $data['condition_attribute'] = $this->rawData['condition_attribute'];
        }

        if ((int)$this->rawData[Resource::COLUMN_CONDITION_MODE] === Description::CONDITION_MODE_EBAY) {
            if ((int)$data[Resource::COLUMN_CONDITION_VALUE] === Description::CONDITION_EBAY_GRADED) {
                $data = $this->prepareDescriptorsDataForGradedCondition($data, $this->rawData);
            } elseif ((int)$data[Resource::COLUMN_CONDITION_VALUE] === Description::CONDITION_EBAY_UNGRADED) {
                $data = $this->prepareDescriptorsDataForUngradedCondition($data, $this->rawData);
            }
        }

        if (isset($this->rawData['condition_note_mode'])) {
            $data['condition_note_mode'] = (int)$this->rawData['condition_note_mode'];
        }

        if (isset($this->rawData['condition_note_template'])) {
            $data['condition_note_template'] = $this->rawData['condition_note_template'];
        }
        // endregion

        if (isset($this->rawData['product_details'])) {
            $data['product_details'] = $this->rawData['product_details'];

            if (is_array($data['product_details'])) {
                $data['product_details'] = \Ess\M2ePro\Helper\Json::encode(
                    $data['product_details']
                );
            }
        }

        if (isset($this->rawData['editor_type'])) {
            $data['editor_type'] = (int)$this->rawData['editor_type'];
        }

        if (isset($this->rawData['cut_long_titles'])) {
            $data['cut_long_titles'] = (int)$this->rawData['cut_long_titles'];
        }

        if (isset($this->rawData['enhancement'])) {
            $data['enhancement'] = $this->rawData['enhancement'];

            if (is_array($data['enhancement'])) {
                $data['enhancement'] = implode(',', $this->rawData['enhancement']);
            }
        }

        if (isset($this->rawData['compliance_documents'])) {
            $data['compliance_documents'] = $this->prepareComplianceDocuments(
                $this->rawData['compliance_documents']
            );
        }

        if (isset($this->rawData['gallery_type'])) {
            $data['gallery_type'] = (int)$this->rawData['gallery_type'];
        }

        if (isset($this->rawData['image_main_mode'])) {
            $data['image_main_mode'] = (int)$this->rawData['image_main_mode'];
        }

        if (isset($this->rawData['image_main_attribute'])) {
            $data['image_main_attribute'] = $this->rawData['image_main_attribute'];
        }

        if (isset($this->rawData['gallery_images_mode'])) {
            $data['gallery_images_mode'] = (int)$this->rawData['gallery_images_mode'];
        }

        if (isset($this->rawData['gallery_images_limit'])) {
            $data['gallery_images_limit'] = (int)$this->rawData['gallery_images_limit'];
        }

        if (isset($this->rawData['gallery_images_attribute'])) {
            $data['gallery_images_attribute'] = $this->rawData['gallery_images_attribute'];
        }

        if (isset($this->rawData['variation_images_mode'])) {
            $data['variation_images_mode'] = (int)$this->rawData['variation_images_mode'];
        }

        if (isset($this->rawData['variation_images_limit'])) {
            $data['variation_images_limit'] = (int)$this->rawData['variation_images_limit'];
        }

        if (isset($this->rawData['variation_images_attribute'])) {
            $data['variation_images_attribute'] = $this->rawData['variation_images_attribute'];
        }

        if (isset($this->rawData['reserve_price_custom_attribute'])) {
            $data['reserve_price_custom_attribute'] = $this->rawData['reserve_price_custom_attribute'];
        }

        if (isset($this->rawData['default_image_url'])) {
            $data['default_image_url'] = $this->rawData['default_image_url'];
        }

        if (isset($this->rawData['variation_configurable_images'])) {
            $data['variation_configurable_images'] = $this->rawData['variation_configurable_images'];

            if (is_array($data['variation_configurable_images'])) {
                $data['variation_configurable_images'] = \Ess\M2ePro\Helper\Json::encode(
                    $data['variation_configurable_images']
                );
            }
        }

        if (isset($this->rawData['use_supersize_images'])) {
            $data['use_supersize_images'] = (int)$this->rawData['use_supersize_images'];
        }

        // ---------------------------------------

        if (isset($this->rawData['video_mode'])) {
            $data['video_mode'] = (int)$this->rawData['video_mode'];
        }

        if (isset($this->rawData['video_attribute'])) {
            if ((int)$data['video_mode'] === Description::VIDEO_MODE_ATTRIBUTE) {
                $data['video_attribute'] = $this->rawData['video_attribute'];
            } else {
                $data['video_attribute'] = '';
            }
        }

        if (isset($this->rawData['video_custom_value'])) {
            if ((int)$data['video_mode'] === Description::VIDEO_MODE_CUSTOM_VALUE) {
                $data['video_custom_value'] = $this->rawData['video_custom_value'];
            } else {
                $data['video_custom_value'] = '';
            }
        }

        // ---------------------------------------

        if (isset($this->rawData['watermark_mode'])) {
            $data['watermark_mode'] = (int)$this->rawData['watermark_mode'];
        }

        // ---------------------------------------

        $watermarkSettings = [];
        $hashChange = false;

        if (isset($this->rawData['watermark_settings']['position'])) {
            $watermarkSettings['position'] = (int)$this->rawData['watermark_settings']['position'];

            if (
                isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['position'] !==
                $this->rawData['old_watermark_settings']['position']
            ) {
                $hashChange = true;
            }
        }

        if (isset($this->rawData['watermark_settings']['transparent'])) {
            $watermarkSettings['transparent'] = (int)$this->rawData['watermark_settings']['transparent'];

            if (
                isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['transparent'] !==
                $this->rawData['old_watermark_settings']['transparent']
            ) {
                $hashChange = true;
            }
        }

        if (isset($this->rawData['watermark_settings']['opacity_level'])) {
            $watermarkSettings['opacity_level'] = (int)$this->rawData['watermark_settings']['opacity_level'];

            if (
                isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['opacity_level'] !==
                $this->rawData['old_watermark_settings']['opacity_level']
            ) {
                $hashChange = true;
            }
        }

        // ---------------------------------------

        $watermarkImageFile = $this->phpEnvironmentRequest->getFiles('watermark_image');

        if (!empty($watermarkImageFile['tmp_name'])) {
            $hashChange = true;

            // @codingStandardsIgnoreLine
            $data['watermark_image'] = base64_encode(file_get_contents($watermarkImageFile['tmp_name']));

            if (isset($data['id'])) {
                /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
                $varDir = $this->modelFactory->getObject('VariablesDir', [
                    'data' => [
                        'child_folder' => 'ebay/template/description/watermarks',
                    ],
                ]);

                $watermarkPath = $varDir->getPath() . (int)$data['id'] . '.png';

                $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
                if ($fileDriver->isFile($watermarkPath)) {
                    $fileDriver->deleteFile($watermarkPath);
                }
            }
        } elseif (!empty($this->rawData['old_watermark_image']) && isset($data['id'])) {
            // @codingStandardsIgnoreLine
            $data['watermark_image'] = $this->rawData['old_watermark_image'];
        }

        // ---------------------------------------

        if ($hashChange) {
            $watermarkSettings['hashes']['previous'] = $this->rawData['old_watermark_settings']['hashes']['current'];
            $watermarkSettings['hashes']['current'] = substr(sha1(microtime()), 0, 5);
        } else {
            $watermarkSettings['hashes']['previous'] = $this->rawData['old_watermark_settings']['hashes']['previous'];
            $watermarkSettings['hashes']['current'] = $this->rawData['old_watermark_settings']['hashes']['current'];
        }

        $data['watermark_settings'] = \Ess\M2ePro\Helper\Json::encode($watermarkSettings);

        // ---------------------------------------

        return $data;
    }

    // region Prepare Descriptors Data For Graded Condition

    private function prepareDescriptorsDataForGradedCondition(array $result, array $input): array
    {
        return array_merge(
            $result,
            $this->prepareProfessionalGraderData($input),
            $this->prepareGradeIdData($input),
            $this->prepareCertificationNumberData($input)
        );
    }

    private function prepareProfessionalGraderData(array $input): array
    {
        $modeFieldsMap = [
            Description::CONDITION_DESCRIPTOR_MODE_NONE => [
                'attr' => null,
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE => [
                'attr' => $input[Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE],
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_EBAY => [
                'attr' => null,
                'val' => (int)$input[Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE],
            ],
        ];

        $profGraderMode = (int)$input[Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE];
        $profGrader = $modeFieldsMap[$profGraderMode];

        return [
            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE => $profGraderMode,
            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE => $profGrader['attr'],
            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE => $profGrader['val'],
        ];
    }

    private function prepareGradeIdData(array $input): array
    {
        $modeFieldsMap = [
            Description::CONDITION_DESCRIPTOR_MODE_NONE => [
                'attr' => null,
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE => [
                'attr' => $input[Resource::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE],
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_EBAY => [
                'attr' => null,
                'val' => (int)$input[Resource::COLUMN_CONDITION_GRADE_ID_VALUE],
            ],
        ];

        $gradeMode = (int)$input[Resource::COLUMN_CONDITION_GRADE_ID_MODE];
        $grades = $modeFieldsMap[$gradeMode];

        return [
            Resource::COLUMN_CONDITION_GRADE_ID_MODE => $gradeMode,
            Resource::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE => $grades['attr'],
            Resource::COLUMN_CONDITION_GRADE_ID_VALUE => $grades['val'],
        ];
    }

    private function prepareCertificationNumberData(array $input): array
    {
        $modeFieldsMap = [
            Description::CONDITION_DESCRIPTOR_MODE_NONE => [
                'attr' => null,
                'cst_val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE => [
                'attr' => $input[Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE],
                'cst_val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_CUSTOM => [
                'attr' => null,
                'cst_val' => $input[Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE],
            ],
        ];

        $certNumMode = (int)$input[Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE];
        $certNum = $modeFieldsMap[$certNumMode];

        return [
            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE => $certNumMode,
            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE => $certNum['attr'],
            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE => $certNum['cst_val'],
        ];
    }

    // endregion
    // region Prepare Descriptors Data For Ungraded Condition

    private function prepareDescriptorsDataForUngradedCondition(array $result, array $input): array
    {
        return array_merge(
            $result,
            $this->prepareCardConditionData($input)
        );
    }

    private function prepareCardConditionData(array $input): array
    {
        $modeFieldsMap = [
            Description::CONDITION_DESCRIPTOR_MODE_NONE => [
                'attr' => null,
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE => [
                'attr' => $input[Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE],
                'val' => null,
            ],
            Description::CONDITION_DESCRIPTOR_MODE_EBAY => [
                'attr' => null,
                'val' => (int)$input[Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE],
            ],
        ];

        $cardCondMode = (int)$input[Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE];
        $cardCond = $modeFieldsMap[$cardCondMode];

        return [
            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE => $cardCondMode,
            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE => $cardCond['attr'],
            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE => $cardCond['val'],
        ];
    }

    // endregion

    public function getDefaultData()
    {
        return [
            'title_mode' => Description::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'subtitle_mode' => Description::SUBTITLE_MODE_NONE,
            'subtitle_template' => '',

            'description_mode' => '',
            'description_template' => '',

            Resource::COLUMN_CONDITION_MODE => Description::CONDITION_MODE_EBAY,
            Resource::COLUMN_CONDITION_VALUE => Description::CONDITION_EBAY_NEW,
            Resource::COLUMN_CONDITION_ATTRIBUTE => '',

            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE => Description::CONDITION_DESCRIPTOR_MODE_NONE,
            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE => null,
            Resource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE => null,

            Resource::COLUMN_CONDITION_GRADE_ID_MODE => Description::CONDITION_DESCRIPTOR_MODE_NONE,
            Resource::COLUMN_CONDITION_GRADE_ID_VALUE => null,
            Resource::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE => null,

            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE => Description::CONDITION_DESCRIPTOR_MODE_NONE,
            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE => '',
            Resource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE => null,

            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE => Description::CONDITION_DESCRIPTOR_MODE_NONE,
            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE => null,
            Resource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE => null,

            'condition_note_mode' => Description::CONDITION_NOTE_MODE_NONE,
            'condition_note_template' => '',

            'product_details' => \Ess\M2ePro\Helper\Json::encode(
                [
                    'brand' => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'mpn' => ['mode' => Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY, 'attribute' => ''],
                    'include_ebay_details' => 1,
                    'include_image' => 1,
                ]
            ),

            'editor_type' => Description::EDITOR_TYPE_SIMPLE,
            'cut_long_titles' => Description::CUT_LONG_TITLE_ENABLED,

            'enhancement' => '',
            'gallery_type' => Description::GALLERY_TYPE_EMPTY,

            'image_main_mode' => Description::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',
            'gallery_images_mode' => Description::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => 0,
            'gallery_images_attribute' => '',
            'variation_images_mode' => Description::VARIATION_IMAGES_MODE_PRODUCT,
            'variation_images_limit' => 1,
            'variation_images_attribute' => '',
            'default_image_url' => '',

            'video_mode' => Description::VIDEO_MODE_NONE,
            'video_attribute' => '',
            'video_custom_value' => '',

            'variation_configurable_images' => \Ess\M2ePro\Helper\Json::encode([]),
            'use_supersize_images' => Description::USE_SUPERSIZE_IMAGES_NO,

            'watermark_mode' => Description::WATERMARK_MODE_NO,

            'watermark_settings' => \Ess\M2ePro\Helper\Json::encode(
                [
                    'position' => Description::WATERMARK_POSITION_TOP_RIGHT,
                    'transparent' => Description::WATERMARK_TRANSPARENT_MODE_NO,
                    'opacity_level' => Description::WATERMARK_OPACITY_LEVEL_DEFAULT,

                    'hashes' => [
                        'current' => '',
                        'previous' => '',
                    ],
                ]
            ),

            'watermark_image' => null,
            'compliance_documents' => \Ess\M2ePro\Helper\Json::encode([]),
        ];
    }

    private function prepareComplianceDocuments(array $complianceDocuments): ?string
    {
        $documentsToSave = [];

        foreach ($complianceDocuments as $document) {
            $documentMode = (int)($document['document_mode'] ?? null);
            $documentType = $document['document_type'] ?? null;
            $documentAttribute = $document['document_attribute'] ?? null;
            $documentCustomValue = $document['document_custom_value'] ?? null;
            $documentLanguages = $document['document_languages'] ?? null;

            $availableModes = [
                Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE,
                Description::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE
            ];

            if (
                empty($documentType)
                || !in_array($documentMode, $availableModes)
            ) {
                continue;
            }

            $isModeAttribute = $documentMode === Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE;
            $isModeCustomValue = $documentMode === Description::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE;

            if (
                ($isModeAttribute && empty($documentAttribute))
                || ($isModeCustomValue && empty($documentCustomValue))
            ) {
                continue;
            }

            $hashKey = $documentType;
            if ($isModeAttribute) {
                $hashKey .= $documentAttribute;
            }

            if ($isModeCustomValue) {
                $hashKey .= $documentCustomValue;
            }

            $hashKey = $this->dataHelper->md5String($hashKey);
            $documentsToSave[$hashKey] = [
                'document_mode' => $documentMode,
                'document_type' => $documentType,
                'document_attribute' => $isModeAttribute ? $documentAttribute : '',
                'document_custom_value' => $isModeCustomValue ? $documentCustomValue : '',
                'document_languages' => $documentLanguages,
            ];
        }

        return \Ess\M2ePro\Helper\Json::encode(array_values($documentsToSave));
    }
}
