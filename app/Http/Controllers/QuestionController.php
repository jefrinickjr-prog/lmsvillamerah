<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    private function admin(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin', 'teacher']), 403);
    }

    public function index(Request $request)
    {
        $this->admin();
        $questions = Question::with(['subject', 'topic', 'options'])->latest();

        foreach (['subject_id', 'topic_id', 'type', 'difficulty', 'status', 'year', 'class_level'] as $filter) {
            if ($request->filled($filter)) {
                $questions->where($filter, $request->input($filter));
            }
        }

        return view('questions.index', [
            'questions' => $questions->paginate(20)->withQueryString(),
            'subjects' => Subject::with('topics')->get(),
        ]);
    }

    public function create()
    {
        $this->admin();

        return view('questions.form', [
            'question' => new Question,
            'subjects' => Subject::with('topics')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();
        $data = $this->validated($request);
        $options = $this->validatedOptions($request, $data['type']);

        DB::transaction(function () use ($data, $options): void {
            $question = Question::create($data + [
                'created_by' => auth()->id(),
                'year' => now()->year,
            ]);
            $this->saveOptions($question, $options);
        });

        return redirect()->route('questions.index')->with('success', 'Soal berhasil disimpan dan sudah masuk ke Bank Soal.');
    }

    public function edit(Question $question)
    {
        $this->admin();
        $question->load('options');

        return view('questions.form', [
            'question' => $question,
            'subjects' => Subject::with('topics')->get(),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $this->admin();
        $data = $this->validated($request);
        $options = $this->validatedOptions($request, $data['type']);

        DB::transaction(function () use ($data, $options, $question): void {
            $question->update($data);
            $question->options()->delete();
            $this->saveOptions($question, $options);
        });

        return redirect()->route('questions.index')->with('success', 'Soal diperbarui dan sudah tampil di Bank Soal.');
    }

    public function destroy(Question $question)
    {
        $this->admin();
        $question->delete();

        return back()->with('success', 'Soal dihapus.');
    }

    public function duplicate(Question $question)
    {
        $this->admin();
        $copy = $question->replicate();
        $copy->question .= ' (Salinan)';
        $copy->created_by = auth()->id();
        $copy->save();

        foreach ($question->options as $option) {
            $copy->options()->create($option->only('option_label', 'option_text', 'is_correct'));
        }

        return back()->with('success', 'Soal diduplikasi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'class_level' => 'nullable|string|max:100',
            'type' => 'required|in:multiple_choice,essay',
            'story' => 'nullable|string',
            'question' => 'required|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'score' => 'required|numeric|min:0|max:10000',
            'explanation' => 'nullable|string',
            'answer_key' => 'nullable|string',
            'instructions' => 'nullable|string',
            'rubric' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'subject_id.required' => 'Subtest wajib dipilih.',
            'question.required' => 'Pertanyaan wajib diisi.',
            'score.required' => 'Bobot nilai wajib diisi.',
        ]);
    }

    private function validatedOptions(Request $request, string $type): array
    {
        if ($type === 'essay') {
            return [];
        }

        $data = $request->validate([
            'options' => 'required|array',
            'options.A' => 'required|string',
            'options.B' => 'required|string',
            'options.C' => 'required|string',
            'options.D' => 'required|string',
            'options.E' => 'nullable|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
        ], [
            'options.A.required' => 'Pilihan A wajib diisi.',
            'options.B.required' => 'Pilihan B wajib diisi.',
            'options.C.required' => 'Pilihan C wajib diisi.',
            'options.D.required' => 'Pilihan D wajib diisi.',
            'correct_answer.required' => 'Pilih satu jawaban yang benar.',
        ]);

        if (blank($data['options'][$data['correct_answer']] ?? null)) {
            throw ValidationException::withMessages([
                'correct_answer' => 'Jawaban benar harus menunjuk ke pilihan yang sudah diisi.',
            ]);
        }

        return $data;
    }

    private function saveOptions(Question $question, array $data): void
    {
        foreach ($data['options'] ?? [] as $label => $text) {
            if (filled($text)) {
                $question->options()->create([
                    'option_label' => $label,
                    'option_text' => $text,
                    'is_correct' => $label === $data['correct_answer'],
                ]);
            }
        }
    }
}
