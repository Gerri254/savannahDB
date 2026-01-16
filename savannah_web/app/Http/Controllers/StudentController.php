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
}
