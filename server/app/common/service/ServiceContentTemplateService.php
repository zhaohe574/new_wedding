<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\wedding\ServiceCategory;
use app\common\model\wedding\ServiceContentTemplate;
use app\common\model\wedding\ServiceContentTemplateField;
use app\common\model\wedding\ServiceContentTemplatePage;
use think\facade\Db;

class ServiceContentTemplateService
{
    public static function getFieldTypeOptions(): array
    {
        return [
            'single_choice' => '单选',
            'multiple_choice' => '多选',
            'text' => '文本',
            'number' => '数字',
            'textarea' => '长文本',
        ];
    }

    public static function getCategoryOptions(): array
    {
        return ServiceCategory::where(['status' => 1])
            ->whereNull('delete_time')
            ->field('id,name')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }

    public static function getTemplateDetail(int $templateId): array
    {
        $template = ServiceContentTemplate::findOrEmpty($templateId);
        if ($template->isEmpty()) {
            return [];
        }

        $data = $template->toArray();
        $pages = ServiceContentTemplatePage::where(['template_id' => $templateId])
            ->whereNull('delete_time')
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        foreach ($pages as &$page) {
            $fields = ServiceContentTemplateField::where(['page_id' => (int)$page['id']])
                ->whereNull('delete_time')
                ->order(['sort' => 'desc', 'id' => 'asc'])
                ->select()
                ->toArray();

            foreach ($fields as &$field) {
                $field['required'] = (int)($field['required'] ?? 0);
                $field['options'] = self::decodeOptions((string)($field['options_json'] ?? ''));
            }

            $page['fields'] = $fields;
        }

        $data['pages'] = $pages;
        return $data;
    }

    public static function getFrontendTemplateByCategory(int $categoryId): array
    {
        $template = ServiceContentTemplate::where([
            'category_id' => $categoryId,
            'status' => 1,
        ])->whereNull('delete_time')->findOrEmpty();

        if ($template->isEmpty()) {
            return [];
        }

        return self::getTemplateDetail((int)$template['id']);
    }

    public static function validateFrontendFormData(int $categoryId, array $formData): array
    {
        $template = self::getFrontendTemplateByCategory($categoryId);
        if (empty($template)) {
            throw new \RuntimeException('当前服务分类尚未配置内容模板');
        }

        $normalizedFormData = [];
        $summaryPages = [];

        foreach ($template['pages'] ?? [] as $page) {
            $pageSummaryFields = [];

            foreach ($page['fields'] ?? [] as $field) {
                $fieldKey = (string)($field['field_key'] ?? '');
                if ($fieldKey === '') {
                    continue;
                }

                $normalizedValue = self::normalizeFrontendFieldValue($field, $formData[$fieldKey] ?? null);
                if ((int)($field['required'] ?? 0) === 1 && self::isEmptyFrontendValue($field['field_type'] ?? '', $normalizedValue)) {
                    throw new \RuntimeException('请填写' . (string)($field['label'] ?? '必填项'));
                }

                $normalizedFormData[$fieldKey] = $normalizedValue;
                if (self::isEmptyFrontendValue($field['field_type'] ?? '', $normalizedValue)) {
                    continue;
                }

                $pageSummaryFields[] = [
                    'label' => (string)($field['label'] ?? ''),
                    'field_key' => $fieldKey,
                    'field_type' => (string)($field['field_type'] ?? ''),
                    'value' => $normalizedValue,
                    'display_value' => self::formatFrontendFieldDisplayValue($field['field_type'] ?? '', $normalizedValue),
                ];
            }

            $summaryPages[] = [
                'title' => (string)($page['title'] ?? ''),
                'description' => (string)($page['description'] ?? ''),
                'fields' => $pageSummaryFields,
            ];
        }

        return [
            'template_id' => (int)($template['id'] ?? 0),
            'category_id' => $categoryId,
            'template_name' => (string)($template['name'] ?? ''),
            'form_data' => $normalizedFormData,
            'summary_pages' => $summaryPages,
        ];
    }

