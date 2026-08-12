@include('questions.math')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const type = document.querySelector('#type');
  const toggleQuestionType = () => {
    document.querySelector('#mc').style.display = type.value === 'multiple_choice' ? 'block' : 'none';
    document.querySelector('#essay').style.display = type.value === 'essay' ? 'grid' : 'none';
  };
  type.addEventListener('change', toggleQuestionType);
  toggleQuestionType();

  const insertAtSelection = (editor, text, placeholder = null) => {
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    editor.setRangeText(text, start, end, 'end');
    if (placeholder) {
      const placeholderStart = start + text.indexOf(placeholder);
      editor.setSelectionRange(placeholderStart, placeholderStart + placeholder.length);
    }
    editor.focus();
    editor.dispatchEvent(new Event('input', { bubbles: true }));
  };

  document.querySelectorAll('[data-math-template]').forEach((button) => {
    button.addEventListener('click', () => insertAtSelection(
      document.getElementById(button.dataset.target),
      button.dataset.mathTemplate,
      button.dataset.placeholder || null
    ));
  });

  document.querySelectorAll('[data-wrap-math]').forEach((button) => {
    button.addEventListener('click', () => {
      const editor = document.getElementById(button.dataset.target);
      const selected = editor.value.slice(editor.selectionStart, editor.selectionEnd);
      insertAtSelection(editor, `$${selected || 'ketik rumus'}$`, selected ? null : 'ketik rumus');
    });
  });

  const renderPreview = (preview) => {
    preview.textContent = document.getElementById(preview.dataset.source).value || 'Pratinjau akan tampil di sini.';
    if (typeof window.renderMathInElement === 'function') {
      window.renderMathInElement(preview, {
        delimiters: [
          { left: '$$', right: '$$', display: true },
          { left: '\\(', right: '\\)', display: false },
          { left: '$', right: '$', display: false }
        ],
        throwOnError: false
      });
    }
  };

  document.querySelectorAll('.math-preview').forEach((preview) => {
    document.getElementById(preview.dataset.source).addEventListener('input', () => renderPreview(preview));
    renderPreview(preview);
  });
  window.addEventListener('katex-ready', () => document.querySelectorAll('.math-preview').forEach(renderPreview));
});
</script>
