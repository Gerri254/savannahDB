@extends('layout')

@section('content')
<div class="space-y-8">
    
    <!-- Analytics Chart -->
    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden p-6">
        <h3 class="text-base font-semibold leading-6 text-slate-900 mb-4">Performance Analytics</h3>
        <div class="relative h-64 w-full">
            <canvas id="gradesChart"></canvas>
        </div>
    </div>

    <!-- Top Section: Header & Assign Form -->
    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50">
             <h3 class="text-base font-semibold leading-6 text-slate-900">Assign Grade</h3>
             <p class="mt-1 text-sm text-slate-500">Record a new grade for a student.</p>
        </div>
        <div class="p-6">
            <form action="{{ route('grades.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                @csrf
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Student</label>
                    <select name="student_id" class="block w-full rounded-md border-0 py-2.5 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        @foreach($students as $student)
                            <option value="{{ $student['id'] }}">{{ $student['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Subject</label>
                    <input type="text" name="subject" placeholder="e.g. Mathematics" required class="block w-full rounded-md border-0 py-2.5 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium leading-6 text-slate-900 mb-2">Score</label>
                    <input type="number" name="score" placeholder="0-100" required class="block w-full rounded-md border-0 py-2.5 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
                <div class="md:col-span-1">
                    <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
                        Submit Grade
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Section: JOIN Table -->
    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
            <div>
                <h3 class="text-base font-semibold leading-6 text-slate-900">Academic Report</h3>
                <p class="mt-1 text-sm text-slate-500">Combined data from Students and Grades (JOIN Operation).</p>
            </div>
            <button onclick="window.location.reload()" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                Refresh Data
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Student Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Subject</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Score</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Internal ID</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($grades as $row)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                            {{ $row['name'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                            {{ $row['subject'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center rounded-md {{ $row['score'] >= 90 ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10' : ($row['score'] >= 70 ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/10') }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                                {{ $row['score'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-400 font-mono">
                            STU-{{ $row['student_id'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                            No grade records found. Assign a grade above!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('gradesChart');
        const data = @json($grades);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(row => row.name),
                datasets: [{
                    label: 'Score',
                    data: data.map(row => row.score),
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>
@endsection
