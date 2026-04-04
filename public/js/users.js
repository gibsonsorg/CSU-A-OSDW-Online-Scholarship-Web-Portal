// State management for pagination
const state = { acad: 1, non: 1 };

const maxPages = { acad: 2, non: 2 };

// Change page function
function changePage(sec, dir) {
    const np = state[sec] + dir;
    if (np < 1 || np > maxPages[sec]) return;
    goPage(sec, np);
}

// Go to specific page
function goPage(sec, page) {
    for (let i = 1; i <= maxPages[sec]; i++) {
        const el = document.getElementById(`${sec}-p${i}`);
        if (el) el.classList.remove('active');
        const pn = document.getElementById(`${sec}-pg${i}`);
        if (pn) pn.classList.remove('active');
    }
    const t = document.getElementById(`${sec}-p${page}`);
    if (t) t.classList.add('active');
    const pa = document.getElementById(`${sec}-pg${page}`);
    if (pa) pa.classList.add('active');
    state[sec] = page;
    document.getElementById(`${sec}-prev`).disabled = (page === 1);
    document.getElementById(`${sec}-next`).disabled = (page === maxPages[sec]);
}

// Filter cards based on search
function filterCards(query = '') {
    const q = (query || '').toLowerCase();
    document.querySelectorAll('.page-set').forEach(ps => { ps.style.display = q ? 'grid' : ''; });
    if (!q) {
        ['acad', 'non'].forEach(sec => {
            for (let i = 1; i <= maxPages[sec]; i++) {
                const el = document.getElementById(`${sec}-p${i}`);
                if (el) el.style.display = '';
                if (el) el.classList.toggle('active', i === state[sec]);
            }
        });
    }
    document.querySelectorAll('.s-card').forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        const title = (card.querySelector('h3')?.textContent || '').toLowerCase();
        card.style.display = (!q || name.includes(q) || title.includes(q)) ? '' : 'none';
    });
}

