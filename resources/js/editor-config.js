// Toolbars match what EditorHtml::sanitize() keeps on the server. `formats` limits
// pasted content to the same set, so the editor never holds what the server would drop.
export const TOOLBARS = {
  basic: {
    toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']],
    formats: ['bold', 'italic', 'underline', 'list', 'link'],
  },
  standard: {
    toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['blockquote', 'link'],
      ['clean'],
    ],
    formats: ['header', 'bold', 'italic', 'underline', 'strike', 'list', 'blockquote', 'link'],
  },
  full: {
    toolbar: [
      [{ header: [2, 3, 4, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['blockquote', 'code-block', 'link'],
      ['clean'],
    ],
    formats: ['header', 'bold', 'italic', 'underline', 'strike', 'list', 'blockquote', 'code-block', 'link'],
  },
};

export function buildEditorConfig(options = {}) {
  const config = { theme: 'snow', placeholder: options.placeholder ?? '' };

  if (Array.isArray(options.toolbar)) {
    config.modules = { toolbar: options.toolbar };
  } else {
    const preset = TOOLBARS[options.toolbar] ?? TOOLBARS.standard;
    config.modules = { toolbar: preset.toolbar };
    config.formats = preset.formats;
  }

  return config;
}

// The value submitted with the form: semantic HTML, or '' for an empty editor
// (Quill keeps "<p><br></p>" when empty, which would defeat "required").
//
// Quill 2's getSemanticHTML() turns every space into "&nbsp;", so text would never wrap.
// Undoing it is exact: real non-breaking spaces stay raw U+00A0 characters, and a typed
// "&nbsp;" is escaped to "&amp;nbsp;", which this replacement does not match.
export function editorHtml(quill) {
  if (quill.getText().trim() === '') {
    return '';
  }
  return quill.getSemanticHTML().replaceAll('&nbsp;', ' ');
}
