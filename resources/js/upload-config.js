export const CSRF_HEADER = 'X-CSRF-TOKEN';

export const MESSAGES = {
  required: 'Please add a file.',
  pending: 'Please wait until the upload is finished.',
  failed: 'Remove the files that could not be uploaded.',
};

// FilePond options for one file field, from the PHP data-celerik-upload options.
export function buildFilePondConfig(input, options, csrfToken) {
  const headers = csrfToken ? { [CSRF_HEADER]: csrfToken } : {};
  const maxFiles = options.maxFiles ?? 1;

  return {
    name: input.name,
    allowMultiple: maxFiles > 1,
    maxFiles,
    acceptedFileTypes: options.types ?? [],
    fileSizeBase: 1024,
    maxFileSize: `${Math.floor((options.maxSize ?? 5 * 1024 * 1024) / 1024)}KB`,
    credits: false,
    server: {
      process: { url: options.url, method: 'POST', headers },
      revert: { url: options.url, method: 'DELETE', headers },
      restore: null,
      load: null,
      fetch: null,
    },
    ...options.filepond,
  };
}

// Why the form must not be submitted yet, or null. `files` are normalized FilePond
// items: {done: uploaded and accepted, pending: still uploading, failed: rejected}.
export function uploadProblem(files, options = {}) {
  if (files.some((file) => file.pending)) {
    return MESSAGES.pending;
  }
  if (files.some((file) => file.failed)) {
    return MESSAGES.failed;
  }
  if (options.required && !files.some((file) => file.done)) {
    return MESSAGES.required;
  }
  return null;
}

export function csrfTokenFor(form, formOptions) {
  const field = formOptions?.csrf?.field;
  if (!field) {
    return null;
  }
  for (const input of form.querySelectorAll('input[type="hidden"]')) {
    if (input.name === field) {
      return input.value;
    }
  }
  return null;
}
