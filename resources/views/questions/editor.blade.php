@php
  $mathTools = [
    ['label' => 'Pecahan', 'example' => 'a/b', 'template' => '$\\frac{angka atas}{angka bawah}$', 'placeholder' => 'angka atas'],
    ['label' => 'Pangkat', 'example' => 'x²', 'template' => '$x^{pangkat}$', 'placeholder' => 'x'],
    ['label' => 'Akar', 'example' => '√x', 'template' => '$\\sqrt{nilai}$', 'placeholder' => 'nilai'],
    ['label' => 'Integral', 'example' => '∫', 'template' => '$\\int_{bawah}^{atas} fungsi \, dx$', 'placeholder' => 'bawah'],
    ['label' => 'Sigma', 'example' => 'Σ', 'template' => '$\\sum_{i=1}^{n} nilai$', 'placeholder' => 'nilai'],
    ['label' => 'Limit', 'example' => 'lim', 'template' => '$\\lim_{x \\to tujuan} fungsi$', 'placeholder' => 'tujuan'],
    ['label' => 'Matriks', 'example' => '2×2', 'template' => '$$\\begin{bmatrix}a & b \\\\ c & d\\end{bmatrix}$$', 'placeholder' => 'a'],
    ['label' => 'Sudut', 'example' => 'θ', 'template' => '$\\theta$', 'placeholder' => null],
    ['label' => 'Delta', 'example' => 'Δ', 'template' => '$\\Delta$', 'placeholder' => null],
    ['label' => '≤', 'example' => 'lebih kecil', 'template' => '$\\leq$', 'placeholder' => null],
    ['label' => '≥', 'example' => 'lebih besar', 'template' => '$\\geq$', 'placeholder' => null],
    ['label' => '≠', 'example' => 'tidak sama', 'template' => '$\\neq$', 'placeholder' => null],
    ['label' => '∞', 'example' => 'tak hingga', 'template' => '$\\infty$', 'placeholder' => null],
    ['label' => '±', 'example' => 'plus minus', 'template' => '$\\pm$', 'placeholder' => null],
  ];
@endphp

@foreach(['question' => 'Pertanyaan', 'explanation' => 'Pembahasan'] as $name => $label)
  <section class="rounded-3xl bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-2">
      <div>
        <label for="{{ $name }}" class="text-lg font-black">{{ $label }}</label>
        <p class="mt-1 text-sm text-slate-500">Tulis kalimat seperti biasa. Klik tombol rumus saat diperlukan.</p>
      </div>
      <button type="button" data-wrap-math data-target="{{ $name }}" class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-indigo-700">
        Jadikan teks terpilih sebagai rumus
      </button>
    </div>

    <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-3">
      <div class="mb-2 text-xs font-black uppercase tracking-wide text-indigo-600">Sisipkan rumus atau simbol</div>
      <div class="flex flex-wrap gap-2">
        @foreach($mathTools as $tool)
          <button type="button" data-math-template="{{ $tool['template'] }}" data-placeholder="{{ $tool['placeholder'] }}" data-target="{{ $name }}" class="rounded-xl border border-indigo-100 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm hover:border-indigo-300 hover:bg-indigo-50">
            <b>{{ $tool['label'] }}</b><span class="ml-1 text-xs text-slate-400">{{ $tool['example'] }}</span>
          </button>
        @endforeach
      </div>
    </div>

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $name === 'question' ? 7 : 7 }}" {{ $name === 'question' ? 'required' : '' }} class="mt-4 w-full rounded-2xl border-slate-200 font-mono text-sm leading-6" placeholder="Contoh: Nilai hari = $\\frac{150}{7}$ = 21, ...">{{ old($name, $question->$name) }}</textarea>

    <div class="mt-4">
      <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-400">Pratinjau hasil</div>
      <div class="math-preview min-h-20 whitespace-pre-wrap rounded-2xl border border-slate-100 bg-slate-50 p-4 leading-8" data-source="{{ $name }}"></div>
    </div>
  </section>
@endforeach
