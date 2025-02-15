<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        return view('dashboard.quizzes', [
            'quizzes' => Quiz::withCount('questions')->paginate(1)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        return view('dashboard.create-quiz');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = $request->validate([
           'title' => 'required | string',
           'description' => 'required | string',
           'timeLimit' => 'required | integer',
            'questions' => 'required | array'
        ]);
        $quiz = Quiz::create([
            'user_id' => auth()->id(),
            'title' => $validator['title'],
            'description' => $validator['description'],
            'time_limit' => $validator['timeLimit'],
            'slug' => Str::slug(date(strtotime('now') . '/' . $request['title']))
        ]);
        foreach ($validator['questions'] as $question) {
            $questionItem = $quiz->questions()->create([
                'question_text' => $question['quiz'],
                'correct_answer' => $question['correct']
            ]);
            foreach ($question['options'] as $optionKey => $optionValue) {
                $questionItem->options()->create([
                    'question_id' => $questionItem->id,
                    'option_text' => $optionValue,
                    'is_correct' => $question['correct'] == $optionKey ? 1 : 0,
                ]);
            }
        }
        return to_route('quizzes');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quiz $quiz)
    {
        return view('dashboard.edit-quiz', [
            'quiz' => $quiz
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quiz $quiz)
    {
        $validator=$request->validate([
            'title'=>'required|string|max:255',
            'description'=>'required|string',
            'timeLimit'=>'required|integer',
            'questions'=>'required|array',

        ]);
        $quiz->title=$request['title'];
        $quiz->description=$request['description'];
        $quiz->time_limit=$request['timeLimit'];
        $quiz->slug=Str::slug(strtotime('now') . '/' . $request['title']);
        $quiz->save();

        $quiz->questions()->delete();
        foreach ($validator['questions'] as $question) {
            $questionItem=$quiz->questions()->create([
                'question_text'=>$question['quiz'],
                'correct_answer'=>$question['correct']

            ]);
            foreach ($question['options'] as $optionKey=> $option) {
                $questionItem->options()->create([
                    'option_text'=>$option,
                    'is_correct'=>$question['correct']==$optionKey ? 1 : 0,

                ]);

            }
        }
        return to_route('quizzes')->with('success', 'Quiz updated successfully');

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz): \Illuminate\Http\RedirectResponse
    {
        $quiz->delete();
        return to_route('quizzes');
    }

    public function takeQuiz(Quiz $quiz): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        return view('quiz.take-quiz', [
            'quiz' => $quiz
        ]);
    }
}
