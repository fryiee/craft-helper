<?php namespace Fryiee\CraftHelper;

use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\records\MatrixBlockType as MatrixBlockTypeRecord;
use craft\records\Field as FieldRecord;
use craft\records\FieldLayout as FieldLayoutRecord;
use craft\records\FieldLayoutField as FieldLayoutFieldRecord;
use craft\records\FieldLayoutTab as TabRecord;
use craft\records\MatrixBlockType;
use yii\db\Migration;

/**
 * Class Fields
 * @package Fryiee\CraftHelper
 */
class Fields
{
    /**
     * @param MatrixBlockTypeRecord $blockTypeRecord
     * @param $name
     * @param $type
     * @param $handle
     * @param $settings
     * @param $columnType
     * @param $required
     * @param $sortOrder
     * @param string $instructions
     * @param int $fieldGroupId
     * @param string $translationMethod
     * @param null $translationKeyFormat
     * @param bool $runValidation
     * @return bool
     */
    public static function addFieldToMatrixBlockType(
        MatrixBlockTypeRecord $blockTypeRecord,
        $name,
        $type,
        $handle,
        $settings,
        $columnType,
        $required,
        $sortOrder,
        $instructions = '',
        $fieldGroupId = 1,
        $translationMethod = 'none',
        $translationKeyFormat = null,
        $runValidation = false
    ) {
        /** @var FieldRecord $field */
        $field = self::createField($name, $type, $handle, $settings, $instructions, $fieldGroupId, $translationMethod, $translationKeyFormat, $runValidation);

        if (!$field) {
            return false;
        }

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = $blockTypeRecord->getFieldLayout();

        /** @var TabRecord $tab */
        $tab = $fieldLayout->getTabs()->one();

        return self::addFieldToFieldLayout(
            $field,
            $tab,
            $fieldLayout,
            $required,
            $sortOrder,
            'matrixcontent_allcontent',
            'field_'.$blockTypeRecord->handle.'_'.$handle,
            $columnType
        );
    }

    /**
     *
     * This expects a field of the following class: verbb\supertable\fields\SuperTableField
     * This expects a block type record of the following class: verbb\supertable\records\SuperTableBlockTypeRecord
     *
     * @param $superTableField
     * @param $superTableBlockTypeRecord
     * @param $name
     * @param $type
     * @param $handle
     * @param $settings
     * @param $columnType
     * @param $required
     * @param $sortOrder
     * @param string $instructions
     * @param int $fieldGroupId
     * @param string $translationMethod
     * @param null $translationKeyFormat
     * @param bool $runValidation
     * @return bool
     */
    public static function addFieldToSuperTableBlockType(
        $superTableField,
        $superTableBlockTypeRecord,
        $name,
        $type,
        $handle,
        $settings,
        $columnType,
        $required,
        $sortOrder,
        $instructions = '',
        $fieldGroupId = 1,
        $translationMethod = 'none',
        $translationKeyFormat = null,
        $runValidation = false
    )
    {
        if (get_class($superTableField) != 'verbb\supertable\fields\SuperTableField') {
            return false;
        }

        if (get_class($superTableBlockTypeRecord) != 'verbb\supertable\records\SuperTableBlockTypeRecord') {
            return false;
        }

        /** @var FieldRecord $field */
        $field = self::createField($name, $type, $handle, $settings, $instructions, $fieldGroupId, $translationMethod, $translationKeyFormat, $runValidation);

        if (!$field) {
            return false;
        }

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = $superTableBlockTypeRecord->getFieldLayout();

        /** @var TabRecord $tab */
        $tab = $fieldLayout->getTabs()->one();

        if (strpos($superTableField->context, ':') !== false) {
            $table = 'stc_' . (explode(':', $superTableField->context)[1]) . $superTableField->handle;
        } else {
            $table = 'stc_' . $superTableField->handle;
        }

        return self::addFieldToFieldLayout(
            $field,
            $tab,
            $fieldLayout,
            $required,
            $sortOrder,
            $table,
            'field_'.$handle,
            $columnType
        );
    }

    /**
     * @param Matrix $field
     * @param $name
     * @param $handle
     * @return bool|MatrixBlockType
     */
    public static function addBlockTypeToMatrixField(Matrix $field, $name, $handle)
    {
        $fieldLayout = new FieldLayoutRecord();
        $fieldLayout->type = MatrixBlock::class;

        if (!$fieldLayout->save(false)) {
            return false;
        }

        $block = new MatrixBlockType();
        $block->fieldId = $field->id;
        $block->fieldLayoutId = $fieldLayout->id;
        $block->name = $name;
        $block->handle = $handle;

        if (!$block->save(false)) {
            return false;
        }

        return $block;
    }

    /**
     * @param $table
     * @param $column
     * @param $type
     */
    private static function addColumnToTable($table, $column, $type)
    {
        $migration = new Migration();
        $migration->addColumn($table, $column, $type);
    }

    /**
     * @param FieldRecord $field
     * @param TabRecord $tab
     * @param FieldLayoutRecord $fieldLayout
     * @param $required
     * @param $sortOrder
     * @param $table
     * @param $column
     * @param $columnType
     * @return bool
     */
    private static function addFieldToFieldLayout(
        FieldRecord $field,
        TabRecord $tab,
        FieldLayoutRecord $fieldLayout,
        $required,
        $sortOrder,
        $table,
        $column,
        $columnType
    ) {
        self::addColumnToTable($table, $column, $columnType);

        $fieldLayoutField = new FieldLayoutFieldRecord();
        $fieldLayoutField->fieldId = $field->id;
        $fieldLayoutField->tabId = $tab->id;
        $fieldLayoutField->layoutId = $fieldLayout->id;
        $fieldLayoutField->required = $required;
        $fieldLayoutField->sortOrder = $sortOrder;

        $saved = $fieldLayoutField->save(false);
        \Craft::$app->fields->updateFieldVersion();

        return $saved;
    }

    /**
     * @param $name
     * @param $type
     * @param $handle
     * @param $settings
     * @param $context
     * @param string $instructions
     * @param int $fieldGroupId
     * @param string $translationMethod
     * @param null $translationKeyFormat
     * @param bool $runValidation
     * @return FieldRecord
     */
    private static function createField(
        $name,
        $type,
        $handle,
        $settings,
        $context,
        $instructions = '',
        $fieldGroupId = 1,
        $translationMethod = 'none',
        $translationKeyFormat = null,
        $runValidation = false
    ) {
        $field = new FieldRecord();

        $field->context = $context;
        $field->name = $name;
        $field->type = $type;
        $field->handle = $handle;
        $field->groupId = $fieldGroupId;
        $field->instructions = $instructions;
        $field->translationKeyFormat = $translationKeyFormat;
        $field->translationMethod = $translationMethod;
        $field->settings = $settings;

        $field->save($runValidation);

        return $field;
    }
}