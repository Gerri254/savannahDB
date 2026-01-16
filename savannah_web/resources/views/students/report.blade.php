<!DOCTYPE html>
<html>
<head>
    <title>SavannahDB Report Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Student Report Card (JOIN Demo)</h1>
            <a href="{{ route('students.index') }}" class="text-blue-500 hover:underline">Back to Students</a>
        </div>

        <!-- Assign Grade Form -->
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-6">
            <h2 class="text-xl font-bold mb-4">Assign Grade</h2>
            <form action="{{ route('grades.store') }}" method="POST" class="flex gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Student</label>
                    <select name="student_id" class="shadow border rounded py-2 px-3 text-gray-700 w-48">
                        @foreach($students as $student)
                            <option value="{{ $student['id'] }}">{{ $student['name'] }} (ID: {{ $student['id'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                    <input type="text" name="subject" placeholder="Math" class="shadow appearance-none border rounded py-2 px-3 text-gray-700">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Score</label>
                    <input type="number" name="score" placeholder="95" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 w-24">
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Assign
                </button>
            </form>
        </div>

        <!-- Joined Data Table -->
        <div class="bg-white shadow-md rounded overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Student Name</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subject</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Score</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Student ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-bold">{{ $row['name'] }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ $row['subject'] }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $row['score'] >= 90 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $row['score'] }}
                            </span>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-500">{{ $row['student_id'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">No grades assigned yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
