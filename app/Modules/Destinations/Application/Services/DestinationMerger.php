<?php

namespace App\Modules\Destinations\Application\Services;

use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Folds a duplicate destination into the one that survives.
 *
 * The references are discovered from the live schema rather than listed by
 * hand: every `destination_id` column, every foreign key that points at
 * destinations (whatever the column is called), and every polymorphic pair
 * whose type column holds the `destination` alias. A table added later by any
 * module is therefore repointed too, instead of being silently orphaned or,
 * worse, cascade-deleted with the duplicate.
 */
class DestinationMerger
{
    /**
     * Repoint everything at $duplicate to $survivor, then delete $duplicate.
     *
     * @return array<string, int> rows moved, keyed by "table.column"
     */
    public function merge(Destination $duplicate, Destination $survivor): array
    {
        if ($duplicate->is($survivor)) {
            throw new InvalidArgumentException('A destination cannot be merged into itself.');
        }

        return DB::transaction(function () use ($duplicate, $survivor): array {
            $moved = [];

            foreach ($this->foreignKeyColumns() as [$table, $column]) {
                $count = DB::table($table)->where($column, $duplicate->id)->update([$column => $survivor->id]);
                if ($count > 0) {
                    $moved["{$table}.{$column}"] = $count;
                }
            }

            $alias = $duplicate->getMorphClass();
            foreach ($this->morphColumns() as [$table, $typeColumn, $idColumn]) {
                $count = DB::table($table)
                    ->where($typeColumn, $alias)
                    ->where($idColumn, $duplicate->id)
                    ->update([$idColumn => $survivor->id]);
                if ($count > 0) {
                    $moved["{$table}.{$idColumn}"] = $count;
                }
            }

            // Keep anything the survivor is missing rather than losing it with
            // the duplicate: coordinates, copy and imagery.
            $fill = [];
            foreach (['city', 'latitude', 'longitude', 'description', 'short_description', 'image_url', 'thumbnail', 'tagline', 'best_season'] as $attribute) {
                if (blank($survivor->getAttribute($attribute)) && filled($duplicate->getAttribute($attribute))) {
                    $fill[$attribute] = $duplicate->getAttribute($attribute);
                }
            }
            if ($fill !== []) {
                $survivor->forceFill($fill)->save();
            }

            $duplicate->delete();

            return $moved;
        });
    }

    /**
     * [table, column] for every column that stores a destination id.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function foreignKeyColumns(): array
    {
        $destinations = (new Destination)->getTable();
        $columns = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array('destination_id', Schema::getColumnListing($table), true)) {
                $columns["{$table}.destination_id"] = [$table, 'destination_id'];
            }

            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if ($foreignKey['foreign_table'] !== $destinations) {
                    continue;
                }
                foreach ($foreignKey['columns'] as $column) {
                    $columns["{$table}.{$column}"] = [$table, $column];
                }
            }
        }

        return array_values($columns);
    }

    /**
     * [table, type column, id column] for every polymorphic pair.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public function morphColumns(): array
    {
        $pairs = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            $columns = Schema::getColumnListing($table);
            foreach ($columns as $column) {
                if (! str_ends_with($column, '_type')) {
                    continue;
                }
                $idColumn = substr($column, 0, -5).'_id';
                if (in_array($idColumn, $columns, true)) {
                    $pairs[] = [$table, $column, $idColumn];
                }
            }
        }

        return $pairs;
    }
}
