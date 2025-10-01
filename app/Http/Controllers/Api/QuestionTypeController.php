<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionType;
use Illuminate\Http\Request;

class QuestionTypeController extends Controller
{
    /**
     * Display a listing of question types.
     */
    public function index()
    {
        $questionTypes = QuestionType::where('is_active', true)->get();

        return response()->json([
            'data' => $questionTypes
        ]);
    }

    /**
     * Display the specified question type.
     */
    public function show($id)
    {
        $questionType = QuestionType::findOrFail($id);

        return response()->json([
            'data' => $questionType
        ]);
    }

    /**
     * Get a question type by name.
     */
    public function getByName($name)
    {
        $questionType = QuestionType::where('name', $name)->firstOrFail();

        return response()->json([
            'data' => $questionType
        ]);
    }
}
