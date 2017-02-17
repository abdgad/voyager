<?php

namespace TCG\Voyager\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Facades\DBSchema;

class DatabaseTest extends TestCase
{
    const ON = 'on';
    const OFF = 'on';

    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        $this->install();
    }

    // TODO: Should have one for each driver later
    protected $types = [
        'string' => 'varchar',
    ];

    protected function table($name, $oldName = null, $callback = null, $options = [])
    {
        if ($oldName instanceof \Closure) {
            if (is_array($callback)) {
                $options = $callback;
            }

            $callback = $oldName;
            $oldName = 'New Table';

        }

        $table = new Blueprint($name, $callback);

        $columns = [];

        foreach ($table->getColumns() as $column) {
            $columns[] = [
                'name' => $column->name,
                'oldName' => '',
                'type' => [
                    'name' => isset($this->types[$column->type]) ? $this->types[$column->type] : $column->type,
                ],
                'length' => null,
                'fixed' => false,
                'unsigned' => (boolean) $column->unsigned,
                'autoincrement' => (boolean) $column->autoIncrement,
                'notnull' => false,
                'default' => null,
            ];
        }

        return [
            'name' => $name,
            'oldName' => $oldName,
            'columns' => $columns,
            'indexes' => [],
            'primaryKeyName' => false,
            'foreignKeys' => [],
            'options' => $options,
        ];
    }

    protected function assertTableExists($table)
    {
        $this->assertTrue(Schema::hasTable($table));
    }

    protected function assertTableMatch($name, array $structure)
    {
        $columns = DBSchema::describeTable($name);
        $columnNames = $columns->pluck('field');

        // Test that all expected columns exists
        foreach ($structure['columns'] as $column) {
            $this->assertContains($column['name'], $columnNames);
        }

        $columnNames = collect($structure['columns'])->pluck('name');

        // Test that all columns are expected
        foreach ($columns as $column) {
            $this->assertContains($column['field'], $columnNames);
        }

        // Test that column data matches
        foreach ($structure['columns'] as $expected) {
            $column = $columns->filter(function ($item) use ($expected) {
                return $item['field'] == $expected['name'];
            })->first();

            // Test type
            $this->assertEquals(
                strtolower($expected['type']['name']),
                strtolower($column['type'])
            );

            // Test nullable
            $this->assertEquals(
                $expected['notnull'] == false,
                $column['null'] == 'YES'
            );

            // TODO: Test something else
        }
    }

    public function test_can_create_table()
    {
        $this->disableExceptionHandling();

        Auth::loginUsingId(1);

        // TODO: Test more column types
        $this->post(route('voyager.database.store'), [
            'create_model' => static::OFF,
            'create_migration' => static::OFF,
            'table' => json_encode($table = $this->table('voyagertest', function (Blueprint $table) {
                // TODO: Test more types

                //$table->bigInteger('bigInteger');
                //$table->binary('binary');
                $table->boolean('boolean');
                //$table->char('char');
                $table->date('date');
                //$table->dateTime('dateTime');
                //$table->decimal('decimal');
                //$table->double('double');
                //$table->enum('enum', ['foo', 'bar']);
                //$table->float('float');
                $table->integer('integer');
                //$table->json('json');
                //$table->jsonb('jsonb');
                //$table->longText('longText');
                //$table->mediumInteger('mediumInteger');
                //$table->mediumText('mediumText');
                //$table->smallInteger('smallInteger');
                //$table->string('string');
                $table->text('text');
                $table->time('time');
                //$table->tinyInteger('tinyInteger');
                //$table->timestamp('timestamp');
            })),
            '_token' => csrf_token(),
        ]);

        // Test redirect to correct page
        $this->assertRedirectedToRoute('voyager.database.edit', [
            'table' => 'voyagertest',
        ]);

        // Test table exist
        $this->assertTableExists('voyagertest');

        // Test that table match expectations
        $this->assertTableMatch('voyagertest', $table);

        // TODO: Test nullable
        // TODO: Test primary key
        // TODO: Test unique
        // TODO: Test default value
        // TODO: Test updating table without making changes
        // TODO: Test updating table and making changes
        // TODO: Test renaming table name
        // TODO: Test renaming column name
        // TODO: Test change column type
        // TODO: Test change nullable
        // TODO: Test change key to unique
        // TODO: Test add primary key
        // TODO: Test drop column
        // TODO: Test change default value
        // TODO: Test delete table
    }
}
