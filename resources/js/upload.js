import * as FilePond from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
import { buildFilePondConfig, uploadProblem } from './upload-config.js';

FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize, FilePondPluginImagePreview);

const { FileStatus } = FilePond;
const PENDING = [FileStatus.INIT, FileStatus.IDLE, FileStatus.LOADING, FileStatus.PROCESSING_QUEUED, FileStatus.PROCESSING];
const FAILED = [FileStatus.LOAD_ERROR, FileStatus.PROCESSING_ERROR];

function normalize(item) {
  return {
    done: item.status === FileStatus.PROCESSING_COMPLETE && Boolean(item.serverId),
    pending: PENDING.includes(item.status),
    failed: FAILED.includes(item.status),
  };
}

function showProblem(group, message) {
  let feedback = group.querySelector('.celerik-upload-error');
  if (!message) {
    feedback?.remove();
    return;
  }
  if (!feedback) {
    feedback = document.createElement('div');
    feedback.className = 'invalid-feedback d-block celerik-upload-error';
    feedback.setAttribute('role', 'alert');
    group.append(feedback);
  }
  feedback.textContent = message;
}

// Browsers FilePond does not support keep the plain file input: the form (multipart)
// then submits the files themselves instead of upload ids.
function keepNativeInput(input, options) {
  input.removeAttribute('data-parsley-excluded');
  input.required = Boolean(options.required);
}

// FilePond moves the input's id to its wrapper <div>, which breaks the PHP <label for>.
// Point the label at FilePond's real file input and give it the field's name and help.
function relabel(pond, labelId, describedBy) {
  const browser = pond.element.querySelector('input.filepond--browser');
  const label = document.getElementById(labelId);
  if (!browser || !label) {
    return;
  }
  label.htmlFor = browser.id;
  const dropLabel = pond.element.querySelector('.filepond--drop-label label');
  browser.setAttribute('aria-labelledby', [labelId, dropLabel?.id].filter(Boolean).join(' '));
  if (describedBy) {
    browser.setAttribute('aria-describedby', describedBy);
  }
}

// Replaces each async file input with FilePond, and blocks submission while an upload
// is missing (required), still running, or failed. Resolves once every FilePond is ready.
export async function initUploads(form, csrfToken) {
  const fields = [];
  const ready = [];

  for (const input of form.querySelectorAll('input[type="file"][data-celerik-upload]')) {
    const options = JSON.parse(input.getAttribute('data-celerik-upload'));
    if (!FilePond.supported()) {
      keepNativeInput(input, options);
      continue;
    }
    const group = input.closest('.mb-3') ?? input.parentElement;
    const labelId = `${input.id}-label`;
    const describedBy = input.getAttribute('aria-describedby');

    let resolve;
    ready.push(new Promise((done) => { resolve = done; }));
    const pond = FilePond.create(input, {
      ...buildFilePondConfig(input, options, csrfToken),
      oninit: () => {
        relabel(pond, labelId, describedBy);
        resolve();
      },
    });
    pond.on('processfile', () => showProblem(group, null));
    fields.push({ pond, options, group });
  }

  await Promise.all(ready);

  form.addEventListener('submit', (event) => {
    let blocked = false;
    for (const { pond, options, group } of fields) {
      const problem = uploadProblem(pond.getFiles().map(normalize), options);
      showProblem(group, problem);
      blocked ||= problem !== null;
    }
    if (blocked) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);

  return fields.map((field) => field.pond);
}