// Modal handling
function openApplyModal() {
    const m = document.getElementById('applyModal');
    if (m) { m.classList.add('open'); m.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden'; }
}

function closeApplyModal() {
    const m = document.getElementById('applyModal');
    if (m) { m.classList.remove('open'); m.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; }
}

function openAcademicApplyModal() {
    const m = document.getElementById('academicApplyModal');
    if (m) { m.classList.add('open'); m.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden'; }
}

function closeAcademicApplyModal() {
    const m = document.getElementById('academicApplyModal');
    if (m) { m.classList.remove('open'); m.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; }
}

// Academic form popup handlers
function closeAcademicErrorPopup() {
    document.getElementById('academicErrorPopup').style.display = 'none';
}

function closeAcademicSuccessPopup() {
    document.getElementById('academicSuccessPopup').style.display = 'none';
}

function submitAcademicForm() {
    allowSubmitAcademic = true;
    document.getElementById('academicApplyForm').submit();
}

document.addEventListener('DOMContentLoaded', function(){
    // Academic scholarships mapping
    const academicScholarshipMap = {
        'Cagayan Valley Assoc. of Hawaii': { name: 'Cagayan Valley Assoc. of Hawaii', type: 'International Educational Grant' },
        'University Based Scholarship': { name: 'University Based Scholarship', type: 'Academic Excellence – In-House' },
        'ACEF – GIAHEP': { name: 'ACEF – GIAHEP', type: 'Agricultural Credit & Education Fund' },
        'DOST SEI Project Strand': { name: 'DOST SEI Project Strand', type: 'Graduate School Scholarship' },
        'Tulong Dunong Program': { name: 'Tulong Dunong Program', type: 'Kuya Win Scholarship Program' },
        'LGU Aparri': { name: 'LGU Aparri', type: 'Local Government Unit Scholarship' },
        'Natalged A Lasam': { name: 'Natalged A Lasam', type: 'Educational Assistance Grant' },
        'Andres Tamayo Scholarship': { name: 'Andres Tamayo Scholarship', type: 'Private Foundation Grant' },
        'Gokongwei Brothers Foundation': { name: 'Gokongwei Brothers Foundation', type: 'Foundation Scholarship Program' }
    };

    // Non-academic scholarships mapping
    const nonAcademicScholarshipMap = {
        'Athletic Scholarship': { name: 'Athletic Scholarship', type: 'Sports Excellence Program In-House' },
        "Employees' Grant": { name: "Employees' Grant", type: "Employees' Grant In-House" },
        'Socio-Cultural Scholarship': { name: 'Socio-Cultural Scholarship', type: 'Arts & Culture Program In-House' },
        'Campus Student Publication': { name: 'Campus Student Publication', type: 'University Based Scholarship In-House' },
        'Differently Abled Persons': { name: 'Differently Abled Persons', type: 'University Based Scholarship In-House' },
        'Student Council Federation Grant': { name: 'Student Council Federation Grant', type: 'University Based Scholarship In-House' },
        'CSU Aparri Alumni Association': { name: 'CSU Aparri Alumni Association', type: 'Alumni Grant Program In-House' },
        'Tertiary Education Subsidy': { name: 'Tertiary Education Subsidy', type: 'UniFAST – TES Program Government Based' },
        'Agkaykaysa': { name: 'Agkaykaysa', type: 'Regional Solidarity Grant Government Based' },
        'Open Heart Scholarship': { name: 'Open Heart Scholarship', type: 'Private Foundation Grant Private Based' },
        'Talamayan Scholarship': { name: 'Talamayan Scholarship', type: 'Community Support Grant Private Based' }
    };

    // Handle academic scholarship cards
    document.querySelectorAll('#acad-p1 .btn-apply, #acad-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const nameSel = document.getElementById('academic_scholarship_name');
            const typeSel = document.getElementById('academic_scholarship_type');

            // reset any previous generated options
            if (nameSel) Array.from(nameSel.options).forEach(o => { if (o.dataset.generated === '1') o.remove(); });
            if (typeSel) Array.from(typeSel.options).forEach(o => { if (o.dataset.generated === '1') o.remove(); });

            if (title && academicScholarshipMap[title]) {
                const map = academicScholarshipMap[title];

                if (nameSel) {
                    let found = false;
                    for (let i=0;i<nameSel.options.length;i++){
                        if (nameSel.options[i].value === map.name) { nameSel.selectedIndex = i; found = true; break; }
                    }
                    if (!found) { const opt = document.createElement('option'); opt.text = map.name; opt.value = map.name; opt.dataset.generated = '1'; nameSel.appendChild(opt); nameSel.value = map.name; }
                    nameSel.value = map.name;
                    nameSel.dataset.autofilled = '1';
                }

                if (typeSel) {
                    let foundT = false;
                    for (let i=0;i<typeSel.options.length;i++){
                        if (typeSel.options[i].value === map.type) { typeSel.selectedIndex = i; foundT = true; break; }
                    }
                    if (!foundT) { const opt = document.createElement('option'); opt.text = map.type; opt.value = map.type; opt.dataset.generated = '1'; typeSel.appendChild(opt); typeSel.value = map.type; }
                    typeSel.value = map.type;
                    typeSel.dataset.autofilled = '1';
                }
            }

            openAcademicApplyModal();
        });
    });

    // Handle non-academic scholarship cards
    document.querySelectorAll('#non-p1 .btn-apply, #non-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const nameSel = document.getElementById('scholarship_name');
            const typeSel = document.querySelector('select[name="scholarship_type"]');

            // reset any previous generated options
            if (nameSel) Array.from(nameSel.options).forEach(o => { if (o.dataset.generated === '1') o.remove(); });
            if (typeSel) Array.from(typeSel.options).forEach(o => { if (o.dataset.generated === '1') o.remove(); });

            if (title && nonAcademicScholarshipMap[title]) {
                const map = nonAcademicScholarshipMap[title];

                if (nameSel) {
                    let found = false;
                    for (let i=0;i<nameSel.options.length;i++){
                        if (nameSel.options[i].value === map.name) { nameSel.selectedIndex = i; found = true; break; }
                    }
                    if (!found) { const opt = document.createElement('option'); opt.text = map.name; opt.value = map.name; opt.dataset.generated = '1'; nameSel.appendChild(opt); nameSel.value = map.name; }
                    nameSel.value = map.name;
                    nameSel.dataset.autofilled = '1';
                }

                if (typeSel) {
                    let foundT = false;
                    for (let i=0;i<typeSel.options.length;i++){
                        if (typeSel.options[i].value === map.type) { typeSel.selectedIndex = i; foundT = true; break; }
                    }
                    if (!foundT) { const opt = document.createElement('option'); opt.text = map.type; opt.value = map.type; opt.dataset.generated = '1'; typeSel.appendChild(opt); typeSel.value = map.type; }
                    typeSel.value = map.type;
                    typeSel.dataset.autofilled = '1';
                }
            }

            openApplyModal();
        });
    });

    // Non-academic form close handlers
    function resetAndCloseNonAcademic(){
        const form = document.getElementById('applyForm');
        if (form) form.reset();
        const nameSel = document.getElementById('scholarship_name');
        const typeSel = document.querySelector('select[name="scholarship_type"]');
        if (nameSel) { Array.from(nameSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); nameSel.dataset.autofilled = ''; }
        if (typeSel) { Array.from(typeSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); typeSel.dataset.autofilled = ''; }
        closeApplyModal();
    }

    const closeBtn = document.getElementById('applyModalClose');
    if (closeBtn) closeBtn.addEventListener('click', resetAndCloseNonAcademic);
    const modal = document.getElementById('applyModal');
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) resetAndCloseNonAcademic(); });

    // Academic form close handlers
    function resetAndCloseAcademic(){
        const form = document.getElementById('academicApplyForm');
        if (form) form.reset();
        const nameSel = document.getElementById('academic_scholarship_name');
        const typeSel = document.getElementById('academic_scholarship_type');
        if (nameSel) { Array.from(nameSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); nameSel.dataset.autofilled = ''; }
        if (typeSel) { Array.from(typeSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); typeSel.dataset.autofilled = ''; }
        closeAcademicApplyModal();
    }

    const academicCloseBtn = document.getElementById('academicApplyModalClose');
    if (academicCloseBtn) academicCloseBtn.addEventListener('click', resetAndCloseAcademic);
    const academicModal = document.getElementById('academicApplyModal');
    if (academicModal) academicModal.addEventListener('click', function(e){ if (e.target === academicModal) resetAndCloseAcademic(); });

    // File validation for academic form
    let allowSubmitAcademic = false;
    const fileValidationRules = {
        document: {
            extensions: ['pdf', 'docx', 'doc'],
            maxSize: 2 * 1024 * 1024,
            displaySize: '2MB'
        },
        image: {
            extensions: ['jpg', 'jpeg', 'png'],
            maxSize: 1 * 1024 * 1024,
            displaySize: '1MB'
        }
    };

    function getFileCategory(extension) {
        ext = extension.toLowerCase();
        if (fileValidationRules.document.extensions.includes(ext)) {
            return 'document';
        }
        if (fileValidationRules.image.extensions.includes(ext)) {
            return 'image';
        }
        return null;
    }

    // Academic form file validation
    const academicForm = document.getElementById('academicApplyForm');
    if (academicForm) {
        academicForm.addEventListener('submit', function(e) {
            if (allowSubmitAcademic) {
                allowSubmitAcademic = false;
                return true;
            }

            const fileInput = academicForm.querySelector('input[name="uploads[]"]');
            const files = fileInput.files;

            if (files.length === 0) {
                allowSubmitAcademic = true;
                return true;
            }

            e.preventDefault();

            let errors = [];
            let successFiles = [];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileName = file.name;
                const fileSize = file.size;
                const fileExt = fileName.split('.').pop();
                const category = getFileCategory(fileExt);

                if (!category) {
                    errors.push(`"${fileName}" - Unsupported format. Allowed: PDF, DOCX, JPG, PNG`);
                    continue;
                }

                const maxSize = fileValidationRules[category].maxSize;
                const displaySize = fileValidationRules[category].displaySize;
                const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);

                if (fileSize > maxSize) {
                    errors.push(`"${fileName}" - Size: ${fileSizeMB}MB exceeds limit of ${displaySize}`);
                } else {
                    successFiles.push(`"${fileName}" - ${fileSizeMB}MB (${category})`);
                }
            }

            if (errors.length > 0) {
                const errorList = errors.map(err => `<p>${err}</p>`).join('');
                document.getElementById('academicErrorMessage').innerHTML = errorList;
                document.getElementById('academicErrorPopup').style.display = 'flex';
                return false;
            }

            if (successFiles.length > 0) {
                const successList = successFiles.map(file => `<p>${file}</p>`).join('');
                document.getElementById('academicSuccessMessage').innerHTML = 
                    `<p>All files validated successfully and are ready to upload:</p>\n` + successList;
                document.getElementById('academicSuccessPopup').style.display = 'flex';
                return false;
            }

            return true;
        });
    }

    // Close popup handlers for academic error popup
    const academicErrorPopup = document.getElementById('academicErrorPopup');
    if (academicErrorPopup) {
        academicErrorPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAcademicErrorPopup();
            }
        });
    }

    // Close popup handlers for academic success popup
    const academicSuccessPopup = document.getElementById('academicSuccessPopup');
    if (academicSuccessPopup) {
        academicSuccessPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAcademicSuccessPopup();
            }
        });
    }
});
