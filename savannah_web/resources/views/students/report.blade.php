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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Internal ID</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400 font-mono">
                            STU-{{ $row['student_id'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                             <a href="{{ route('grades.edit', $row['id']) }}" class="text-slate-400 hover:text-indigo-600 transition-colors inline-block" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <form action="{{ route('grades.destroy', $row['id']) }}" method="POST" onsubmit="return confirm('Delete this grade?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
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
        const rawData = @json($grades);

        // 1. Extract Unique Student Names for X-Axis Labels
        const studentNames = [...new Set(rawData.map(item => item.name))];

        // 2. Extract Unique Subjects
        const subjects = [...new Set(rawData.map(item => item.subject))];

        // 3. Define a color palette for subjects
        const colors = [
            'rgba(99, 102, 241, 0.7)',  // Indigo
            'rgba(16, 185, 129, 0.7)',  // Emerald
            'rgba(245, 158, 11, 0.7)',  // Amber
            'rgba(239, 68, 68, 0.7)',   // Red
            'rgba(59, 130, 246, 0.7)',  // Blue
            'rgba(139, 92, 246, 0.7)',  // Violet
            'rgba(236, 72, 153, 0.7)'   // Pink
        ];

        // 4. Create Datasets (One per Subject)
        const datasets = subjects.map((subject, index) => {
            return {
                label: subject,
                backgroundColor: colors[index % colors.length],
                borderColor: colors[index % colors.length].replace('0.7', '1'),
                borderWidth: 1,
                // Map scores to the student index. If student has no score for this subject, use null/0.
                data: studentNames.map(name => {
                    const record = rawData.find(r => r.name === name && r.subject === subject);
                    return record ? record.score : null;
                })
            };
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: studentNames,
                datasets: datasets
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Score'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Students'
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    });
</script>
@endsection