    public static function saveTemplate(array $params, int $templateId = 0): array
    {
        $categoryId = (int)($params['category_id'] ?? 0);
        $name = trim((string)($params['name'] ?? ''));
        $pages = self::normalizePages($params['pages'] ?? []);

        $query = ServiceContentTemplate::where(['category_id' => $categoryId])->whereNull('delete_time');
        if ($templateId > 0) {
            $query->where('id', '<>', $templateId);
        }
        if ($query->count() > 0) {
            throw new \RuntimeException('同一服务分类只能维护一套模板');
        }

        Db::transaction(function () use ($templateId, $categoryId, $name, $params, $pages) {
            $now = time();
            $templateData = [
                'category_id' => $categoryId,
                'name' => $name,
                'status' => (int)($params['status'] ?? 1),
                'sort' => (int)($params['sort'] ?? 0),
                'update_time' => $now,
            ];

            if ($templateId > 0) {
                $templateData['id'] = $templateId;
                ServiceContentTemplate::update($templateData);
                self::clearTemplateChildren($templateId);
            } else {
                $templateData['create_time'] = $now;
                $template = ServiceContentTemplate::create($templateData);
                $templateId = (int)$template->id;
            }

            foreach ($pages as $pageItem) {
                $page = ServiceContentTemplatePage::create([
                    'template_id' => $templateId,
                    'title' => $pageItem['title'],
                    'description' => $pageItem['description'],
                    'sort' => $pageItem['sort'],
                    'create_time' => $now,
                    'update_time' => $now,
                ]);

                foreach ($pageItem['fields'] as $fieldItem) {
                    ServiceContentTemplateField::create([
                        'page_id' => (int)$page->id,
                        'label' => $fieldItem['label'],
                        'field_key' => $fieldItem['field_key'],
                        'field_type' => $fieldItem['field_type'],
                        'required' => $fieldItem['required'],
                        'options_json' => json_encode($fieldItem['options'], JSON_UNESCAPED_UNICODE),
                        'default_value' => $fieldItem['default_value'],
                        'placeholder' => $fieldItem['placeholder'],
                        'sort' => $fieldItem['sort'],
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                }
            }
        });

        $template = ServiceContentTemplate::where(['category_id' => $categoryId])
            ->whereNull('delete_time')
            ->findOrEmpty();

        return self::getTemplateDetail((int)$template['id']);
    }

    public static function clearTemplateChildren(int $templateId): void
    {
        $pageIds = ServiceContentTemplatePage::where(['template_id' => $templateId])
            ->whereNull('delete_time')
            ->column('id');

        if (!empty($pageIds)) {
            ServiceContentTemplateField::whereIn('page_id', $pageIds)->delete();
        }

        ServiceContentTemplatePage::where(['template_id' => $templateId])->delete();
    }

    private static function normalizePages(array $pages): array
    {
        if (empty($pages)) {
            throw new \RuntimeException('模板至少需要一个页面');
        }

        $fieldKeySet = [];
        $fieldTypeOptions = self::getFieldTypeOptions();
        $normalizedPages = [];

        foreach ($pages as $pageIndex => $page) {
            $title = trim((string)($page['title'] ?? ''));
            if ($title === '') {
                throw new \RuntimeException('模板页面标题不能为空');
            }

            $fields = $page['fields'] ?? [];
            if (!is_array($fields) || empty($fields)) {
                throw new \RuntimeException('模板页面至少需要一个字段');
            }

            $normalizedFields = [];
            foreach ($fields as $fieldIndex => $field) {
                $fieldKey = trim((string)($field['field_key'] ?? ''));
                $fieldType = trim((string)($field['field_type'] ?? ''));
                $label = trim((string)($field['label'] ?? ''));

                if ($label === '') {
                    throw new \RuntimeException('模板字段标题不能为空');
                }
                if ($fieldKey === '') {
                    throw new \RuntimeException('模板字段键不能为空');
                }
                if (isset($fieldKeySet[$fieldKey])) {
                    throw new \RuntimeException('模板字段键不能重复');
                }
                if (!isset($fieldTypeOptions[$fieldType])) {
                    throw new \RuntimeException('模板字段类型不正确');
                }

                $fieldKeySet[$fieldKey] = true;
                $options = array_values(array_filter(array_map(function ($item) {
                    return trim((string)$item);
                }, $field['options'] ?? [])));

                if (in_array($fieldType, ['single_choice', 'multiple_choice'], true) && empty($options)) {
                    throw new \RuntimeException('选项类字段至少需要一个可选项');
                }

                $normalizedFields[] = [
                    'label' => $label,
                    'field_key' => $fieldKey,
                    'field_type' => $fieldType,
                    'required' => (int)!empty($field['required']),
                    'options' => $options,
                    'default_value' => trim((string)($field['default_value'] ?? '')),
                    'placeholder' => trim((string)($field['placeholder'] ?? '')),
                    'sort' => (int)($field['sort'] ?? max(0, 1000 - $fieldIndex)),
                ];
            }

            $normalizedPages[] = [
                'title' => $title,
                'description' => trim((string)($page['description'] ?? '')),
                'sort' => (int)($page['sort'] ?? max(0, 1000 - $pageIndex)),
                'fields' => $normalizedFields,
            ];
        }

        return $normalizedPages;
    }

    private static function decodeOptions(string $optionsJson): array
    {
        if ($optionsJson === '') {
            return [];
        }

        $decoded = json_decode($optionsJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            return trim((string)$item);
        }, $decoded)));
    }

    private static function normalizeFrontendFieldValue(array $field, $value)
    {
        $fieldType = (string)($field['field_type'] ?? '');
        $options = $field['options'] ?? [];

        return match ($fieldType) {
            'single_choice' => self::normalizeSingleChoiceValue((string)($field['label'] ?? ''), $options, $value),
            'multiple_choice' => self::normalizeMultipleChoiceValue((string)($field['label'] ?? ''), $options, $value),
            'number' => self::normalizeNumberValue((string)($field['label'] ?? ''), $value),
            'text', 'textarea' => self::normalizeTextValue($value),
            default => throw new \RuntimeException('模板字段类型不正确'),
        };
    }

    private static function normalizeSingleChoiceValue(string $label, array $options, $value): string
    {
        if (is_array($value)) {
            throw new \RuntimeException($label . '格式不正确');
        }

        $normalized = trim((string)$value);
        if ($normalized === '') {
            return '';
        }

        if (!in_array($normalized, $options, true)) {
            throw new \RuntimeException($label . '选项不正确');
        }

        return $normalized;
    }

    private static function normalizeMultipleChoiceValue(string $label, array $options, $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            throw new \RuntimeException($label . '格式不正确');
        }

        $normalized = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string)$item);
        }, $value))));

        foreach ($normalized as $item) {
            if (!in_array($item, $options, true)) {
                throw new \RuntimeException($label . '选项不正确');
            }
        }

        return $normalized;
    }

    private static function normalizeNumberValue(string $label, $value): int|float|string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_array($value) || is_object($value) || !is_numeric((string)$value)) {
            throw new \RuntimeException($label . '格式不正确');
        }

        return round((float)$value, 2);
    }

    private static function normalizeTextValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            throw new \RuntimeException('模板文本字段格式不正确');
        }

        return trim((string)$value);
    }

    private static function isEmptyFrontendValue(string $fieldType, $value): bool
    {
        if ($fieldType === 'multiple_choice') {
            return empty($value);
        }

        return $value === '';
    }

    private static function formatFrontendFieldDisplayValue(string $fieldType, $value): string
    {
        if ($fieldType === 'multiple_choice') {
            return implode('、', $value);
        }

        return is_scalar($value) ? (string)$value : '';
    }
}
