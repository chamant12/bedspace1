<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * table => [column => [referenced table, on delete]]
     *
     * The original tables used signed `integer` columns for relations; they
     * are changed to unsigned big integers to match `id()` before the
     * constraints are added.
     */
    private array $relations = [
        'districts' => ['province_id' => ['provinces', 'restrict']],
        'cities' => ['district_id' => ['districts', 'restrict']],
        'users' => ['user_type_id' => ['user_types', 'restrict'], 'user_role_id' => ['user_roles', 'restrict']],
        'properties' => ['city_id' => ['cities', 'restrict'], 'property_owner_id' => ['users', 'restrict']],
        'roomtypes' => ['property_id' => ['properties', 'cascade']],
        'ratetypes' => ['property_id' => ['properties', 'cascade']],
        'rooms' => ['roomType_id' => ['roomtypes', 'cascade']],
        'property_amenity_xrefs' => ['property_id' => ['properties', 'cascade'], 'amenity_id' => ['amenities', 'restrict']],
        'roomType_amenity_xrefs' => ['roomType_id' => ['roomtypes', 'cascade'], 'amenity_id' => ['amenities', 'restrict']],
        'currency_rates' => ['currency_type_id' => ['currency_types', 'restrict'], 'property_owner_id' => ['users', 'cascade']],
        'base_rates' => [
            'roomType_id' => ['roomtypes', 'cascade'],
            'rateType_id' => ['ratetypes', 'cascade'],
            'mealType_id' => ['mealtypes', 'restrict'],
            'occupancy_id' => ['occupancies', 'restrict'],
            'currency_type_id' => ['currency_types', 'restrict'],
        ],
    ];

    // Column defaults that must survive ->change()
    private array $defaults = [
        'users.user_type_id' => 1,
        'users.user_role_id' => 1,
        'base_rates.currency_type_id' => 1,
    ];

    // Orphans in these tables are real data problems, never deleted automatically
    private array $protectedTables = ['districts', 'cities', 'users', 'properties'];

    private string $baseRateUnique = 'base_rates_unique_slot';
    private array $baseRateSlot = ['roomType_id', 'rateType_id', 'mealType_id', 'occupancy_id', 'currency_type_id', 'rate_for'];

    public function up(): void
    {
        // SQLite (local dev default) cannot add constraints to existing tables
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->removeOrphans();
        $this->removeDuplicateBaseRates();

        foreach ($this->relations as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach (array_keys($columns) as $column) {
                    $definition = $table->unsignedBigInteger($column);
                    if (array_key_exists("$tableName.$column", $this->defaults)) {
                        $definition->default($this->defaults["$tableName.$column"]);
                    }
                    $definition->change();
                }
            });

            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => [$references, $onDelete]) {
                    $table->foreign($column)->references('id')->on($references)->onDelete($onDelete);
                }
            });
        }

        Schema::table('base_rates', function (Blueprint $table) {
            $table->unique($this->baseRateSlot, $this->baseRateUnique);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('base_rates', function (Blueprint $table) {
            $table->dropUnique($this->baseRateUnique);
        });

        foreach ($this->relations as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach (array_keys($columns) as $column) {
                    $table->dropForeign([$column]);
                }
            });
        }
    }

    private function removeOrphans(): void
    {
        $problems = [];

        foreach ($this->relations as $tableName => $columns) {
            foreach ($columns as $column => [$references]) {
                $orphans = DB::table($tableName)
                    ->whereNotIn($column, DB::table($references)->select('id'));

                $count = $orphans->count();
                if ($count === 0) {
                    continue;
                }

                if (in_array($tableName, $this->protectedTables)) {
                    $problems[] = "$tableName.$column: $count row(s) point to missing $references";
                } else {
                    $orphans->delete();
                }
            }
        }

        if ($problems) {
            throw new RuntimeException("Fix these records before adding foreign keys:\n".implode("\n", $problems));
        }
    }

    // Keep the newest rate where the same slot was saved more than once
    private function removeDuplicateBaseRates(): void
    {
        $keep = DB::table('base_rates')
            ->selectRaw('MAX(id) as id')
            ->groupBy($this->baseRateSlot)
            ->pluck('id');

        DB::table('base_rates')->whereNotIn('id', $keep)->delete();
    }
};
