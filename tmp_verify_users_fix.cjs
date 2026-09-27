const fs = require('fs');
const vm = require('vm');
const assert = (cond, msg) => { if (!cond) throw new Error(msg); };
const code = fs.readFileSync('C:\\xampp\\htdocs\\CSU-A-OSDW-Online-Scholarship-Web-Portal-main\\public\\js\\users.js', 'utf8');

function makeInput(name, value = '') {
  return {
    name,
    value,
    type: name === 'course' ? 'select' : 'text',
    disabled: false,
    readOnly: false,
    required: false,
    dataset: {},
    style: {},
    addEventListener() {},
    setAttribute(k, v) { this[k] = String(v); },
    getAttribute(k) { return this[k]; },
    closest() { return null; }
  };
}

function makeForm(id) {
  const fields = {};
  const add = (name, value = '') => { const el = makeInput(name, value); fields[name] = el; return el; };
  add('course');
  add('college_department');
  add('student_id_number');
  add('first_name');
  add('last_name');
  add('scholarship_name');
  add('scholarship_id');

  return {
    id,
    dataset: {},
    attributes: {},
    setAttribute(k, v) { this.attributes[k] = String(v); },
    getAttribute(k) { return this.attributes[k]; },
    querySelector(selector) {
      if (!selector) return null;
      const match = selector.match(/\[name="([^"]+)"\]/);
      return match ? (fields[match[1]] ?? null) : null;
    },
    querySelectorAll() { return []; },
    reset() {
      Object.values(fields).forEach((field) => {
        if (['course', 'college_department', 'student_id_number', 'scholarship_name', 'scholarship_id'].includes(field.name)) {
          field.value = '';
        }
      });
    },
    addEventListener() {},
    removeEventListener() {},
    appendChild() { return null; }
  };
}

