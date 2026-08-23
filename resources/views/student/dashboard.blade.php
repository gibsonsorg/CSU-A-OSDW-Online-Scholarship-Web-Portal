<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships | CSU Aparri</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
    <style>
        /* Applied (user already applied) - keep previous visual style */
        .s-card.card-applied {
            opacity: 0.65;
            pointer-events: none;
        }
        .s-card.card-rejected {
            opacity: 1;
        }
        .s-card.card-rejected .applied-label {
            color: #a81f1f;
        }
        .s-card.card-applied .card-foot,
        .s-card.card-rejected .card-foot {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            align-items: center;
        }
        .card-applied .applied-label {
            color: #a81f1f;
            font-weight: 700;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .card-applied .applied-label.status-approved {
            color: #15803d;
        }
        .card-applied .applied-text,
        .card-rejected .applied-text {
            color: #15803d;
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.3;
            max-width: 220px;
        }
        .card-rejected .applied-text {
            color: #a81f1f;
        }
        .s-card.card-applied .btn-apply {
            pointer-events: none;
            opacity: 0.35;
            cursor: not-allowed;
        }

        /* Category-full unavailable (blocked when user reached limit) */
        .s-card.card-unavailable {
            opacity: 1;
            pointer-events: none;
            border: 1px solid #e2d7d7;
            box-shadow: none;
            background: #fbf6f6;
        }
        .s-card.card-unavailable .card-head,
        .s-card.card-unavailable .card-meta-row,
        .s-card.card-unavailable .card-names h3,
        .s-card.card-unavailable .card-names .prog {
            opacity: 0.7;
        }
        .s-card.card-unavailable .card-foot {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        .card-unavailable .unavailable-text {
            width: 100%;
            max-width: 300px;
            background: #fff;
            border: 1px solid #e9e4e4;
            border-radius: 12px;
            padding: 14px 16px;
            color: #5e2323;
            text-align: center;
            line-height: 1.3;
            box-shadow: 0 1px 0 rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .card-unavailable .btn-apply {
            pointer-events: none;
            opacity: 0.45;
            background: #f0eded;
            color: #7f7f7f;
            border: 1px solid #d9d2d2;
            cursor: not-allowed;
        }
        .Applied-text
        {
              color: #a81f1f;
        }
    </style>
</head>

<body>

<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            </div>
        </div>
    </div>
</x-app-layout>

<!-- ==================== MAIN CONTENT ==================== -->
<div class="main">

<?php $section = request()->query('section', 'acad'); ?>

@if ($section === 'acad')
    <!-- ==================== ACADEMIC GRANTS ==================== -->
    <div class="sec-header">
        <h2>Academic Grants to Students</h2>
    </div>
    <!-- Search Bar -->
        
    <div class="search-wrap">
        <div class="search-box">
            <!-- search input passes its value to the handler -->
            <input type="text" class="search-input" placeholder="Search here" oninput="filterCards(this.value)">
        </div>
    </div>
    <!-- Academic Page 1 -->
    <div class="card-grid page-set active" id="acad-p1">
        <div class="s-card" data-name="cagayan valley association hawaii internationally funded" data-description=" The Cagayan Valley Association of Hawaii (CVAH) Internationally Funded Scholarship is an academic grant program established through a partnership with Cagayan State University (CSU). It provides financial assistance to deserving students in Cagayan Valley, Philippines.

Launched in 2017 through an agreement between CVAH President Engr. Roger A. Urbi and CSU, the program initially supported students at CSU-Carig Campus and later expanded to other campuses such as CSU Tuguegarao. Funded by the Filipino diaspora community in Hawaii, USA, it aims to help undergraduate students meet their educational expenses.
  
More info visits:https://web.facebook.com/cagayanvalleyassoofhawaii

         Eligibility Requirements
         *Must be an officially enrolled undergraduate student at an eligible Cagayan State University campus

         *Must demonstrate clear financial need.Priority is given to students belonging to low-income, indigent families who struggle to cover tertiary education costs beyond free tuition

         *Must maintain the required General Weighted Average (GWA) set by the institutional guidelines for student welfare grants.

         *Must not have any record of severe disciplinary action or academic misconduct within the university
        ">
            <div class="card-head">
                <div class="card-icon">
                    <img src="{{ asset('images/cagayan valley assoc. hawaii logo.png') }}" alt="Hawaii Logo">
            </div>
                <div class="card-names">
                    <h3>Cagayan Valley Assoc. of Hawaii</h3>
                    <p class="prog">International Educational Grant</p>
                    <span class="type-tag tag-intl">Internationally Funded</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Academic</span>
            </div>
            <p class="card-desc">Scholarship funded by the Cagayan Valley Association of Hawaii supporting deserving CSU Aparri students with academic excellence.</p>
            <div class="card-foot"> 
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="university based scholarship academic inhouse" data-description="The University Based Scholarship recognizes students with outstanding academic performance throughout the school year and provides financial assistance to help them continue their studies.">
            <div class="card-head">
                <div class="card-icon">  <img src="{{ asset('images/csu.logo.png') }}" alt="CsuLogo"></div>
                <div class="card-names">
                    <h3>University Based Scholarship</h3>
                    <p class="prog">Academic Excellence – In-House</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Merit-Based</span>
            </div>
            <p class="card-desc">CSU Aparri in-house scholarship for students with outstanding academic performance throughout the academic year.</p>
            <div class="card-foot">
                <span class="deadline">📅 Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="acef giahep government locally funded" data-description="The  ACEF-GIAHEP (Agricultural Competitiveness Enhancement Fund Grants-in-Aid for Higher Education Program) is a joint financial assistance program by the Commission on Higher Education and the Department of Agriculture designed to support undergraduate students pursuing degrees in agriculture, forestry, fisheries, and veterinary medicine
        More info visits:https://acef.da.gov.ph/
    
        Qualifications

        *Citizenship: Must be a Filipino citizen

        *Academic Status: Graduating high school students, high school graduates, college unit earners, or Alternative Learning System (ALS/PEPT) passers.

        *Income Limit: Combined annual gross income of parents or guardians must not exceed ₱300,000.

        *Documents: Requires a PSA birth certificate, proof of admission/enrollment in a CHED-recognized higher education institution, and proof of family income (such as an ITR or BIR certificate of tax exemption).
        ">
            <div class="card-head">
                <div class="card-icon">   <img src="{{ asset('images/acef-giahep scholarship logo.png') }}" alt="ACEF-GIAHEP Logo"></div>
                <div class="card-names">
                    <h3>ACEF – GIAHEP</h3>
                    <p class="prog">Agricultural Credit & Education Fund</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Government scholarship supporting students in agriculture and related fields at the graduate school level.</p>
            <div class="card-foot">
                <span class="deadline">📅 Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="dost sei project strand graduate school government" data-description="TThe DOST scholarship is a government-funded educational financial aid program in the Philippines managed by the Science Education Institute - DOST to support students pursuing science, technology, engineering, and mathematics (STEM) careers.
        more info visit https://science-scholarships.ph
        
    

        *Citizenship: You must be a natural-born Filipino citizen

         *You must be a natural-born Filipino citizen.Health and Character: You must be of good moral character and in good medical health
           
         *Non-STEM Strand Students: Students from other strands (like HUMSS, ABM, or GAS) are qualified only if they belong to the top 5% of their graduating senior high school class.

         *Prior Units: You must not have earned any college or post-secondary vocational units before
         ">
            <div class="card-head">
                <div class="card-icon">  
                     <img src="{{ asset('images/dost.logo.png') }}" alt="DOST Logo">
                    </div>
                <div class="card-names">
                    <h3>DOST SEI Project Strand</h3>
                    <p class="prog">Graduate School Scholarship</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Science & Tech</span>
            </div>
            <p class="card-desc">DOST scholarship for graduate-level students pursuing STEM disciplines under the SEI Project Strand program.</p>
            <div class="card-foot">
                <span class="deadline">📅 Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="tulong dunong kuya win scholarship government" data-description="The Kuya Win Scholarship Program (often linked with legislative or targeted student assistance initiatives spearheaded by Senator Win Gatchalian and aligned with government efforts like the UniFAST / CHED Tulong Dunong Program) is an academic support and financial assistance initiative. It provides monetary aid to help deserving, low-income Filipino college students pay for tuition, school fees, and daily living expenses.
         More info visits:https://legacy.ched.gov.ph/
        Eligibility Requirements
        *Citizenship: Must be a Filipino citizen
        *Must be a registered voter or with parents who are registered voters
        *college students with earned units in recognized and authorized programs of Cagayan State University with at least a passing general weighted average (GWA)
        -1st to 3rd Year - At least 15 Units
        -4th & 5th Year - Consideration without failing grades
        *Must not have any failing grade
        *Must be of good moral character.
        *The combined annual gross income of the applicant's family shall not exceed "Four Hundred Thousand Pesos (Php400,000.00)
        "
        >
            <div class="card-head">
                <div class="card-icon"> <img src="{{ asset('images/kuya win logo.png') }}" alt="DOST Logo"></div>
                <div class="card-names">
                    <h3>Tulong Dunong Program</h3>
                    <p class="prog">Kuya Win Scholarship Program</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Financial assistance for deserving students under the Tulong Dunong – Kuya Win Program for Cagayan scholars.</p>
            <div class="card-foot">
                <span class="deadline">📅 Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="lgu aparri local government unit scholarship" data-description="The LGU Aparri scholarship is a local government assistance program for qualified students from Aparri who are pursuing higher education and need financial support.">
            <div class="card-head">
                <div class="card-icon">
                    <img src="{{ asset('images/lgu.logo.jpg') }}" alt="LGU Logo">
                </div>
                <div class="card-names">
                    <h3>LGU Aparri</h3>
                    <p class="prog">Local Government Unit Scholarship</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Local Gov't</span>
            </div>
            <p class="card-desc">Scholarship granted by the LGU of Aparri to qualified students from the municipality pursuing higher education.</p>
            <div class="card-foot">
                <span class="deadline">📅 Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>
    </div>

    <!-- Academic Page 2 -->
    <div class="card-grid page-set" id="acad-p2">
        <div class="s-card" data-name="natalged lasam educational assistance government" data-description="The Natalged a Lasam Educational Assistance (NALEA) program is a municipal financial aid initiative in Lasam, Cagayan, providing cash stipends, semester allowances, and graduate recognition to support deserving tertiary and high school students
        More info  visits https://lasamonline.com

        

        *Academic Level: Must be a currently enrolled tertiary (college) student.

        *Financial Status: Must belong to a low-income, underprivileged, or financially challenged family.

        *Academic Standing: Must be a deserving student, meaning you must maintain passing grades and meet the minimum academic performance requirements set by the local screening committee."

        >
            <div class="card-head">
<div class="card-icon"><img src="{{ asset('images/lgu lasam.logo.png') }}" alt="LGU lasam logo"></div>
                <div class="card-names">
                    <h3>Natalged A Lasam</h3>
                    <p class="prog">Educational Assistance Grant</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Educational assistance for deserving students affiliated with Lasam, Cagayan under local government funding.</p>
            <div class="card-foot">
                <span class="deadline"> Deadline: TBA</span>
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="andres tamayo scholarship private foundation" data-description=" Is a registered private, non-governmental organization in the Philippines that provides the Handog Edukasyon Scholarship Program to support financially challenged youth. The grant covers educational costs for qualified college and vocational students to alleviate poverty through long-term educational access.
        More info visits:https://andresptamayosrfoundation.com/our-programs  
         Qualifications
         *Maintain Grade Requirement: Keep a General Weighted Average (GWA) of at least 85% every term
         *No Academic Failures: Avoid dropped, incomplete, or failing grades.
         *Full-Time Student Status: Carry a regular, full semester course load without skipping years
         *No Employment: Remain completely unemployed during the scholarship period to focus exclusively on studies.
         *Fixed Track: Shifting to a different course or transferring from a state university to a private institution is prohibited.
        ">
            <div class="card-head">
                <div class="card-icon">
                    <img src="{{ asset('images/andres tamayo.logo.jpg') }}" alt="LGU lasam logo"></div>
                <div class="card-names">
                    <h3>Andres Tamayo Scholarship</h3>
                    <p class="prog">Private Foundation Grant</p>
                    <span class="type-tag tag-private">Private Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Merit-Based</span>
            </div>
            <p class="card-desc">Private scholarship by the Andres Tamayo Foundation supporting environmentally conscious and deserving students.</p>
            <div class="card-foot">
                
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="gokongwei brothers foundation scholarship private" data-description="(GBF) is the strategic philanthropic arm of the Gokongwei Group, established in 1992 by brothers John, Henry, Johnson, and James. It advances STEM education across the Philippines through scholarships, teacher professional development, and technical-vocational training.
        More info: https://www.gokongweibrothersfoundation.org/
        Qualifications
        *Course Enrollment: Must be enrolled or planning to enroll in a GBF priority stem course
        (e.g., Engineering, Computer Science, IT, Analytics)

        *Academic Record: Requires a General Weighted Average (GWA) of at least 85% or 2.0 (or its equivalent).

        *Grades: No failed, dropped, or incomplete grades in the previous academic year.

        *Financial Standing: Must demonstrate a clear need for financial support to complete college studies.

        *Character: Must exhibit good moral character and active participation in extra-curricular activities
        
        ">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/gokongwei logo.png') }}" alt="Gokongwei Brothers Foundation"></div>
                <div class="card-names">
                    <h3>Gokongwei Brothers Foundation</h3>
                    <p class="prog">Foundation Scholarship Program</p>
                    <span class="type-tag tag-private">Private Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Academic Merit</span>
            </div>
            <p class="card-desc">Scholarship for outstanding students pursuing engineering, science, and technology courses at CSU Aparri.</p>
            <div class="card-foot">
                
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>
    </div>

    <!-- Academic Pagination -->
    <div class="pagination">
        <button class="pg-btn" onclick="changePage('acad',-1)" id="acad-prev" disabled>&#8592; Prev</button>
        <span class="pg-num active" id="acad-pg1" onclick="goPage('acad',1)">1</span>
        <span class="pg-num" id="acad-pg2" onclick="goPage('acad',2)">2</span>
        <button class="pg-btn" onclick="changePage('acad',1)" id="acad-next">Next &#8594;</button>
    </div>

@endif

@if ($section === 'non')

    <!-- ==================== NON-ACADEMIC GRANTS ==================== -->
         <!-- Search Bar -->

    <div class="sec-header">
        <h2>Non-Academic Grants to Students</h2>
    </div>
 <!-- Search Bar -->
    <div class="search-wrap">
        <div class="search-box">
            <input type="text" class="search-input" placeholder="Search here" oninput="filterCards(this.value)">
        </div>
    </div>
    <!-- Non-Academic Page 1 -->
    <div class="card-grid page-set active" id="non-p1">
        <div class="s-card" data-name="athletic scholarship sports inhouse" data-description="The Athletic Scholarship recognizes student athletes who represent CSU Aparri in regional and national competitions and provides support for their studies and training.">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/athletic scholarship.logo.png') }}" alt="LGU lasam logo"></div>
                <div class="card-names">
                    <h3>Athletic Scholarship</h3>
                    <p class="prog">Sports Excellence Program</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Sports-Based</span>
            </div>
            <p class="card-desc">Awarded to student athletes who represent CSU Aparri in regional and national competitions.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="employees grant inhouse dependent faculty" data-description=" Employees' Grant Dependent Employee Scholarship short descriptionYou said: Employees' Grant Dependent Employee Scholarship short descriptionEmployees' Grant Dependent Employee Scholarship short descriptionAn Employees' Grant Dependent Employee Scholarship is a financial aid benefit provided by an organization to help pay for the college or vocational school costs of its workers' children. It covers school fees directly as a grant, rather than requiring a loan or a repayment process.
         More info visits:https://scholarship.owwa.gov.ph/
        Eligibility Requirements
        Legal Relationship: Must be a biological child, legally adopted child, or stepchild.
        Age Limits: Generally limited to unmarried dependents under the age of 24
        Academic Minimums: Must maintain a baseline Grade Point Average (GPA), typically a 2.5 on a 4.0 scale or equivalent
        Enrollment Status: Must be accepted or enrolled full-time in an accredited college, university, or trade school
        ">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/employees grant.logo.png') }}" alt="Employees grant logo"></div>
                <div class="card-names">
                    <h3>Employees' Grant</h3>
                    <p class="prog">Dependent Employee Scholarship</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Employee Benefit</span>
            </div>
            <p class="card-desc">Scholarship for dependents of CSU Aparri Campus faculty and administrative staff members.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="socio cultural scholarship arts inhouse" data-description="The Socio-Cultural Scholarship supports students who participate in cultural, arts, and socio-civic activities that represent CSU Aparri with excellence.">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/csu.logo.png') }}" alt="CsuLogo"></div>
                <div class="card-names">
                    <h3>Socio-Cultural Scholarship</h3>
                    <p class="prog">Arts & Culture Program</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Cultural Arts</span>
            </div>
            <p class="card-desc">Granted to students participating in cultural, arts, and socio-civic activities representing CSU Aparri.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="campus student publication university based inhouse" data-description="The Campus Student Publication scholarship is intended for student journalists who contribute to the official campus publication and promote campus communication.">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/campus stud. publication.jpg') }}" alt="stud.council logo"></div>
                <div class="card-names">
                    <h3>Campus Student Publication</h3>
                    <p class="prog">University Based Scholarship</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Publication</span>
            </div>
            <p class="card-desc">For student journalists contributing to the official campus publication of CSU Aparri Campus.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="differently abled persons pwd disability university based inhouse" data-description="The Differently Abled Persons University-Based Scholarship provides financial relief, equal access, and student support for enrolled college students with physical, sensory, or learning impairments. It covers educational costs directly through the host institution to help students finish their degrees
        More info visits:https://web.facebook.com/OSDWAparri
        Eligibility Requirements
        Enrollment: Must be officially accepted or currently enrolled in an undergraduate or graduate degree program at the university
        Proof of Disability: Valid medical certificate, PWD ID card, or official disability documentation
        Academic Standing: Maintain the minimum passing grade required by the school's scholarship office.
        ">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/dap.logo.png') }}" alt="LGU lasam logo"></div>
                <div class="card-names">
                    <h3>Differently Abled Persons</h3>
                    <p class="prog">University Based Scholarship</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Inclusive / PWD</span>
            </div>
            <p class="card-desc">Dedicated scholarship for persons with disabilities (PWD) pursuing higher education at CSU Aparri.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="university student council federation grant inhouse leadership" data-description="The Student Council Federation Grant assists student leaders who demonstrate exemplary service and leadership to the CSU Aparri student body.">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/campus stud.council.png') }}" alt="socio cultural csu-a logo"></div>
                <div class="card-names">
                    <h3>Student Council Federation Grant</h3>
                    <p class="prog">University Based Scholarship</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Leadership</span>
            </div>
            <p class="card-desc">Grant for student council officers demonstrating exemplary leadership and service to the CSU Aparri student body.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>
    </div>

    <!-- Non-Academic Page 2 -->
    <div class="card-grid page-set" id="non-p2">
        <div class="s-card" data-name="csu aparri alumni association inhouse" data-description="The CSU Aparri Alumni Association scholarship supports current students who embody the university values and need financial assistance to succeed academically.">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/csu alumni assoc.logo.jpg') }}" alt="CSU allumni assoc. logo"></div>
                <div class="card-names">
                    <h3>CSU Aparri Alumni Association</h3>
                    <p class="prog">Alumni Grant Program</p>
                    <span class="type-tag tag-inhouse">In-House</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Alumni Funded</span>
            </div>
            <p class="card-desc">Scholarship funded by CSU Aparri Alumni Association supporting current students who embody the university's values.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="tertiary education subsidy tes unifast government locally funded" data-description="The Tertiary Education Subsidy offers financial aid to qualified students under the UniFAST program to help cover school-related expenses each academic year.
        What TES Covers:
• Tuition and Fees: Covers school and miscellaneous fees  
• Living Costs: Books, supplies, board, and travel expenses  
• Extra Grants: Support for students with disabilities and board exam assistance  

Who it is for:
Filipino undergraduate students prioritized by financial need (Listahanan, 4Ps, etc.) enrolled in public or private institutions.

Basic Eligibility Requirements
Citizenship: Must be a Filipino citizen
Enrollment: Officially enrolled in an undergraduate program at a participating State University or College
Degree Status: Must not hold any previous undergraduate degree
Residency: Must meet the admission and retention policies of the school and not exceed maximum allowable residency/overstaying limits
More info: https://unifaast.gov.ph/tes.html"
     
        ">
            <div class="card-head">
                <div class="card-icon">
<img src="{{ asset('images/tes logo.png') }}" alt="TES logo"></div>
                <div class="card-names">
                    <h3>Tertiary Education Subsidy</h3>
                    <p class="prog">UniFAST – TES Program</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Grant-in-aid of Php 40,000 for SUC/LUC grantees and Php 60,000 for Private HEI grantees per Academic Year.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="agkaykaysa government locally funded regional" data-description="The Agkaykaysa scholarship supports deserving students from the Cagayan Region through a regional solidarity program that promotes access to education.">
            <div class="card-head">
                <div class="card-icon">
<img src="{{ asset('images/agkaykaysa.logo.png') }}" alt="LGU lasam logo"></div>
                <div class="card-names">
                    <h3>Agkaykaysa</h3>
                    <p class="prog">Regional Solidarity Grant</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Scholarship supporting deserving students from Cagayan Region through the local government Agkaykaysa program.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="tulong dunong sen loren legarda government locally funded" data-description="The Tulong Dunong Program (TDP), supported through the initiative of Senator Loren Legarda, is a government-funded financial assistance grant implemented via the Commission on Higher Education (CHED). It provides cash stipends (commonly ₱7,500 per semester or up to ₱20,000 depending on the specific allocation terms) to help disadvantaged undergraduate students cover basic school needs.
         More info visits:https://unifast.gov.ph/
         Eligibilility Requirements
         Citizenship: Must be a Filipino citizen
         Student Status: Enrolled in a first undergraduate degree program. Second-degree takers are automatically disqualified
         Institution Type: Enrolled in state universities and colleges (SUCs), CHED-recognized local universities and colleges (LUCs), or registered private higher education institutions (HEIs).
         Exclusivity rule: Must not be a recipient of the Tertiary Education Subsidy (TES), CHED Merit Scholarships, or any other national government-funded student financial aid
         Income Limit: Combined annual gross income of parents or guardians must not exceed ₱400,000
        ">
            <div class="card-head">
                <div class="card-icon">
<img src="{{ asset('images/ched.logo.png') }}" alt="CHED logo"></div>
                <div class="card-names">
                    <h3>Tulong Dunong Program</h3>
                    <p class="prog">Sen. Loren Legarda Scholarship</p>
                    <span class="type-tag tag-govt">Government Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Locally Funded</span>
            </div>
            <p class="card-desc">Financial assistance under Sen. Loren Legarda's Tulong Dunong initiative for Cagayan Valley region students.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="open heart scholarship private foundation" data-description="Open Heart Foundation scholarship functions as a social welfare and youth development program. It covers students from  college level. Rather than just distributing funds, the foundation pairs financial aid with mandatory life skills training, financial literacy seminars, psychosocial support, and regular family monitoring visits by professional social workers to ensure long-term student success
         More info visits:https://openheartfoundation.org.ph/
         Eligibility Requirements
         *Socio-Economic Vibe: Must belong to an underprivileged or financially vulnerable household, with absolute priority given to students who are the potential primary breadwinners of their families.
         *Academic Criteria: Must be currently enrolled in an elementary, high school, or college program. Students must show commitment to passing their classes and completing their degrees.
         *The Kasunduan (Agreement): Both the student and their parent/guardian must sign a formal contract committing to attend the foundation's capacity-building seminars and maintain regular communication with the assigned social worker.
        ">
            <div class="card-head">
                <div class="card-icon"><img src="{{ asset('images/open heart.logo.png') }}" alt="open heart scholarship logo"></div>
                <div class="card-names">
                    <h3>Open Heart Scholarship</h3>
                    <p class="prog">Private Foundation Grant</p>
                    <span class="type-tag tag-private">Private Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Private</span>
            </div>
            <p class="card-desc">Private scholarship offering financial support to students in need, funded by compassionate donors committed to education.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>

        <div class="s-card" data-name="talamayan scholarship private community" data-description="The Talamayan Scholarship is a community-based private support program that helps students in the Cagayan community continue their education with financial assistance.">
            <div class="card-head">
                <div class="card-icon"></div>
                <div class="card-names">
                    <h3>Talamayan Scholarship</h3>
                    <p class="prog">Community Support Grant</p>
                    <span class="type-tag tag-private">Private Based</span>
                </div>
            </div>
            <div class="card-meta-row">
                <span class="meta-chip"><span class="dot"></span>Private</span>
            </div>
            <p class="card-desc">Community-based private scholarship fostering solidarity and providing educational support in the Cagayan community.</p>
            <div class="card-foot">
                <a href="#" class="btn-apply">VIEW & APPLY</a>
            </div>
        </div>
    </div>

    <!-- Non-Academic Pagination -->
     
    <div class="pagination">
        <button class="pg-btn" onclick="changePage('non',-1)" id="non-prev" disabled>&#8592; Prev</button>
        <span class="pg-num active" id="non-pg1" onclick="goPage('non',1)">1</span>
        <span class="pg-num" id="non-pg2" onclick="goPage('non',2)">2</span>
        <button class="pg-btn" onclick="changePage('non',1)" id="non-next">Next &#8594;</button>
    </div>

@endif

@if ($section === 'status')
    <!-- ==================== APPLICATION STATUS ==================== -->
    <div class="status-container">
        <div class="status-card">
            <div class="status-header">
                <h1 class="status-title">My Application Status</h1>
                
            </div>

            @php
                $userApplications = \App\Models\StudentProfile::orderBy('created_at', 'desc')->get();
                $latestApp = $userApplications->first();
            @endphp

            @if($latestApp)
                <div class="progress-tracker">
                    <!-- Step 1: Application Submitted -->
                    <div class="progress-step">
                        <div class="step-indicator step-completed">
                            <span class="step-number">✓</span>
                        </div>
                        <div class="step-content">
                            <div class="step-title">Application Submitted</div>
                            <div class="step-date">{{ $latestApp->created_at->format('M d') }} - Completed</div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="progress-connector {{ $latestApp->application_status !== 'pending' ? 'step-completed' : '' }}"></div>

                    <!-- Step 2: Application Review -->
                    <div class="progress-step">
                        <div class="step-indicator {{ $latestApp->application_status === 'pending' ? 'step-active' : 'step-completed' }}">
                            <span class="step-number">{{ $latestApp->application_status === 'pending' ? '2' : '✓' }}</span>
                        </div>
                        <div class="step-content">
                            <div class="step-title">Application Review</div>
                            <div class="step-date">{{ $latestApp->created_at->format('M d') }} - {{ $latestApp->application_status === 'pending' ? 'Active' : 'Completed' }}</div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="progress-connector {{ in_array($latestApp->application_status, ['approved', 'rejected']) ? 'step-completed' : '' }}"></div>

                    <!-- Step 3: Admin Decision -->
                     <div class="progress-step">
                        <div class="step-indicator {{ $latestApp->application_status === 'approved' ? 'step-completed' : ($latestApp->application_status === 'rejected' ? 'step-rejected' : '') }}">
                            <span class="step-number">{{ $latestApp->application_status === 'approved' ? '✓' : ($latestApp->application_status === 'rejected' ? '✕' : '3') }}</span>
                        </div>
                        <div class="step-content">
                            <div class="step-title">Admin Decision</div>
                            <div class="step-date">{{ $latestApp->created_at->format('M d') }} - {{ in_array($latestApp->application_status, ['approved', 'rejected']) ? ucfirst($latestApp->application_status) : 'Not Started' }}</div>
                        </div>
                    </div>
                </div>
                        

                <div class="application-details">
                    <div class="detail-row">
                        <span class="detail-label">Scholarship:</span>
                        <span class="detail-value">{{ $latestApp->scholarship_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">{{ $latestApp->scholarship_type }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Course:</span>
                        <span class="detail-value">{{ $latestApp->course }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value status-badge status-{{ $latestApp->application_status }}">{{ ucfirst($latestApp->application_status) }}</span>
                    </div>
                    @if($latestApp->application_status === 'rejected' && $latestApp->rejection_reason)
                    <div class="detail-row">
                        <span class="detail-label">Reason for Decision:</span>
                        <span class="detail-value">{{ $latestApp->rejection_reason }}</span>
                    </div>
                    @endif
                </div>
            @else
                <div class="no-applications">
                    <div class="empty-state">
                        <p class="empty-message">No applications yet</p>
                        <p class="empty-submessage">Submit your first scholarship application to track its progress</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endif

</div>
    @php $description = 'Select a scholarship to view its full description.'; @endphp
    <!-- Scholarship Details Modal -->
    <div id="scholarshipDetailsModal" class="apply-modal scholarship-details-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-topbar">
                <div></div>
                <button class="modal-close" id="scholarshipDetailsModalClose" type="button" aria-label="Close scholarship details">✕</button>
            </div>

            <div class="modal-body">
                <div class="details-card">
                    <div class="details-header">
                        <div class="detail-hero-content">
                            <h3 id="detailsScholarshipTitle">Scholarship Title</h3>
                            <p id="detailsScholarshipSubtitle" class="detail-subtitle">Program details</p>
                            <div id="detailsScholarshipTags" class="detail-tags"></div>
                        </div>
                        <div class="detail-logo">
                            <img id="detailsScholarshipImage" src="" alt="Scholarship logo">
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4><b>About this Scholarship</b></h4>
                        <p id="detailsScholarshipDescription" class="details-description">{!! $description !!}</p>
                    </div>

                    <div class="detail-section">
                        <h4><b>Eligibility Requirements</b></h4>
                        <ul id="detailsScholarshipRequirements" class="detail-requirements"></ul>
                    </div>
                </div>
            </div>

            <div class="details-actions">
                <button type="button" class="btn btn-primary" id="detailsApplyBtn">APPLY NOW</button>
            </div>
        </div>
    </div>

    <!-- Apply Modal -->
    <div id="applyModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <button class="modal-close" id="applyModalClose">✕</button>
            <h1 class="form-title">Scholarship Application</h1>
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'SUCCESS',
                            text: @json(session('success')),
                            confirmButtonColor: '#16a34a'
                        });
                    });
                </script>
            @endif
            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Upload Error',
                            text: @json(session('error')),
                            confirmButtonColor: '#dc2626'
                        });
                    });
                </script>
            @endif
            
                @php
                $currentUser = auth()->user();
                $fullName = trim($currentUser->name ?? '');
                $nameParts = preg_split('/\s+/', $fullName);
                $firstNameDefault = old('first_name', $nameParts[0] ?? '');
                $middleNameDefault = old('middle_name', count($nameParts) > 2 ? $nameParts[1] : '');
                $lastNameDefault = old('last_name', count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : '');
                $emailDefault = old('email', $currentUser->email ?? '');
                $studentIdDefault = old('student_id_number', $currentUser->student_id ?? '');
            @endphp
            <form id="applyForm" method="POST" action="{{ route('student-profiles.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="apply-row">
                    <div>
                        <label>First name</label>
                        <input class="input" type="text" name="first_name" value="{{ $firstNameDefault }}" readonly required>
                    </div>
                    <div>
                        <label>Middle name</label>
                        <input class="input" type="text" name="middle_name" value="{{ $middleNameDefault }}" readonly>
                    </div>
                    <div>
                        <label>Last name</label>
                        <input class="input" type="text" name="last_name" value="{{ $lastNameDefault }}" readonly required>
                    </div>
                    <div>
                        <label>Sex</label>
                        <select name="sex" class="input" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" class="input" required>
                            <option value="">Select</option>
                            <option>Single</option>
                            <option>Married</option>
                            <option>In a Relationship</option>
                            <option>Divorced</option>
                        </select>
                    </div>
                    <div>
                        <label>Email address</label>
                        <input class="input" type="email" name="email" value="{{ $emailDefault }}" readonly required>
                    </div>
                    <div>
                        <label>Student ID Number</label>
                        <input class="input" type="text" name="student_id_number" value="{{ $studentIdDefault }}" readonly>
                    </div>
                    <div class="full">
                        <label>Home address</label>
                        <textarea name="home_address" class="input" rows="2"></textarea>
                    </div>
                    <div>
                        <label>Contact number</label>
                        <input class="input" type="text" name="contact_number">
                    </div>
                    <div>
                        <label>Course</label>
                        <select name="course" class="input" required>
                            <option value="">Select course</option>
                            <option>BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN CRIMINOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN FISHERIES</option>
                            <option>BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT</option>
                            <option>BACHELOR OF SECONDARY EDUCATION</option>
                            <option>BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM</option>
                        </select>
                    </div>
                    <div>
                        <label>Year Level</label>
                        <select name="year_level" class="input" required>
                            <option value="">Select year level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <label>Name of scholarship</label>
                        <select id="scholarship_name" name="scholarship_name" class="input" required>
                            <option value="">Select scholarship</option>
                            <option value="Athletic Scholarship">Athletic Scholarship</option>
                            <option value="Employees' Grant">Employees' Grant</option>
                            <option value="Socio-Cultural Scholarship">Socio-Cultural Scholarship</option>
                            <option value="Campus Student Publication">Campus Student Publication</option>
                            <option value="Differently Abled Persons">Differently Abled Persons</option>
                            <option value="Student Council Federation Grant">Student Council Federation Grant</option>
                            <option value="CSU Aparri Alumni Association">CSU Aparri Alumni Association</option>
                            <option value="Tertiary Education Subsidy">Tertiary Education Subsidy</option>
                            <option value="Agkaykaysa">Agkaykaysa</option>
                            <option value="Tulong Dunong Program">Tulong Dunong Program</option>
                            <option value="Open Heart Scholarship">Open Heart Scholarship</option>
                            <option value="Talamayan Scholarship">Talamayan Scholarship</option>
                        </select>
                    </div>
                    <div class="full">
                        <label>Types of scholarship</label>
                        <select name="scholarship_type" class="input" required>
                            <option value="">Select type</option>
                            <option value="Sports Excellence Program In-House">Sports Excellence Program In-House</option>
                            <option value="Employees' Grant In-House">Employees' Grant In-House</option>
                            <option value="Arts & Culture Program In-House">Arts & Culture Program In-House</option>
                            <option value="University Based Scholarship In-House">University Based Scholarship In-House</option>
                            <option value="Alumni Grant Program In-House">Alumni Grant Program In-House</option>
                            <option value="UniFAST – TES Program Government Based">UniFAST – TES Program Government Based</option>
                            <option value="Regional Solidarity Grant Government Based">Regional Solidarity Grant Government Based</option>
                            <option value="Sen. Loren Legarda Scholarship Government Based">Sen. Loren Legarda Scholarship Government Based</option>
                            <option value="Private Foundation Grant Private Based">Private Foundation Grant Private Based</option>
                            <option value="Community Support Grant Private Based">Community Support Grant Private Based</option>
                        </select>
                    </div>
                    <div class="full">
                        <label>Upload files</label>
                        <div style="background:#f9f9f9; padding:12px; border-radius:8px; margin-bottom:8px; border:1px solid #e5e5e5; font-size:0.85rem;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#333;">Supported file types & sizes:</p>
                            <ul style="margin:0; padding-left:20px; color:#666;">
                                <li><strong>Documents:</strong> PDF, DOCX (max 2MB)</li>
                                <li><strong>Images:</strong> JPG, PNG (max 1MB)</li>
                            </ul>
                        </div>

                        <!-- ID label to show for specific non-academic scholarships -->
                        <div id="nonAcademicIDLabel" style="display:none; font-weight:600; margin-bottom:8px; color:#333;">Front and Back School ID docs or PDF Format</div>

                        <div class="tes-docs-label" style="display:none; margin-bottom:10px; padding:12px; border-radius:8px; background:#f9f9f9; border:1px solid #e5e5e5;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#333; font-size:0.9rem;">Required Documents (PDF Format)</p>
                            <ul style="margin:0; padding-left:18px; color:#666; font-size:0.85rem; line-height:1.5;">
                                <li>COE of the Present Semester</li>
                                <li>School ID (Front and Back)</li>
                                <li>ATM Proof of Account Number</li>
                                <li>Three (3) Signatures</li>
                                <li>Contact Number</li>
                            </ul>
                        </div>

                        <style>
                            #applyForm:has(#scholarship_name option[value="Tertiary Education Subsidy"]:checked) .tes-docs-label {
                                display: block !important;
                            }

                            #applyForm:has(#scholarship_name option[value="Differently Abled Persons"]:checked) #nonAcademicIDLabel,
                            #applyForm:has(#scholarship_name option[value="Open Heart Scholarship"]:checked) #nonAcademicIDLabel,
                            #applyForm:has(#scholarship_name option[value="Talamayan Scholarship"]:checked) #nonAcademicIDLabel {
                                display: block !important;
                            }

                            #applyForm:has(#scholarship_name option[value="Athletic Scholarship"]:checked) .athletic-only-fields {
                                display: flex !important;
                            }

                            #applyForm:has(#scholarship_name option[value="Campus Student Publication"]:checked) .campus-publication-label {
                                display: block !important;
                            }

                            #applyForm:has(#scholarship_name option[value="CSU Aparri Alumni Association"]:checked) .alumni-label {
                                display: block !important;
                            }
                        </style>

                        <div class="athletic-only-fields" style="display:none; gap:16px; flex-wrap:wrap; align-items:flex-start; margin-top:10px;">
                            <div style="flex:1 1 320px; min-width:260px; max-width:420px;">
                                <label style="display:block; margin-bottom:8px;">Certificate from the Sports Coordinator</label>
                                <input type="file" name="uploads[]" multiple accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" style="width:115px; overflow:hidden;">
                            </div>
                            <div style="flex:1 1 320px; min-width:260px; max-width:420px;">
                                <label style="display:block; margin-bottom:8px;">Certificate of Adviser</label>
                                <input type="file" name="certificate_of_adviser" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" style="width:115px; overflow:hidden;">
                            </div>
                        </div>

                        <div class="campus-publication-label" style="display:none; font-weight:600; margin-top:10px; margin-bottom:8px; color:#333;">
                            Certificate from the Adviser
                        </div>

                        <div class="alumni-label" style="display:none; font-weight:600; margin-top:10px; margin-bottom:8px; color:#333;">
                            Certificate of Enrollment
                        </div>

                        <input type="file" name="uploads[]" multiple id="nonAcademicUploadsInput" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit Application</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Academic Grants Application Modal -->
    <div id="academicApplyModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <button class="modal-close" id="academicApplyModalClose">✕</button>
            <h1 class="form-title">Academic Scholarship Application</h1>
            
            @php
                $academicEmailDefault = old('email', $currentUser->email ?? '');
                $academicFirstNameDefault = old('first_name', $firstNameDefault);
                $academicMiddleNameDefault = old('middle_name', $middleNameDefault);
                $academicLastNameDefault = old('last_name', $lastNameDefault);
                $academicStudentIdDefault = old('id_number', $currentUser->student_id ?? '');
            @endphp
            <form id="academicApplyForm" method="POST" action="{{ route('student-profiles.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="apply-row">
                    <div>
                        <label>Name of Scholarship/Name of Sponsor</label>
                        <select id="academic_scholarship_name" name="scholarship_name" class="input" required>
                            <option value="">Select scholarship</option>
                        </select>
                    </div>
                    <div>
                        <label>Last name</label>
                        <input class="input" type="text" name="last_name" value="{{ $academicLastNameDefault }}" readonly required>
                    </div>
                    <div>
                        <label>First name</label>
                        <input class="input" type="text" name="first_name" value="{{ $academicFirstNameDefault }}" readonly required>
                    </div>
                    <div>
                        <label>Middle name</label>
                        <input class="input" type="text" name="middle_name" value="{{ $academicMiddleNameDefault }}" readonly>
                    </div>
                    <div>
                        <label>Email</label>
                        <input class="input" type="email" name="email" value="{{ $academicEmailDefault }}" readonly>
                    </div>
                    <div>
                        <label>Student ID Number</label>
                        <input class="input" type="text" name="id_number" value="{{ $academicStudentIdDefault }}" readonly required>
                    </div>
                    <div>
                        <label>Course</label>
                        <select name="course" class="input" required>
                            <option value="">Select course</option>
                            <option>BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN CRIMINOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN FISHERIES</option>
                            <option>BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT</option>
                            <option>BACHELOR OF SECONDARY EDUCATION</option>
                            <option>BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM</option>
                        </select>
                    </div>
                    <div>
                        <label>Year Level</label>
                        <select name="year_level" class="input" required>
                            <option value="">Select year level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <label>Sex</label>
                        <select name="sex" class="input" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label>Program</label>
                        <input class="input" type="text" name="program" required>
                    </div>
                    <div>
                        <label>ID Number</label>
                        <input class="input" type="text" name="id_number" required>
                    </div>
                    <div>
                        <label>Birthdate</label>
                        <input class="input" type="date" name="birthdate" required>
                    </div>
                    <div>
                        <label>Birthplace</label>
                        <input class="input" type="text" name="birthplace" required>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" class="input" required>
                            <option value="">Select</option>
                            <option>Single</option>
                            <option>Married</option>
                            <option>In a Relationship</option>
                            <option>Widowed</option>
                        </select>
                    </div>
                    <div>
                        <label>Religion</label>
                        <input class="input" type="text" name="religion" required>
                    </div>
                    <div>
                        <label>Contact number</label>
                        <input class="input" type="text" name="contact_number" required>
                    </div>
                    <div class="full">
                        <label>Home Address</label>
                        <textarea name="home_address" class="input" rows="2" required></textarea>
                    </div>
                    <div class="full">
                        <label>Types of Scholarship</label>
                        <select id="academic_scholarship_type" name="scholarship_type" class="input" required>
                            <option value="">Select type</option>
                        </select>
                    </div>
                    <div class="full">
                        <label>Upload files</label>
                        <div style="background:#f9f9f9; padding:12px; border-radius:8px; margin-bottom:8px; border:1px solid #e5e5e5; font-size:0.85rem;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#333;">Supported file types & sizes:</p>
                            <ul style="margin:0; padding-left:20px; color:#666;">
                                <li><strong>Documents:</strong> PDF, DOCX (max 2MB)</li>
                                <li><strong>Images:</strong> JPG, PNG (max 1MB)</li>
                            </ul>
                        </div>
                        <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-start;">
                            <div style="flex:1 1 320px; min-width:260px; max-width:420px;">
                                <label style="display:block; margin-bottom:8px;">Certificate of Grades</label>
                                <input type="file" id="academicFileInput" name="uploads[]" multiple accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" style="width:115px; overflow:hidden;">
                                <div id="academicFileNames" style="margin-top:8px; font-size:13px; color:#666;"></div>
                            </div>
                            <div id="academicEnrollmentSection" style="flex:1 1 320px; min-width:260px; max-width:420px; display:none;">
                                <label style="display:block; margin-bottom:8px;">Certificate of Enrollment</label>
                                <input type="file" id="academicEnrollmentFileInput" name="certificate_of_enrollment" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" style="width:115px; overflow:hidden;">
                                <div id="academicEnrollmentFileNames" style="margin-top:8px; font-size:13px; color:#666;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="academicSubmitBtn">Submit Application</button>
                </div>
            </form>

        </div>
    </div>

  <script src="{{ asset('js/users.js') }}"></script>

  <!-- File Validation Script -->
  <script>
    // File validation configuration
    const fileValidationRules = {
      document: {
        extensions: ['pdf', 'docx', 'doc'],
        maxSize: 2 * 1024 * 1024, // 2MB in bytes
        displaySize: '2MB'
      },
      image: {
        extensions: ['jpg', 'jpeg', 'png'],
        maxSize: 1 * 1024 * 1024, // 1MB in bytes
        displaySize: '1MB'
      }
    };

    // Get file category
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

    function getAllFileInputs(form) {
      return Array.from(form.querySelectorAll('input[type="file"]')).filter(input => input.files && input.files.length > 0);
    }

    function validateFiles(files) {
      const errors = [];
      const successFiles = [];

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

      return { errors, successFiles };
    }

    // Validate files on form submission
    document.getElementById('applyForm').addEventListener('submit', function(e) {
      // Allow submission if flag is set
      if (allowSubmit) {
        allowSubmit = false;
        return true;
      }

      const fileInputs = getAllFileInputs(this);
      const files = fileInputs.reduce((list, input) => list.concat(Array.from(input.files)), []);

      if (files.length === 0) {
        // No files selected - allow form submission
        allowSubmit = true;
        return true;
      }

      e.preventDefault();

      const { errors, successFiles } = validateFiles(files);

      // Validate each file
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileName = file.name;
        const fileSize = file.size;
        const fileExt = fileName.split('.').pop();
        const category = getFileCategory(fileExt);

        // Check file type
        if (!category) {
          errors.push(`"${fileName}" - Unsupported format. Allowed: PDF, DOCX, JPG, PNG`);
          continue;
        }

        // Check file size
        const maxSize = fileValidationRules[category].maxSize;
        const displaySize = fileValidationRules[category].displaySize;
        const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);

        if (fileSize > maxSize) {
          errors.push(`"${fileName}" - Size: ${fileSizeMB}MB exceeds limit of ${displaySize}`);
        } else {
          successFiles.push(`"${fileName}" - ${fileSizeMB}MB (${category})`);
        }
      }

      // If there are errors, show error popup
      if (errors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Upload Error',
                    text: errors.join('\n'),
                    confirmButtonColor: '#dc2626'
                });
        return false;
      }

      // If all files are valid, show success popup
      if (successFiles.length > 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Files Validated Successfully',
                    text: 'All files validated successfully and are ready to upload.\n' + successFiles.join('\n'),
                    showCancelButton: true,
                    confirmButtonText: 'Submit Application',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        allowSubmit = true;
                        submitForm();
                    }
                });
        return false;
      }

      return true;
    });
     //error message
    document.getElementById('academicApplyForm').addEventListener('submit', function(e) {
      if (allowSubmit) {
        allowSubmit = false;
        return true;
      }

      const fileInputs = getAllFileInputs(this);
      const files = fileInputs.reduce((list, input) => list.concat(Array.from(input.files)), []);

      if (files.length === 0) {
        allowSubmit = true;
        return true;
      }

      e.preventDefault();

      const { errors, successFiles } = validateFiles(files);

      if (errors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Upload Error',
                    text: errors.join('\n'),
                    confirmButtonColor: '#dc2626'
                });
        return false;
      }

      if (successFiles.length > 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Files Validated Successfully',
                    text: 'All files validated successfully and are ready to upload.\n' + successFiles.join('\n'),
                    showCancelButton: true,
                    confirmButtonText: 'Submit Application',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        allowSubmit = true;
                        submitAcademicForm();
                    }
                });
        return false;
      }

      return true;
    });

    // Show file count when files are selected
    document.querySelector('input[name="uploads[]"]').addEventListener('change', function(e) {
      const fileCount = this.files.length;
      if (fileCount > 0) {
        let totalSize = 0;
        for (let i = 0; i < this.files.length; i++) {
          totalSize += this.files[i].size;
        }
        const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);
        console.log(`Selected ${fileCount} file(s), Total size: ${totalSizeMB}MB`);
      }
    });

    // Display file names for academic grants
    const academicFileInput = document.getElementById('academicFileInput');
    if (academicFileInput) {
      academicFileInput.addEventListener('change', function(e) {
        const fileNames = document.getElementById('academicFileNames');
        if (this.files.length > 0) {
          let fileList = '';
          for (let i = 0; i < this.files.length; i++) {
            fileList += `<div> ${this.files[i].name}</div>`;
          }
          fileNames.innerHTML = fileList;
        } else {
          fileNames.innerHTML = '';
        }
      });
    }

    const academicEnrollmentFileInput = document.getElementById('academicEnrollmentFileInput');
    if (academicEnrollmentFileInput) {
      academicEnrollmentFileInput.addEventListener('change', function(e) {
        const fileNames = document.getElementById('academicEnrollmentFileNames');
        if (this.files.length > 0) {
          let fileList = '';
          for (let i = 0; i < this.files.length; i++) {
            fileList += `<div> ${this.files[i].name}</div>`;
          }
          fileNames.innerHTML = fileList;
        } else {
          fileNames.innerHTML = '';
        }
      });
    }
  </script>

  <!-- Error Popup Styles -->
  <style>
   
  </style>

  <script>
    let allowSubmit = false;

        function submitForm() {
            allowSubmit = true;
            document.getElementById('applyForm').submit();
        }

    // Close popup when clicking outside

    (function () {
      const section = @json($section ?? 'acad');
      const appliedScholarships = @json($appliedScholarships ?? []);
      const activeAcademicApplications = @json($activeAcademicApplications ?? 0);
      const activeNonAcademicApplications = @json($activeNonAcademicApplications ?? 0);
      const maxApplications = 2;
      const currentActiveApplications = section === 'non' ? activeNonAcademicApplications : activeAcademicApplications;

      const cards = document.querySelectorAll('.s-card');

      cards.forEach(card => {
        const titleEl = card.querySelector('h3');
        const scholarshipTitle = titleEl ? titleEl.innerText.trim() : null;

        if (!scholarshipTitle) {
          return;
        }

                const application = appliedScholarships.find(item => item.name === scholarshipTitle);
                const isApplied = Boolean(application && application.status !== 'rejected');
                const cardFoot = card.querySelector('.card-foot');

                if (application && application.status === 'rejected' && currentActiveApplications < maxApplications) {
                    card.classList.add('card-rejected');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'applied-text';
                        message.innerHTML = '<div class="applied-label">REJECTED</div><div>Rejected. See Application Status for details.</div>';
                        cardFoot.prepend(message);
                    }
                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = 'APPLY NOW';
                        applyButton.removeAttribute('aria-disabled');
                        applyButton.setAttribute('href', '#');
                        applyButton.classList.remove('btn-applied');
                    }
                    return;
                }

                if (isApplied) {
                    card.classList.add('card-applied');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'applied-text';
                        const isApproved = application.status === 'approved';
                        message.innerHTML = isApproved
                            ? '<div class="applied-label status-approved">APPROVED</div><div>Your application for this scholarship has been approved.</div>'
                            : '<div class="applied-label">PENDING</div><div class="Applied-text">Your application for this scholarship is being processed.</div>';
                        cardFoot.prepend(message);
                    }

                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = 'APPLIED';
                        applyButton.setAttribute('aria-disabled', 'true');
                        applyButton.setAttribute('href', 'javascript:void(0);');
                        applyButton.classList.add('btn-applied');
                    }
                    return;
                }

        if (currentActiveApplications >= maxApplications) {
          card.classList.add('card-unavailable');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'unavailable-text';
                        message.textContent = 'You already have two active applications in this category, so this scholarship is not available.';
                        cardFoot.prepend(message);
                    }

          const applyButton = card.querySelector('.btn-apply');
          if (applyButton) {
            applyButton.textContent = 'UNAVAILABLE';
            applyButton.setAttribute('aria-disabled', 'true');
            applyButton.setAttribute('href', 'javascript:void(0);');
          }
        }
      });
    })();
  </script>
</body>
</html>