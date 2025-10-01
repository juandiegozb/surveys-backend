@extends('layouts.app')
@section('content')
<div class="bg-white p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-4">Survey Management Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="/surveys" class="block p-6 bg-blue-100 rounded-lg hover:bg-blue-200">
            <h2 class="text-xl font-semibold text-blue-900">Manage Surveys</h2>
            <p class="text-blue-700">Create and manage surveys</p>
        </a>
        <a href="/questions" class="block p-6 bg-purple-100 rounded-lg hover:bg-purple-200">
            <h2 class="text-xl font-semibold text-purple-900">Manage Questions</h2>
            <p class="text-purple-700">Create and manage questions</p>
        </a>
    </div>
</div>
@endsection
