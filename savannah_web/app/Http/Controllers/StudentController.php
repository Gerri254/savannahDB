<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\SavannahDB\Database;

class StudentController extends Controller
{
    private Database $db;

    public function __construct(Database $db)
    {
        // Injected via Service Container
        $this->db = $db;
        
        // Ensure table exists
        $this->db->execute("CREATE TABLE students");
        $this->db->execute("CREATE TABLE grades");
    }

    public function index()
    {
        $students = $this->db->execute("SELECT * FROM students");
        
        if (!is_array($students)) {
            $students = [];
        }

        return view('students.index', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'class' => 'required|string',
            'phone' => 'required|string'
        ]);

        $name = $request->input('name');
        $class = $request->input('class');
        $phone = $request->input('phone');

        // Manual SQL string interpolation
        $this->db->execute("INSERT INTO students (name, class, phone) VALUES ('$name', '$class', '$phone')");

        return redirect()->route('students.index');
    }

    public function edit($id)
    {
        // Fetch specific student
        $result = $this->db->execute("SELECT * FROM students WHERE id = $id");
        
        if (empty($result) || !isset($result[0])) {
            return redirect()->route('students.index')->with('error', 'Student not found');
        }

        $student = $result[0];
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'class' => 'required|string',
            'phone' => 'required|string'
        ]);

        $name = $request->input('name');
        $class = $request->input('class');
        $phone = $request->input('phone');

        // Execute UPDATE command
        // Note: Our simple parser expects UPDATE table SET col=val, col2=val WHERE id=X
        $this->db->execute("UPDATE students SET name='$name', class='$class', phone='$phone' WHERE id=$id");

        return redirect()->route('students.index');
    }

    public function destroy($id)
    {
        $this->db->execute("DELETE FROM students WHERE id = $id");
        return redirect()->route('students.index');
    }

    public function assignGrade(Request $request)
    {
        $request->validate([
            'student_id' => 'required|numeric',
            'subject' => 'required|string',
            'score' => 'required|numeric',
        ]);

        $studentId = $request->input('student_id');
        $subject = $request->input('subject');
        $score = $request->input('score');

        $this->db->execute("INSERT INTO grades (student_id, subject, score) VALUES ($studentId, '$subject', $score)");

        return redirect()->route('students.report');
    }

    public function reportCard()
    {
        // Demonstration of JOIN and ORDER BY
        // We join students (for name) and grades (for subject/score)
        $sql = "SELECT * FROM students JOIN grades ON students.id = grades.student_id ORDER BY score DESC";
        $grades = $this->db->execute($sql);

        if (!is_array($grades)) {
            $grades = [];
        }

        // We also need students for the assignment dropdown
        $students = $this->db->execute("SELECT * FROM students");
        if (!is_array($students)) $students = [];

        return view('students.report', ['grades' => $grades, 'students' => $students]);
    }

    public function seed()
    {
        // 1. Check if data exists
        $existing = $this->db->execute("SELECT * FROM students");
        
        if (is_array($existing) && count($existing) > 0) {
            // In a real app we might flash a message, for now just redirect
            return redirect()->route('students.report');
        }

        // 2. Loop and Insert
        $subjects = ['Mathematics', 'Science', 'History', 'Physics', 'Literature'];
        $classes = ['10A', '10B', '11A', '12C'];

        for ($i = 1; $i <= 20; $i++) {
            $class = $classes[array_rand($classes)];
            $phone = "07" . rand(10000000, 99999999);
            // Insert Student
            $this->db->execute("INSERT INTO students (name, class, phone) VALUES ('Student {$i}', '$class', '$phone')");

            // Insert Grade for this student
            $subj = $subjects[array_rand($subjects)];
            $gradeScore = rand(70, 100);
            
            $this->db->execute("INSERT INTO grades (student_id, subject, score) VALUES ($i, '$subj', $gradeScore)");
        }

        return redirect()->route('students.report');
    }

    // --- Grade Management ---

    public function editGrade($id)
    {
        $result = $this->db->execute("SELECT * FROM grades WHERE id = $id");
        if (empty($result) || !isset($result[0])) {
            return redirect()->route('students.report');
        }
        
        $grade = $result[0];
        
        // Fetch student name for context
        $studentRes = $this->db->execute("SELECT * FROM students WHERE id = " . $grade['student_id']);
        $grade['student_name'] = $studentRes[0]['name'] ?? 'Unknown Student';

        return view('grades.edit', compact('grade'));
    }

    public function updateGrade(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string',
            'score' => 'required|numeric'
        ]);
        
        $subj = $request->input('subject');
        $score = $request->input('score');
        
        $this->db->execute("UPDATE grades SET subject='$subj', score=$score WHERE id=$id");
        return redirect()->route('students.report');
    }

    public function destroyGrade($id)
    {
        $this->db->execute("DELETE FROM grades WHERE id=$id");
        return redirect()->route('students.report');
    }
}
