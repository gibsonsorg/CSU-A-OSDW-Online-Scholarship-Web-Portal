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

function formatDescriptionText(text = '') {
    return String(text || '').replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
}

function parseScholarshipDetails(description = '') {
    const text = (description || '').replace(/\r/g, '').trim();
    if (!text) {
        return { aboutText: 'No additional description is available for this scholarship.', requirements: [] };
    }

    const lines = text.split(/\n+/).map(line => line.trim()).filter(Boolean);
    const aboutLines = [];
    const requirements = [];
    let collectingRequirements = false;

    lines.forEach(line => {
        const normalized = line.toLowerCase();
        if (/^(eligibility requirements|requirements|qualifications|what you need|eligibility)$/.test(normalized)) {
            collectingRequirements = true;
            return;
        }

        const cleaned = line.replace(/^[\*\-\d.]+\s*/, '').trim();
        if (!cleaned) return;

        if (collectingRequirements) {
            requirements.push(cleaned);
        } else if (/^[\*\-]\s*/.test(line)) {
            requirements.push(cleaned);
        } else {
            aboutLines.push(cleaned);
        }
    });

    if (requirements.length === 0) {
        lines.filter(line => /^[\*\-]\s+/.test(line)).forEach(line => {
            requirements.push(line.replace(/^[\*\-]\s+/, '').trim());
        });
    }

    const aboutText = aboutLines.join('<br>').trim() || 'No additional description is available for this scholarship.';
    return { aboutText: formatDescriptionText(aboutText), requirements };
}

function openScholarshipDetailsModal(title, description, modalType = 'non', card = null) {
    const modal = document.getElementById('scholarshipDetailsModal');
    const titleEl = document.getElementById('detailsScholarshipTitle');
    const subtitleEl = document.getElementById('detailsScholarshipSubtitle');
    const tagsEl = document.getElementById('detailsScholarshipTags');
    const imageEl = document.getElementById('detailsScholarshipImage');
    const descEl = document.getElementById('detailsScholarshipDescription');
    const requirementsEl = document.getElementById('detailsScholarshipRequirements');

    if (modal && titleEl && descEl) {
        const details = parseScholarshipDetails(description);
        const subtitle = card?.querySelector('.card-names .prog')?.textContent?.trim() || '';
        const typeTag = card?.querySelector('.type-tag')?.textContent?.trim() || '';
        const metaTag = card?.querySelector('.meta-chip')?.textContent?.trim() || '';
        const cardImage = card?.querySelector('.card-icon img');
        const cardImageSrc = card?.dataset.image || cardImage?.getAttribute('src') || '';

        titleEl.textContent = title || 'Scholarship Details';
        descEl.innerHTML = details.aboutText;

        if (requirementsEl) {
            requirementsEl.innerHTML = '';
            if (details.requirements.length > 0) {
                details.requirements.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    requirementsEl.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = 'No specific requirements are listed for this scholarship yet.';
                requirementsEl.appendChild(li);
            }
        }

        if (subtitleEl) {
            subtitleEl.textContent = subtitle || 'Program details';
        }

        if (tagsEl) {
            tagsEl.innerHTML = '';
            [typeTag, metaTag].filter(Boolean).forEach(tagText => {
                const chip = document.createElement('span');
                chip.className = 'detail-chip';
                if (tagText.toLowerCase().includes('academic') || tagText.toLowerCase().includes('funded')) {
                    chip.classList.add('detail-chip-accent');
                }
                chip.textContent = tagText;
                tagsEl.appendChild(chip);
            });

            if (!tagsEl.children.length) {
                const chip = document.createElement('span');
                chip.className = 'detail-chip';
                chip.textContent = 'Scholarship';
                tagsEl.appendChild(chip);
            }
        }

        if (imageEl) {
            if (cardImageSrc) {
                imageEl.src = cardImageSrc;
                imageEl.alt = title || 'Scholarship logo';
                imageEl.style.display = 'block';
            } else {
                imageEl.removeAttribute('src');
                imageEl.alt = title || 'Scholarship logo';
                imageEl.style.display = 'none';
            }
        }

        modal.dataset.modalType = modalType;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

function closeScholarshipDetailsModal() {
    const modal = document.getElementById('scholarshipDetailsModal');
    if (modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
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
            const description = card?.dataset.description || '';
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

            const enrollmentSection = document.getElementById('academicEnrollmentSection');
            if (enrollmentSection) {
                enrollmentSection.style.display = title === 'University Based Scholarship' ? 'block' : 'none';
            }

            openScholarshipDetailsModal(title, description, 'academic', card);
        });
    });

    // Handle non-academic scholarship cards
    document.querySelectorAll('#non-p1 .btn-apply, #non-p2 .btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const card = btn.closest('.s-card');
            const title = card?.querySelector('.card-names h3')?.textContent?.trim();
            const description = card?.dataset.description || '';
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

            // Show ID label for Open Heart and Talamayan only
            const nonAcademicIDLabel = document.getElementById('nonAcademicIDLabel');
            const nonAcademicUploadsInput = document.getElementById('nonAcademicUploadsInput');
            if (nonAcademicIDLabel) {
                if (title === 'Open Heart Scholarship' || title === 'Talamayan Scholarship') {
                    nonAcademicIDLabel.style.display = 'block';
                    if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.doc,.docx';
                } else {
                    nonAcademicIDLabel.style.display = 'none';
                    if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.docx,.doc,.jpg,.jpeg,.png';
                }
            }

            openScholarshipDetailsModal(title, description, 'non', card);
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
        const nonAcademicIDLabel = document.getElementById('nonAcademicIDLabel');
        const nonAcademicUploadsInput = document.getElementById('nonAcademicUploadsInput');
        if (nonAcademicIDLabel) nonAcademicIDLabel.style.display = 'none';
        if (nonAcademicUploadsInput) nonAcademicUploadsInput.accept = '.pdf,.docx,.doc,.jpg,.jpeg,.png';
        closeApplyModal();
    }

    const detailsBackBtn = document.getElementById('scholarshipDetailsModalBack');
    if (detailsBackBtn) detailsBackBtn.addEventListener('click', closeScholarshipDetailsModal);
    const detailsCloseBtn = document.getElementById('scholarshipDetailsModalClose');
    if (detailsCloseBtn) detailsCloseBtn.addEventListener('click', closeScholarshipDetailsModal);
    const detailsModal = document.getElementById('scholarshipDetailsModal');
    if (detailsModal) detailsModal.addEventListener('click', function(e){ if (e.target === detailsModal) closeScholarshipDetailsModal(); });

    const detailsApplyBtn = document.getElementById('detailsApplyBtn');
    if (detailsApplyBtn) detailsApplyBtn.addEventListener('click', function(){
        closeScholarshipDetailsModal();
        const modal = document.getElementById('scholarshipDetailsModal');
        const modalType = modal?.dataset.modalType || 'non';
        if (modalType === 'academic') {
            openAcademicApplyModal();
        } else {
            openApplyModal();
        }
    });

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
        const enrollmentSection = document.getElementById('academicEnrollmentSection');
        if (nameSel) { Array.from(nameSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); nameSel.dataset.autofilled = ''; }
        if (typeSel) { Array.from(typeSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); typeSel.dataset.autofilled = ''; }
        if (enrollmentSection) { enrollmentSection.style.display = 'none'; }
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

confirmYesBtn.addEventListener('click',function()
{
 if (confirmResolve) confirmResolve(true);
  closeConfirm();
});

confirmCancelBtn.addEventListener('click',function()
{
if (confirmResolve) comfirmResolve(false);
});