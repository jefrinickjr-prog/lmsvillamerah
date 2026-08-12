<?php
namespace App\Http\Controllers;
use App\Models\{Question,Subject,Topic}; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
class QuestionController extends Controller
{
 private function admin():void { abort_unless(in_array(auth()->user()->role,['admin','super_admin','teacher']),403); }
 public function index(Request $r){$this->admin();$q=Question::with(['subject','topic','options'])->latest(); foreach(['subject_id','topic_id','type','difficulty','status','year','class_level'] as $f) if($r->filled($f))$q->where($f,$r->$f); return view('questions.index',['questions'=>$q->paginate(20)->withQueryString(),'subjects'=>Subject::with('topics')->get()]);}
 public function create(){ $this->admin(); if(!Subject::exists()){Subject::create(['name'=>'Matematika']);Subject::create(['name'=>'Fisika']);} return view('questions.form',['question'=>new Question,'subjects'=>Subject::with('topics')->get()]); }
 public function store(Request $r){$this->admin();$data=$this->validated($r); DB::transaction(function()use($data,$r){$q=Question::create($data+['created_by'=>auth()->id(),'year'=>now()->year]);$this->options($q,$r);});return redirect()->route('questions.index')->with('success','Soal berhasil disimpan.');}
 public function edit(Question $question){$this->admin();$question->load('options');return view('questions.form',compact('question')+['subjects'=>Subject::with('topics')->get()]);}
 public function update(Request $r,Question $question){$this->admin();$data=$this->validated($r);DB::transaction(function()use($data,$r,$question){$question->update($data);$question->options()->delete();$this->options($question,$r);});return redirect()->route('questions.index')->with('success','Soal diperbarui.');}
 public function destroy(Question $question){$this->admin();$question->delete();return back()->with('success','Soal dihapus.');}
 public function duplicate(Question $question){$this->admin();$copy=$question->replicate();$copy->question.=' (Salinan)';$copy->created_by=auth()->id();$copy->save();foreach($question->options as $o)$copy->options()->create($o->only('option_label','option_text','is_correct'));return back()->with('success','Soal diduplikasi.');}
 private function validated(Request $r):array{return $r->validate(['subject_id'=>'required|exists:subjects,id','topic_id'=>'nullable|exists:topics,id','class_level'=>'nullable|string|max:100','type'=>'required|in:multiple_choice,essay','question'=>'required|string','difficulty'=>'required|in:easy,medium,hard','score'=>'required|numeric|min:0|max:10000','explanation'=>'nullable|string','answer_key'=>'nullable|string','instructions'=>'nullable|string','rubric'=>'nullable|string','status'=>'required|in:active,inactive']);}
 private function options(Question $q,Request $r):void{if($q->type!=='multiple_choice')return;$opts=$r->validate(['options'=>'required|array|min:4|max:5','options.*'=>'nullable|string','correct_answer'=>'required|in:A,B,C,D,E']);foreach($opts['options'] as $label=>$text)if(filled($text))$q->options()->create(['option_label'=>$label,'option_text'=>$text,'is_correct'=>$label===$opts['correct_answer']]);}
}
