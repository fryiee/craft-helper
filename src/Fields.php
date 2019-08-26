<?php namespace Fryiee\CraftHelper;

use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\records\FieldLayoutField;
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
        $field = self::createField($name, $type, $handle, $settings, 'matrixBlockType:'.$blockTypeRecord->uid, $instructions, $fieldGroupId, $translationMethod, $translationKeyFormat, $runValidation);

        if (!$field) {
            return false;
        }

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = $blockTypeRecord->getFieldLayout()->one();

        /** @var TabRecord $tab */
        $tab = $fieldLayout->getTabs()->one();

        /** @var Matrix $matrixField */
        $matrixField = $blockTypeRecord->getField()->one();

        return self::addFieldToFieldLayout(
            $field,
            $tab,
            $fieldLayout,
            $required,
            $sortOrder,
            'matrixcontent_'.strtolower($matrixField->handle),
            'field_'.$blockTypeRecord->handle.'_'.$handle,
            $columnType
        );
    }

    /**
     * @param MatrixBlockType $matrixBlockType
     * @param FieldRecord $field
     * @return bool
     */
    public static function removeFieldFromMatrixBlockType(
        MatrixBlockTypeRecord $matrixBlockType,
        FieldRecord $field
    ) {

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = $matrixBlockType->getFieldLayout()->one();

        /** @var Matrix $matrixField */
        $matrixField = $matrixBlockType->getField()->one();

        $deleted = self::deleteFieldFromFieldLayout(
            $fieldLayout,
            $field,
            'matrixcontent_'.strtolower($matrixField->handle),
            'field_'.$matrixBlockType->handle.'_'.$field->handle
        );

        if (!$deleted) {
            return false;
        }

        try {
            $field->delete();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
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
        $field = self::createField($name, $type, $handle, $settings, 'superTableBlockType:'.$superTableBlockTypeRecord->id, $instructions, $fieldGroupId, $translationMethod, $translationKeyFormat, $runValidation);

        if (!$field) {
            return false;
        }

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = FieldLayoutRecord::findOne(['id' => $superTableBlockTypeRecord->fieldLayoutId]);

        /** @var TabRecord $tab */
        $tab = $fieldLayout->getTabs()->one();

        if (strpos($superTableField->context, ':') !== false) {
            $idOrUid = explode(':', $superTableField->context)[1];

            if (strpos($idOrUid, '-') !== false) {
                // it's a uid
                $blockTypeRecord = MatrixBlockType::findOne(['uid' => $idOrUid]);
                $id = $blockTypeRecord->id;
            } else {
                $id = $idOrUid;
            }

            $table = 'stc_' . $id . '_' . strtolower($superTableField->handle);
        } else {
            $table = 'stc_' . strtolower($superTableField->handle);
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
     * @param $superTableField
     * @param $superTableBlockTypeRecord
     * @param FieldRecord $field
     * @return bool
     */
    public static function removeFieldFromSuperTableBlockType(
        $superTableField,
        $superTableBlockTypeRecord,
        FieldRecord $field
    ) {
        if (get_class($superTableField) != 'verbb\supertable\fields\SuperTableField') {
            return false;
        }

        if (get_class($superTableBlockTypeRecord) != 'verbb\supertable\records\SuperTableBlockTypeRecord') {
            return false;
        }

        /** @var FieldLayoutRecord $fieldLayout */
        $fieldLayout = FieldLayoutRecord::findOne(['id' => $superTableBlockTypeRecord->fieldLayoutId]);

        if (strpos($superTableField->context, ':') !== false) {
            $idOrUid = explode(':', $superTableField->context)[1];

            if (strpos($idOrUid, '-') !== false) {
                // it's a uid
                $blockTypeRecord = MatrixBlockType::findOne(['uid' => $idOrUid]);
                $id = $blockTypeRecord->id;
            } else {
                $id = $idOrUid;
            }

            $table = 'stc_' . $id . '_' . strtolower($superTableField->handle);
        } else {
            $table = 'stc_' . strtolower($superTableField->handle);
        }

        $deleted = self::deleteFieldFromFieldLayout(
            $fieldLayout,
            $field,
            $table,
            'field_'.$field->handle
        );

        if (!$deleted) {
            return false;
        }

        try {
            $field->delete();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
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
     * @param FieldRecord $field
     * @param $attribute
     * @param $value
     * @param bool $encode
     * @return bool
     */
    public static function updateFieldAttribute(FieldRecord $field, $attribute, $value, $encode = false)
    {
        $field->$attribute = ($encode ? json_encode($value) : $value);
        return $field->save(false);
    }

    /**
     * @param FieldRecord $field
     * @param $attribute
     * @param $value
     * @param bool $encode
     * @return bool
     */
    public static function updateFieldLayoutFieldAttribute(FieldRecord $field, $attribute, $value, $encode = false)
    {
        $fieldLayoutField = FieldLayoutFieldRecord::findOne(['fieldId' => $field->id]);
        $fieldLayoutField->$attribute = ($encode ? json_encode($value) : $value);
        return $fieldLayoutField->save(false);
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
     * @param $table
     * @param $column
     */
    private static function removeColumnFromTable($table, $column)
    {
        $migration = new Migration();
        $migration->dropColumn($table, $column);
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
     * @param FieldLayoutRecord $fieldLayout
     * @param FieldRecord $field
     * @param $table
     * @param $column
     * @return bool
     */
    private static function deleteFieldFromFieldLayout(FieldLayoutRecord $fieldLayout, FieldRecord $field, $table, $column)
    {
        $fieldLayoutField = FieldLayoutFieldRecord::findOne(['fieldId' => $field->id, 'layoutId' => $fieldLayout->id]);

        if (!$fieldLayoutField) {
            return false;
        }

        try {
            $fieldLayoutField->delete();
        } catch (\Throwable $e) {
            return false;
        }

        self::removeColumnFromTable($table, $column);

        return true;
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