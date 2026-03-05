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

document.addEventListener('DOMContentLoaded', function(){
    // mapping for non-academic scholarships -> auto-fill name and type
    const scholarshipMap = {
        'Athletic Scholarship': { name: 'Athletic Scholarship', type: 'Sports Excellence Program In-House' },
        "Employees' Grant": { name: "Employees' Grant", type: "Employees' Grant In-House" },
        'Socio-Cultural Scholarship': { name: 'Socio-Cultural Scholarship', type: 'Arts & Culture Program In-House' },
        'Campus Student Publication': { name: 'Campus Student Publication', type: 'University Based Scholarship In-House' },
        'Differently Abled Persons': { name: 'Differently Abled Persons', type: 'University Based Scholarship In-House' },
        'Student Council Federation Grant': { name: 'Student Council Federation Grant', type: 'University Based Scholarship In-House' },
        'CSU Aparri Alumni Association': { name: 'CSU Aparri Alumni Association', type: 'Alumni Grant Program In-House' },
        'Tertiary Education Subsidy': { name: 'Tertiary Education Subsidy', type: 'UniFAST – TES Program Government Based' },
        'Agkaykaysa': { name: 'Agkaykaysa', type: 'Regional Solidarity Grant Government Based' },
        'Tulong Dunong Program': { name: 'Tulong Dunong Program', type: 'Sen. Loren Legarda Scholarship Government Based' },
        'Open Heart Scholarship': { name: 'Open Heart Scholarship', type: 'Private Foundation Grant Private Based' },
        'Talamayan Scholarship': { name: 'Talamayan Scholarship', type: 'Community Support Grant Private Based' }
    };

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

            if (title && scholarshipMap[title]) {
                const map = scholarshipMap[title];

                if (nameSel) {
                    let found = false;
                    for (let i=0;i<nameSel.options.length;i++){
                        if (nameSel.options[i].value === map.name) { nameSel.selectedIndex = i; found = true; break; }
                    }
                    if (!found) { const opt = document.createElement('option'); opt.text = map.name; opt.value = map.name; opt.dataset.generated = '1'; nameSel.appendChild(opt); nameSel.value = map.name; }
                    nameSel.value = map.name;
                    nameSel.dataset.autofilled = '1';
                    console.log('Set scholarship_name to:', map.name);
                }

                if (typeSel) {
                    let foundT = false;
                    for (let i=0;i<typeSel.options.length;i++){
                        if (typeSel.options[i].value === map.type) { typeSel.selectedIndex = i; foundT = true; break; }
                    }
                    if (!foundT) { const opt = document.createElement('option'); opt.text = map.type; opt.value = map.type; opt.dataset.generated = '1'; typeSel.appendChild(opt); typeSel.value = map.type; }
                    typeSel.value = map.type;
                    typeSel.dataset.autofilled = '1';
                    console.log('Set scholarship_type to:', map.type);
                }
            } else {
                if (nameSel) {
                    nameSel.value = '';
                }
                if (typeSel) {
                    typeSel.value = '';
                }
            }

            openApplyModal();
        });
    });

    // Close handlers and reset behavior
    function resetAndClose(){
        const form = document.getElementById('applyForm');
        if (form) form.reset();
        const nameSel = document.getElementById('scholarship_name');
        const typeSel = document.querySelector('select[name="scholarship_type"]');
        if (nameSel) { Array.from(nameSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); nameSel.dataset.autofilled = ''; }
        if (typeSel) { Array.from(typeSel.options).forEach(o=>{ if (o.dataset.generated==='1') o.remove(); }); typeSel.dataset.autofilled = ''; }
        closeApplyModal();
    }

    const closeBtn = document.getElementById('applyModalClose');
    if (closeBtn) closeBtn.addEventListener('click', resetAndClose);
    const cancelBtn = document.getElementById('applyCancel');
    if (cancelBtn) cancelBtn.addEventListener('click', resetAndClose);
    const modal = document.getElementById('applyModal');
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) resetAndClose(); });
});
