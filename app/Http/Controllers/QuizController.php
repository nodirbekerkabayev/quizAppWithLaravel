<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;use App\Models\Answer;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use League\Uri\Http;use function Laravel\Prompts\select;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        $quiz = Quiz::withCount('questions')
            ->where('user_id', auth()->user()->id);
        if (request()->has('search')) {
            $quiz->where('title', 'like', '%' . request('search') . '%')
                ->orWhere('description', 'like', '%' . request('search') . '%');
        }
        if (request()->has('sort_by_date')) {
            $quiz->orderBy('id', 'desc');
        }
        $quiz = $quiz->paginate(2);
        return view('dashboard.quizzes',[
            'quizzes' =>$quiz
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'timeLimit' => 'required|integer',
            'questions' => 'required|array',
        ]);
        $quiz = Quiz::create([
            'user_id' => auth()->id(),
            'title'=>$validator['title'],
            'description'=>$validator['description'],
            'time_limit'=>$validator['timeLimit'],
            'slug'=>Str::slug( strtotime('now') . '-'.  $request['title'])
        ]);

        foreach ($validator['questions'] as $question) {
            $questionItem = $quiz->questions()->create([
                'name'=>$question['quiz'],
            ]);
            foreach ($question['options'] as $optionKey => $option) {
                $questionItem->options()->create([
                    'name'=>$option,
                    'is_correct'=>$question['correct'] == $optionKey ? 1 : 0,
                ]);
            }
        }

        return to_route('my-quizzes');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        $quiz = Quiz::where('slug', $slug)->first();
        $result = Result::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->first();
        if (!$result) {
            return view('quiz.show-quiz', compact('quiz'));
        }
        $answers = Answer::query()
            ->where('result_id', $result->id)
            ->get();
        $correctOptionCount = Option::query()
            ->select('question_id')
            ->where('is_correct', 1)
            ->whereIn('id', $answers->pluck('option_id'))
            ->count();
        return view('quiz.result-quiz', [
            'quiz' => $quiz->withCount('questions')->first(),
            'correctOptionCount' => $correctOptionCount,
            'time_taken' => Date::createFromFormat('Y-m-d H:i:s', $result->finished_at)->diff($result->started_at),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quiz $quiz): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        return view('dashboard.edit-quiz',[
            'quiz' => $quiz->load('questions.options'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quiz $quiz): \Illuminate\Http\RedirectResponse
    {
        $validator = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'timeLimit' => 'required|integer',
            'questions' => 'required|array'
        ]);

        $quiz->title = $request['title'];
        $quiz->description = $request['description'];
        $quiz->time_limit = $request['timeLimit'];
        $quiz->slug = Str::slug( strtotime('now') . '-'.  $request['title']);

        $quiz->save();

        $quiz->questions()->delete();
        foreach ($validator['questions'] as $question) {
            $questionItem = $quiz->questions()->create([
                'name'=>$question['quiz'],
            ]);
            foreach ($question['options'] as $optionKey => $option) {
                $questionItem->options()->create([
                    'name'=>$option,
                    'is_correct'=>$question['correct'] == $optionKey ? 1 : 0,
                ]);
            }
        }

        return to_route('my-quizzes')->with('message', 'Quiz updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz): \Illuminate\Http\RedirectResponse
    {
        $quiz->delete();
        return to_route('my-quizzes');
    }
    public function startQuiz(string $slug){
        $quiz = Quiz::query()->where('slug', $slug)->first();
        $result = Result::create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'started_at' => now(),
            'finished_at' => date('Y-m-d H:i:s', strtotime('+' . $quiz->time_limit . ' minutes')),
        ]);

        return view('quiz.start-quiz',[
            'quiz' => $quiz->load('questions.options'),
        ]);
    }
    public function takeQuiz(string $slug, Request $request): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {
        $validator = $request->validate([
            'answer' => 'required|integer|exists:options,id',
        ]);

        $user_id = auth()->id();
        $quiz = Quiz::where('slug', $slug)->first();

        $result = Result::where('quiz_id', $quiz->id)
            ->where('user_id', $user_id)
            ->first();
        if ($result->finished_at <= now()) {
            $answers = Answer::query()
                ->where('result_id', $result->id)
                ->get();
            $correctOptionCount = Option::query()
                ->select('question_id')
                ->where('is_correct', 1)
                ->whereIn('id', $answers->pluck('option_id'))
                ->count();
            return view('quiz.result-quiz', [
                'quiz' => $quiz->withCount('questions')->first(),
                'correctOptionCount' => $correctOptionCount,
                'time_taken' => Date::createFromFormat('Y-m-d H:i:s', $result->finished_at)->diff($result->started_at),
            ]);
        }
//        $result->finished_at = now();
//        $result->save();

        Answer::create([
            'result_id' => $result->id,
            'option_id' => $validator['answer'],
        ]);

        $answers = Answer::query()
            ->where('result_id', $result->id)
            ->get();
        $options = Option::query()
            ->select('question_id')
            ->whereIn('id', $answers->pluck('option_id'))
            ->get();
        $questions = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotIn('id', $options->pluck('question_id'))
            ->with('options')
            ->get();
        if (count($questions)) {
            return view('quiz.take-quiz', [
                'quiz' => $quiz,
                'questions' => $questions,
            ]);
        }
        $correctOptionCount = Option::query()
            ->select('question_id')
            ->where('is_correct', 1)
            ->whereIn('id', $answers->pluck('option_id'))
            ->count();
        return view('quiz.result-quiz', [
            'quiz' => $quiz->withCount('questions')->first(),
            'correctOptionCount' => $correctOptionCount,
            'time_taken' => Date::createFromFormat('Y-m-d H:i:s', $result->finished_at)->diff($result->started_at),
        ]);
    }
}
