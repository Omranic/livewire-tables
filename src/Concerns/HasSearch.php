<?php

namespace RamonRietdijk\LivewireTables\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RamonRietdijk\LivewireTables\Columns\BaseColumn;

trait HasSearch
{
    public string $globalSearch = '';

    /** @var array<string, mixed> */
    public array $search = [];

    /** @return array<string, mixed> */
    protected function queryStringHasSearch(): array
    {
        if (! $this->useQueryString) {
            return [];
        }

        return [
            'globalSearch' => [
                'as' => $this->getQueryStringName('globalSearch'),
            ],
            'search' => [
                'as' => $this->getQueryStringName('search'),
            ],
        ];
    }

    public function updatedGlobalSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(mixed $value, string $key): void
    {
        if (blank($value)) {
            unset($this->search[$key]);
        }

        $this->resetPage();
    }

    /** @param  Builder<Model>  $builder */
    protected function applyGlobalSearch(Builder $builder): static
    {
        if (strlen($this->globalSearch) === 0 || count($this->columns) === 0) {
            return $this;
        }

        $columns = $this->resolveColumns()->filter(function (BaseColumn $column): bool {
            return $column->isSearchable() && in_array($column->code(), $this->columns);
        });

        $builder->where(function (Builder $builder) use ($columns): void {
            $columns->each(function (BaseColumn $column) use ($builder): void {
                $builder->orWhere(function (Builder $builder) use ($column) {
                    $column->applySearch($builder, $this->globalSearch);
                });
            });
        });

        return $this;
    }

    /** @param  Builder<Model>  $builder */
    protected function applyColumnSearch(Builder $builder): static
    {
        if (count($this->columns) === 0) {
            return $this;
        }

        $columns = $this->resolveColumns()->filter(function (BaseColumn $column): bool {
            return $column->isSearchable() && in_array($column->code(), $this->columns);
        });

        $builder->where(function (Builder $builder) use ($columns): void {
            $columns->each(function (BaseColumn $column) use ($builder): void {
                $builder->where(function (Builder $builder) use ($column) {
                    $search = $this->search[$column->code()] ?? null;

                    if (! blank($search)) {
                        $column->applySearch($builder, $search);
                    }
                });
            });
        });

        return $this;
    }
}
