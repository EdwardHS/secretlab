<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\KeyValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class KeyValuesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('key_values')->truncate();
    }

    public function test_health_check_endpoint()
    {
        $response = $this->getJson('/api/health-check');

        $response->assertStatus(200)
            ->assertJson(['message' => 'testing']);
    }

    public function test_can_store_single_key_value()
    {
        $data = [
            'testKey' => [
                'name' => 'test',
                'value' => 'testValue'
            ]
        ];

        $response = $this->postJson('/api/object', $data);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Stored successfully']);

        $record = DB::table('key_values')
            ->where('key', 'testKey')
            ->first();

        $this->assertNotNull($record);
        
        $this->assertEquals(
            json_decode($record->values, true),
            $data['testKey'],
            'Stored JSON values do not match expected values'
        );
    }

    public function test_can_store_multiple_key_values()
    {
        $data = [
            'key1' => ['value' => 'value1'],
            'key2' => ['value' => 'value2']
        ];

        $response = $this->postJson('/api/object', $data);

        $response->assertStatus(201);

        foreach ($data as $key => $value) {
            $record = DB::table('key_values')
                ->where('key', $key)
                ->first();

            $this->assertNotNull($record, "Record with key {$key} not found");
            $this->assertEquals(
                json_decode($record->values, true),
                $value,
                "Values for key {$key} do not match"
            );
        }
    }

    public function test_can_get_latest_value()
    {
        $now = Carbon::now()->timestamp;
        
        $oldValue = ['value' => 'oldValue'];
        KeyValue::create([
            'key' => 'testKey',
            'values' => $oldValue,
            'timestamp' => $now - 100
        ]);

        $newValue = ['value' => 'newValue'];
        KeyValue::create([
            'key' => 'testKey',
            'values' => $newValue,
            'timestamp' => $now
        ]);

        $response = $this->getJson('/api/object/testKey');

        $response->assertStatus(200);
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(
            $newValue,
            $responseData,
            'Latest value does not match expected value'
        );
    }

    public function test_can_get_value_at_timestamp()
    {
        $timestamp = Carbon::now()->timestamp;
        $testValue = ['value' => 'specificValue'];
        
        KeyValue::create([
            'key' => 'testKey',
            'values' => $testValue,
            'timestamp' => $timestamp
        ]);

        $response = $this->getJson("/api/object/testKey?timestamp={$timestamp}");

        $response->assertStatus(200);
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(
            $testValue,
            $responseData,
            'Retrieved value does not match expected value for timestamp'
        );
    }

    public function test_returns_404_for_nonexistent_key()
    {
        $response = $this->getJson('/api/object/nonexistentKey');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Key not found']);
    }

    public function test_handles_empty_timestamp()
    {
        $response = $this->getJson('/api/object/testKey?timestamp=');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Timestamp cannot be empty']);
    }

    public function test_can_get_all_records()
    {
        $timestamp = Carbon::now()->timestamp;
        
        KeyValue::create([
            'key' => 'key1',
            'values' => ['value' => 'value1'],
            'timestamp' => $timestamp
        ]);

        KeyValue::create([
            'key' => 'key2',
            'values' => ['value' => 'value2'],
            'timestamp' => $timestamp + 100
        ]);

        $response = $this->getJson('/api/object/get_all_records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'key1' => [
                    '*' => [
                        'values',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'key2' => [
                    '*' => [
                        'values',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('key1', $responseData);
        $this->assertArrayHasKey('key2', $responseData);
    }

    public function test_api_rate_limiting()
    {
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/object/testKey');
            $response->assertStatus(404);
        }

        $response = $this->getJson('/api/object/testKey');
        $response->assertStatus(429);
    }

    public function test_can_handle_complex_json_values()
    {
        $complexData = [
            'complexKey' => [
                'string' => 'value',
                'number' => 42,
                'boolean' => true,
                'array' => [1, 2, 3],
                'nested' => [
                    'a' => 1,
                    'b' => 2
                ]
            ]
        ];

        $response = $this->postJson('/api/object', $complexData);
        $response->assertStatus(201);

        $response = $this->getJson('/api/object/complexKey');
        $response->assertStatus(200);
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(
            $complexData['complexKey'],
            $responseData,
            'Complex JSON structure was not preserved'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}