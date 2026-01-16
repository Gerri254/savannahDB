<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\SavannahDB\Database;

class StudentController extends Controller
{
    private Database $db;

    public function __construct()
    {
        // Instantiate our custom engine
        $this->db = new Database();
        
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
        $report = $this->db->execute($sql);

        if (!is_array($report)) {
            $report = [];
        }

        // We also need students for the assignment dropdown
        $students = $this->db->execute("SELECT * FROM students");
        if (!is_array($students)) $students = [];

        return view('students.report', compact('report', 'students'));
    }
}