const context = {
  console,
  window: {
    location: { origin: 'http://localhost' },
    currentUserId: 1,
    sessionStorage: {
      store: {},
      getItem(k) { return Object.prototype.hasOwnProperty.call(this.store, k) ? this.store[k] : null; },
      setItem(k, v) { this.store[k] = String(v); },
      removeItem(k) { delete this.store[k]; },
      clear() { this.store = {}; }
    },
    reapplicationProfiles: {}
  },
  document: {
    getElementById(id) { return (context.__currentForm && context.__currentForm.id === id) ? context.__currentForm : null; },
    addEventListener() {},
    querySelectorAll() { return []; }
  },
  fetch: async () => ({ ok: true, json: async () => ({ data: { course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', student_id_number: '2024-001' } }) }),
  setTimeout,
  clearTimeout,
  URL: globalThis.URL
};
context.globalThis = context;
vm.createContext(context);
vm.runInContext(code, context);

(async () => {
  for (let i = 0; i < 10; i++) {
    const form = makeForm('academicApplyForm');
    context.__currentForm = form;
    context.window.reapplicationProfiles = {
      'Scholarship A': {
        first_name: 'Ana',
        middle_name: '',
        last_name: 'Student',
        suffix: 'Jr.',
        email: 'student@example.com',
        student_id_number: 'RA-100',
        course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
        college_department: 'College of Information and Computing Science',
        year_level: '2nd Year',
        program: 'Information Technology',
        birthdate: '2000-01-01',
        birthplace: 'Aparri',
        sex: 'Female',
        status: 'Single',
        religion: 'None',
        contact_number: '09123456789',
        home_address: 'Aparri',
        scholarship_name: 'Scholarship A',
        scholarship_type: 'Academic Excellence – In-House',
        application_id: 101,
        id: 55,
        student_information_status: 'rejected',
        personal_information_status: 'rejected',
        scholarship_requirements_status: 'rejected'
      }
    };
    context.prepareReapplication('Scholarship A', 'academic');
    assert(form.querySelector('[name="student_id_number"]').value === 'RA-100', 'Academic reapply student id failed');
    assert(form.querySelector('[name="college_department"]').value === 'College of Information and Computing Science', 'Academic reapply college failed');
    form.dataset.reapplicationPrepared = 'true';
    let fetchCalls = 0;
    context.fetch = async () => {
      fetchCalls += 1;
      return { ok: true, json: async () => ({ data: { course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', student_id_number: 'SHOULD-NOT-APPLY' } }) };
    };
    await context.hydrateFormOpenState(form, 'academic', 'SCH-1', 'Scholarship A');
    assert(fetchCalls === 0, 'Academic reapplication should skip async hydrate');
    assert(form.querySelector('[name="student_id_number"]').value === 'RA-100', 'Academic reapplication value overwritten');
  }

  for (let i = 0; i < 10; i++) {
    const form = makeForm('academicApplyForm');
    context.__currentForm = form;
    context.window.reapplicationProfiles = {};
    form.querySelector('[name="scholarship_id"]').value = 'SCH-NEW';
    form.querySelector('[name="scholarship_name"]').value = 'Fresh Scholarship';
    context.fetch = async () => ({ ok: true, json: async () => ({ data: { course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', student_id_number: 'NEW-100' } }) });
    await context.hydrateFormOpenState(form, 'academic', 'SCH-NEW', 'Fresh Scholarship');
    assert(form.querySelector('[name="student_id_number"]').value === 'NEW-100', 'Academic new form id failed');
    assert(form.querySelector('[name="college_department"]').value === 'College of Information and Computing Science', 'Academic new form college failed');
  }

  for (let i = 0; i < 10; i++) {
    const form = makeForm('applyForm');
    context.__currentForm = form;
    context.window.reapplicationProfiles = {
      'Non Academic Scholarship': {
        first_name: 'Ana',
        last_name: 'Student',
        student_id_number: 'NR-200',
        course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
        college_department: 'College of Information and Computing Science',
        year_level: '1st Year',
        sex: 'Female',
        status: 'Single',
        contact_number: '09123456789',
        home_address: 'Aparri',
        scholarship_name: 'Non Academic Scholarship',
        scholarship_type: 'Sports Excellence Program',
        application_id: 202,
        id: 88,
        student_information_status: 'rejected',
        personal_information_status: 'rejected',
        scholarship_requirements_status: 'rejected'
      }
    };
    context.prepareReapplication('Non Academic Scholarship', 'non');
    assert(form.querySelector('[name="student_id_number"]').value === 'NR-200', 'Non-academic reapply student id failed');
    assert(form.querySelector('[name="college_department"]').value === 'College of Information and Computing Science', 'Non-academic reapply college failed');
    form.dataset.reapplicationPrepared = 'true';
    let fetchCalls = 0;
    context.fetch = async () => {
      fetchCalls += 1;
      return { ok: true, json: async () => ({ data: { course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', student_id_number: 'NON-SHOULD-NOT-APPLY' } }) };
    };
    await context.hydrateFormOpenState(form, 'non-academic', 'N-1', 'Non Academic Scholarship');
    assert(fetchCalls === 0, 'Non-academic reapplication should skip async hydrate');
    assert(form.querySelector('[name="student_id_number"]').value === 'NR-200', 'Non-academic reapplication value overwritten');

    const newForm = makeForm('applyForm');
    context.__currentForm = newForm;
    context.window.reapplicationProfiles = {};
    newForm.querySelector('[name="scholarship_id"]').value = 'N-NEW';
    newForm.querySelector('[name="scholarship_name"]').value = 'Fresh Non Academic';
    context.fetch = async () => ({ ok: true, json: async () => ({ data: { course: 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY', student_id_number: 'NEW-NON-100' } }) });
    await context.hydrateFormOpenState(newForm, 'non-academic', 'N-NEW', 'Fresh Non Academic');
    assert(newForm.querySelector('[name="student_id_number"]').value === 'NEW-NON-100', 'Non-academic new form id failed');
    assert(newForm.querySelector('[name="college_department"]').value === 'College of Information and Computing Science', 'Non-academic new form college failed');
  }

  console.log('PASS: repeated academic/non-academic reopen/new-open checks with no race or blank college field.');
})();
