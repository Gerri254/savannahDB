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
            'score' => 'required|numeric'
        ]);

        $name = $request->input('name');
        $score = $request->input('score');

        // Manual SQL string interpolation
        $this->db->execute("INSERT INTO students (name, score) VALUES ('$name', $score)");

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

        for ($i = 1; $i <= 20; $i++) {
            $score = rand(60, 99);
            // Insert Student
            // Note: In our simple engine, IDs auto-increment. 
            // Since table is empty, this student will get ID = $i (roughly, depending on engine state).
            $this->db->execute("INSERT INTO students (name, score) VALUES ('Student {$i}', $score)");

            // Insert Grade for this student (using $i as ID assumption for fresh table)
            // In a more complex engine we'd fetch the last insert ID.
            $subj = $subjects[array_rand($subjects)];
            $gradeScore = rand(70, 100);
            
            $this->db->execute("INSERT INTO grades (student_id, subject, score) VALUES ($i, '$subj', $gradeScore)");
        }

        return redirect()->route('students.report');
    }
}
