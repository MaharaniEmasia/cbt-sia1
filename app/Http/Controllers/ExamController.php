<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Answer;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $subjects = Subject::where('is_active', true)->get();
        return view('exam.index', compact('subjects'));
    }

    public function show($id)
    {
        $subject = Subject::findOrFail($id);
        $questions = $subject->questions()->with('answers')->paginate(10);
        
        $savedAnswers = session()->get("exam_answers_{$id}", []);

        return view('exam.show', compact('subject', 'questions', 'savedAnswers'));
    }

    public function storeAnswer(Request $request, $id)
    {
        $questionId = $request->input('question_id');
        $answerId = $request->input('answer_id');

        session()->put("exam_answers_{$id}.{$questionId}", $answerId);

        return response()->json(['success' => true]);
    }

    public function submit(Request $request, $id)
    {
        $submittedAnswers = session()->get("exam_answers_{$id}", []); 

        $totalBenar = 0;
        $totalSalah = 0;
        $totalNilai = 0;
        $soalDijawab = count($submittedAnswers);

        foreach ($submittedAnswers as $questionId => $answerId) {
            $answer = Answer::where('id', $answerId)->where('question_id', $questionId)->first();
            
            if ($answer && $answer->is_correct) {
                $totalBenar++;
                $totalNilai += 10;
            } else {
                $totalSalah++;
            }
        }

        session()->forget("exam_answers_{$id}");

        return redirect()->route('exam.index')->with('success', "Ujian selesai! Kamu menjawab $soalDijawab soal. Benar: $totalBenar, Salah: $totalSalah. Total Nilai: $totalNilai poin.");
    }
}