<?php

declare(strict_types=1);

namespace app\adminapi\lists\wedding;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wedding\ProviderSchedule;
use app\common\service\ProviderScheduleService;

class ProviderScheduleLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return ['provider_id', 'status'];
    }

    public function lists(): array
    {
        $query = ProviderSchedule::alias('schedule')
            ->leftJoin('service_provider provider', 'provider.id = schedule.provider_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('schedule.delete_time')
            ->field([
                'schedule.id',
                'schedule.provider_id',
                'schedule.service_date',
                'schedule.status',
                'schedule.source_type',
                'schedule.source_id',
                'schedule.remark',
                'schedule.create_time',
                'provider.name' => 'provider_name',
                'provider.category_id',
                'category.name' => 'category_name',
            ]);

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['provider_id'] ?? '') !== '') {
            $query->where('schedule.provider_id', (int)$this->params['provider_id']);
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('schedule.status', (string)$this->params['status']);
        }

        if (($this->params['start_date'] ?? '') !== '') {
            $query->where('schedule.service_date', '>=', (string)$this->params['start_date']);
        }

        if (($this->params['end_date'] ?? '') !== '') {
            $query->where('schedule.service_date', '<=', (string)$this->params['end_date']);
        }

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['schedule.service_date' => 'asc', 'schedule.id' => 'desc'])
            ->select()
            ->toArray();

        $statusMap = ProviderScheduleService::getStatusMap();
        $statusHintMap = ProviderScheduleService::getStatusHintMap();
        foreach ($lists as &$item) {
            $item['status_desc'] = $statusMap[$item['status']] ?? '-';
            $item['status_hint'] = $statusHintMap[$item['status']] ?? '';
            $item['source_desc'] = ProviderScheduleService::getSourceDesc((string)($item['source_type'] ?? ''));
            $item['source_display'] = ProviderScheduleService::getSourceDisplay(
                (string)($item['source_type'] ?? ''),
                (int)($item['source_id'] ?? 0)
            );
        }

        return $lists;
    }

    public function count(): int
    {
        $query = ProviderSchedule::alias('schedule')
            ->leftJoin('service_provider provider', 'provider.id = schedule.provider_id')
            ->leftJoin('service_category category', 'category.id = provider.category_id')
            ->whereNull('schedule.delete_time');

        if (($this->params['keyword'] ?? '') !== '') {
            $keyword = trim((string)$this->params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('provider.name', '%' . $keyword . '%')
                    ->whereOrLike('category.name', '%' . $keyword . '%');
            });
        }

        if (($this->params['provider_id'] ?? '') !== '') {
            $query->where('schedule.provider_id', (int)$this->params['provider_id']);
        }

        if (($this->params['status'] ?? '') !== '') {
            $query->where('schedule.status', (string)$this->params['status']);
        }

        if (($this->params['start_date'] ?? '') !== '') {
            $query->where('schedule.service_date', '>=', (string)$this->params['start_date']);
        }

        if (($this->params['end_date'] ?? '') !== '') {
            $query->where('schedule.service_date', '<=', (string)$this->params['end_date']);
        }

        return $query->count();
    }
}
