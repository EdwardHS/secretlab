<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\KeyValue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Http\Requests\api\KeyValues as KeyValuesRequest;


class KeyValues extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(KeyValuesRequest $request): JsonResponse
    {
        try {
            
            foreach ($request->all() as $key => $value) {
                KeyValue::create([
                    'key' => $key,
                    'values' => $value,
                    'timestamp' => Carbon::now()->timestamp
                ]);
            }
            
            return response()->json(['message' => 'Stored successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to store value'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $key, Request $request): JsonResponse
    {
        try {
            $query = KeyValue::where('key', $key);

            if($request->has('timestamp')){
                if($request->timestamp != null){
                    $query->where('timestamp', $request->timestamp);
                }else{
                    return response()->json(['error' => 'Timestamp cannot be empty'], 404);
                }
            }else{
                $query->latest('timestamp');
            }

            $record = $query->first();
                
            if (!$record) {
                return response()->json(['error' => 'Key not found'], 404);
            }
            
            return response()->json($record->values);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve value'], 500);
        }
    }

    public function getAllRecords(): JsonResponse
    {
        try {
            $records = KeyValue::orderBy('timestamp', 'desc')->get()->groupBy('key')->map(function($records){
                return $records->mapWithKeys(function($record){
                    return [$record->timestamp => [
                        'values' => $record->values,
                        'created_at' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::parse($record->updated_at)->format('Y-m-d H:i:s'),
                    ]];
                });
            });
                
            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve records'], 500);
        }
    }

    public function healthCheck(): JsonResponse
    {
        return response()->json(['message' => 'testing'], 200);
    }
    
}