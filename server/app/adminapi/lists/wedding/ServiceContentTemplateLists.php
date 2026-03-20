<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ServiceContentTemplate;
use app\common\model\wedding\ServiceContentTemplateField;
use app\common\model\wedding\ServiceContentTemplatePage;

class ServiceContentTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['status', 'category_id'];
    }

    public function lists(): array
    {
        $query = ServiceContentTemplate::alias('template')
            ->leftJoin('service_category category', 'category.id = template.category_id')
            ->whereNull('template.delete_time')
            ->field([
                'template.id',
                'template.category_id',
                'template.name',
                'template.status',
                'template.sort',
                'template.create_time',
                'category.name' => 'category_name',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('template.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('template.status', (int)$this->params['status']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('template.category_id', (int)$this->params['category_id']);
        }

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['template.sort' => 'desc', 'template.id' => 'desc'])
            ->select()
            ->toArray();

        $templateIds = array_column($lists, 'id');
        $pageCounts = [];
        $fieldCounts = [];
        if (!empty($templateIds)) {
            $pages = ServiceContentTemplatePage::whereIn('template_id', $templateIds)
                ->whereNull('delete_time')
                ->field('template_id')
                ->select()
                ->toArray();
            $pageCounts = array_count_values(array_map(function ($item) {
                return (int)$item['template_id'];
            }, $pages));
            if (!empty($pages)) {
                $fieldCounts = ServiceContentTemplateField::alias('field')
                    ->leftJoin('service_content_template_page page', 'page.id = field.page_id')
                    ->whereIn('page.template_id', $templateIds)
                    ->whereNull('field.delete_time')
                    ->group('page.template_id')
                    ->column('COUNT(*)', 'page.template_id');
            }
        }

        foreach ($lists as &$item) {
            $item['status_desc'] = (int)$item['status'] === 1 ? '启用' : '停用';
            $item['page_count'] = (int)($pageCounts[$item['id']] ?? 0);
            $item['field_count'] = (int)($fieldCounts[$item['id']] ?? 0);
        }

        return $lists;
    }

    public function count(): int
    {
        $query = ServiceContentTemplate::alias('template')
            ->leftJoin('service_category category', 'category.id = template.category_id')
            ->whereNull('template.delete_time');

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('template.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('template.status', (int)$this->params['status']);
        }

        if (($this->params['category_id'] ?? '') !== '') {
            $query->where('template.category_id', (int)$this->params['category_id']);
        }

        return $query->count();
    }
}
