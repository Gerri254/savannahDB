@extends('layout')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Edit Grade</h1>
        <a href="{{ route('students.report') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            &larr; Back to Report
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100">
            <h2 class="text-base font-semibold leading-6 text-slate-900">Update Academic Record</h2>
            <p class="mt-1 text-sm text-slate-500">Modifying score for <strong>{{ $grade['student_name'] }}</strong>.</p>
        </div>
        <div class="p-6">
            <form action="{{ route('grades.update', $grade['id']) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="subject" class="block text-sm font-medium leading-6 text-slate-900 mb-2">Subject</label>
                    <input type="text" name="subject" id="subject" value="{{ $grade['subject'] }}" required class="block w-full rounded-md border-0 py-2.5 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
                
                <div class="mb-6">
                    <label for="score" class="block text-sm font-medium leading-6 text-slate-900 mb-2">Score</label>
                    <input type="number" name="score" id="score" value="{{ $grade['score'] }}" required class="block w-full rounded-md border-0 py-2.5 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="flex-1 justify-center rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
                        Update Grade
                    </button>
                    <a href="{{ route('students.report') }}" class="flex-1 justify-center rounded-md bg-white px-3 py-2.5 text-sm font-semibold leading-6 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 text-center transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
