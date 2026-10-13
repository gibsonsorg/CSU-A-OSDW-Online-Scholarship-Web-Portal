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
        .slot-count {
            display: block;
            margin-top: 4px;
            color: #59636e;
            font-size: 0.78rem;
            line-height: 1.25;
        }
        .availability-modal-message {
            margin: 0;
            color: #7a2525;
            font-size: 0.9rem;
            text-align: center;
        }
        #detailsApplyBtn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .Applied-text
        {
              color: #a81f1f;
        }

        .details-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .details-actions .btn-primary {
            min-width: 120px;
            padding: 0.9rem 2.25rem;
            border: none;
            border-radius: 999px;
            background: linear-gradient(180deg, #87323d 0%, #5d0f1d 100%);
            color: #fff;
            font-size: 1.06rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            box-shadow: 0 10px 22px rgba(124, 18, 31, 0.22);
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        .details-actions .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(124, 18, 31, 0.28);
            filter: brightness(1.02);
        }
    </style>
</head>

<body>

<x-app-layout>
    <div class="main student-dashboard-main">

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
        @foreach($dynamicScholarships ?? [] as $scholarship)
            <div class="s-card {{ $scholarship->status === 'inactive' ? 'card-unavailable' : '' }}" data-name="{{ strtolower($scholarship->name . ' ' . ($scholarship->type_label ?? '') . ' ' . ($scholarship->funding_type ?? '') . ' ' . ($scholarship->scholarship_classification ?? '')) }}" data-description="{{ $scholarship->description }}@if($scholarship->requirements) Eligibility Requirements @foreach($scholarship->requirements as $requirement) *{{ $requirement }} @endforeach @endif" data-image="{{ $scholarship->logo_path ? route('scholarships.logo', ['scholarship' => $scholarship, 'v' => $scholarship->updated_at?->timestamp], false) : '' }}" data-scholarship-id="{{ $scholarship->id }}" data-scholarship-type="{{ $scholarship->type_label }}" data-required-documents='@json($scholarship->required_documents ?? [])'>
                <div class="card-head">
                    <div class="card-icon"><img src="{{ $scholarship->logo_path ? route('scholarships.logo', ['scholarship' => $scholarship, 'v' => $scholarship->updated_at?->timestamp], false) : asset('images/csu.logo.png') }}" alt="{{ $scholarship->name }} logo" onerror="this.onerror=null;this.src='{{ asset('images/csu.logo.png') }}'"></div>
                    <div class="card-names">
                        <h3>{{ $scholarship->name }}</h3>
                        <p class="prog">{{ $scholarship->type_label ?: 'Scholarship' }}</p>
                        <span class="type-tag tag-intl">{{ $scholarship->funding_type ?: 'Not specified' }}</span>
                    </div>
                </div>
                <div class="card-meta-row"><span class="meta-chip"><span class="dot"></span>{{ $scholarship->scholarship_classification ?: 'Not specified' }}</span></div>
                <p class="card-desc">{{ $scholarship->short_information }}</p>
                <div class="card-foot">
                    @if($scholarship->status === 'inactive')
                        <div class="unavailable-text">UNAVAILABLE</div>
                        <a href="#" class="btn-apply" aria-disabled="true" tabindex="-1">UNAVAILABLE</a>
                    @else
                        <a href="#" class="btn-apply">VIEW &amp; APPLY</a>
                    @endif
                </div>
            </div>
        @endforeach
        <div class="s-card" data-name="cagayan valley association hawaii internationally funded" data-scholarship-id="{{ $academicScholarshipIdMap['Cagayan Valley Assoc. of Hawaii'] ?? '' }}" data-scholarship-type="International Educational Grant" data-grant-type="academic" data-description=" The Cagayan Valley Association of Hawaii (CVAH) Internationally Funded Scholarship is an academic grant program established through a partnership with Cagayan State University (CSU). It provides financial assistance to deserving students in Cagayan Valley, Philippines.

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

        <div class="s-card" data-name="university based scholarship academic inhouse" data-scholarship-id="{{ $academicScholarshipIdMap['University Based Scholarship'] ?? '' }}" data-scholarship-type="Academic Excellence – In-House" data-grant-type="academic" data-description="The University Based Scholarship recognizes students with outstanding academic performance throughout the school year and provides financial assistance to help them continue their studies.
    Eligibility Requirements
    * Must maintain a General Weighted Average (GWA) of at least 94% in every semester to qualify for  for this scholarship.">
            
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

        <div class="s-card" data-name="acef giahep government locally funded" data-scholarship-id="{{ $academicScholarshipIdMap['ACEF – GIAHEP'] ?? '' }}" data-scholarship-type="Agricultural Credit & Education Fund" data-grant-type="academic" data-description="The  ACEF-GIAHEP (Agricultural Competitiveness Enhancement Fund Grants-in-Aid for Higher Education Program) is a joint financial assistance program by the Commission on Higher Education and the Department of Agriculture designed to support undergraduate students pursuing degrees in agriculture, forestry, fisheries, and veterinary medicine
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

        <div class="s-card" data-name="dost sei project strand graduate school government" data-scholarship-id="{{ $academicScholarshipIdMap['DOST SEI Project Strand'] ?? '' }}" data-description="TThe DOST scholarship is a government-funded educational financial aid program in the Philippines managed by the Science Education Institute - DOST to support students pursuing science, technology, engineering, and mathematics (STEM) careers.
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

        <div class="s-card" data-name="tulong dunong kuya win scholarship government" data-scholarship-id="{{ $academicScholarshipIdMap['Tulong Dunong Program'] ?? '' }}" data-description="The Kuya Win Scholarship Program (often linked with legislative or targeted student assistance initiatives spearheaded by Senator Win Gatchalian and aligned with government efforts like the UniFAST / CHED Tulong Dunong Program) is an academic support and financial assistance initiative. It provides monetary aid to help deserving, low-income Filipino college students pay for tuition, school fees, and daily living expenses.
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

        <div class="s-card" data-name="lgu aparri local government unit scholarship" data-scholarship-id="{{ $academicScholarshipIdMap['LGU Aparri'] ?? '' }}" data-description="The LGU Aparri scholarship is a local government assistance program for qualified students from Aparri who are pursuing higher education and need financial support.">
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
        <div class="s-card" data-name="natalged lasam educational assistance government" data-scholarship-id="{{ $academicScholarshipIdMap['Natalged A Lasam'] ?? '' }}" data-description="The Natalged a Lasam Educational Assistance (NALEA) program is a municipal financial aid initiative in Lasam, Cagayan, providing cash stipends, semester allowances, and graduate recognition to support deserving tertiary and high school students
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

        <div class="s-card" data-name="andres tamayo scholarship private foundation" data-scholarship-id="{{ $academicScholarshipIdMap['Andres Tamayo Scholarship'] ?? '' }}" data-description=" Is a registered private, non-governmental organization in the Philippines that provides the Handog Edukasyon Scholarship Program to support financially challenged youth. The grant covers educational costs for qualified college and vocational students to alleviate poverty through long-term educational access.
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

        <div class="s-card" data-name="gokongwei brothers foundation scholarship private" data-scholarship-id="{{ $academicScholarshipIdMap['Gokongwei Brothers Foundation'] ?? '' }}" data-description="(GBF) is the strategic philanthropic arm of the Gokongwei Group, established in 1992 by brothers John, Henry, Johnson, and James. It advances STEM education across the Philippines through scholarships, teacher professional development, and technical-vocational training.
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
        @foreach($dynamicScholarships ?? [] as $scholarship)
            <div class="s-card {{ $scholarship->status === 'inactive' ? 'card-unavailable' : '' }}" data-name="{{ strtolower($scholarship->name . ' ' . ($scholarship->type_label ?? '') . ' ' . ($scholarship->funding_type ?? '') . ' ' . ($scholarship->scholarship_classification ?? '')) }}" data-description="{{ $scholarship->description }}@if($scholarship->requirements) Eligibility Requirements @foreach($scholarship->requirements as $requirement) *{{ $requirement }} @endforeach @endif" data-image="{{ $scholarship->logo_path ? route('scholarships.logo', ['scholarship' => $scholarship, 'v' => $scholarship->updated_at?->timestamp], false) : '' }}" data-scholarship-id="{{ $scholarship->id }}" data-scholarship-type="{{ $scholarship->type_label }}" data-required-documents='@json($scholarship->required_documents ?? [])'>
                <div class="card-head">
                    <div class="card-icon"><img src="{{ $scholarship->logo_path ? route('scholarships.logo', ['scholarship' => $scholarship, 'v' => $scholarship->updated_at?->timestamp], false) : asset('images/csu.logo.png') }}" alt="{{ $scholarship->name }} logo" onerror="this.onerror=null;this.src='{{ asset('images/csu.logo.png') }}'"></div>
                    <div class="card-names">
                        <h3>{{ $scholarship->name }}</h3>
                        <p class="prog">{{ $scholarship->type_label ?: 'Scholarship' }}</p>
                        <span class="type-tag tag-intl">{{ $scholarship->funding_type ?: 'Not specified' }}</span>
                    </div>
                </div>
                <div class="card-meta-row"><span class="meta-chip"><span class="dot"></span>{{ $scholarship->scholarship_classification ?: 'Not specified' }}</span></div>
                <p class="card-desc">{{ $scholarship->short_information }}</p>
                <div class="card-foot">
                    @if($scholarship->status === 'inactive')
                        <div class="unavailable-text">UNAVAILABLE</div>
                        <a href="#" class="btn-apply" aria-disabled="true" tabindex="-1">UNAVAILABLE</a>
                    @else
                        <a href="#" class="btn-apply">VIEW &amp; APPLY</a>
                    @endif
                </div>
            </div>
        @endforeach
        <div class="s-card" data-name="tertiary education subsidy tes unifast government locally funded" data-description="The Tertiary Education Subsidy offers financial aid to qualified students under the UniFAST program to help cover school-related expenses each academic year.


More info: https://unifaast.gov.ph/tes.html

Eligibility Requirements
Students from 4Ps beneficiary households
Children of solo parents or student applicants who are solo parents
Students from municipalities without accessible colleges or higher education institutions"
     
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
                <a href="#" class="btn-apply">VIEW &amp; APPLY</a>
            </div>
        </div>
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

    </div>

    <!-- Non-Academic Page 2 -->
    <div class="card-grid page-set" id="non-p2">
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
                <a href="#" class="btn-apply">VIEW &amp; APPLY</a>
            </div>
        </div>
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
                <div class="card-icon">
                    <img src="{{ asset('images/talamayan.png') }}" alt="open heart scholarship logo">
                </div>
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
                $userApplications = \App\Models\StudentProfile::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->get();
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
                    @if($latestApp->rejection_remark)
                    <div class="detail-row">
                        <span class="detail-label">What to fix:</span>
                        <span class="detail-value">{{ $latestApp->rejection_remark }}</span>
                    </div>
                    @endif
                    @if($latestApp->resubmission_deadline && $latestApp->resubmission_deadline->isFuture())
                    <div class="detail-row">
                        <span class="detail-label">Resubmission deadline:</span>
                        <span class="detail-value">{{ $latestApp->resubmission_deadline->format('M j, Y g:i A') }}</span>
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
                            <img id="detailsScholarshipImage" src="" data-fallback-src="{{ asset('images/csu.logo.png') }}" alt="Scholarship logo">
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

                    <div class="detail-section">
                        <h4><b>Required Documents</b></h4>
                        <ul id="detailsScholarshipDocuments" class="detail-requirements"></ul>
                    </div>
                </div>
            </div>

            <div class="details-actions">
                <p id="detailsAvailabilityMessage" class="availability-modal-message" role="status" hidden></p>
                <button type="button" class="btn btn-primary" id="detailsApplyBtn">APPLY NOW</button>
            </div>
        </div>
    </div>

    <!-- Apply Modal -->
    <div id="applyModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <div class="application-modal-header">
                <button type="button" class="academic-nav-btn academic-nav-back" data-non-academic-step="prev" aria-label="Back to previous step">← Back</button>
                <h1 class="form-title">Non-Academic Scholarship Application</h1>
                <button class="modal-close" id="applyModalClose" type="button" aria-label="Close scholarship application">✕</button>
            </div>
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: @json(session('success')),
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
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
            @if($errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Application could not be submitted',
                            text: @json($errors->first()),
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

            <div class="academic-progress-wrapper">
                <div class="academic-progress-header">Step <span id="nonAcademicStepCounter">1</span> of <span data-step-total>4</span> <span id="nonAcademicStepLabel" hidden></span></div>
                <div class="non-academic-mobile-progress" aria-hidden="true" hidden><span></span></div>
                <div class="academic-progress-track" aria-live="polite">
                    <div class="academic-progress-step active" data-progress-step="1">
                        <div class="academic-progress-dot">1</div>
                        <div class="academic-progress-label">Student Information</div>
                    </div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-progress-step="2">
                        <div class="academic-progress-dot">2</div>
                        <div class="academic-progress-label">Personal Information</div>
                    </div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-progress-step="3">
                        <div class="academic-progress-dot">3</div>
                        <div class="academic-progress-label">Family Background</div>
                    </div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-progress-step="4">
                        <div class="academic-progress-dot">4</div>
                        <div class="academic-progress-label">Scholarship &amp; Requirements</div>
                    </div>
                </div>
            </div>

            <form id="applyForm" method="POST" action="{{ route('student-profiles.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="application_id" value="">
                <input type="hidden" name="student_profile_id" value="">

                <div class="apply-row academic-step-panel active" data-non-academic-step="1">
                    <div>
                        <label>First name</label>
                        <input class="input" type="text" name="first_name" value="{{ $firstNameDefault }}" required>
                    </div>
                    <div>
                        <label>Middle name</label>
                        <input class="input" type="text" name="middle_name" value="{{ $middleNameDefault }}">
                    </div>
                    <div>
                        <label>Last name</label>
                        <input class="input" type="text" name="last_name" value="{{ $lastNameDefault }}" required>
                    </div>
                    <div>
                        <label>Suffix</label>
                        <input class="input" type="text" name="suffix" value="{{ old('suffix') }}">
                    </div>
                    <div>
                        <label>Email address</label>
                        <input class="input email-readonly" type="email" name="email" value="{{ $emailDefault }}" readonly aria-readonly="true" title="Verified account email" required>
                    </div>
                    <div>
                        <label>Student ID Number</label>
                        <input class="input" type="text" name="student_id_number" value="{{ $studentIdDefault }}" inputmode="numeric" maxlength="8" pattern="\d{2}-\d{5}" autocomplete="off" required>
                    </div>
                    <div>
                        <label>Course</label>
                        <select name="course" class="input" required>
                            <option value="">Select course</option>
                            <option value="BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics">BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics</option>
                            <option value="BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive">BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive</option>
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
                        <label>College or Department</label>
                        <input class="input" type="text" name="college_department" value="" readonly aria-readonly="true">
                    </div>
                </div>

                <div class="apply-row academic-step-panel" data-non-academic-step="2">
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
                        </select>
                    </div>
                    <div>
                        <label>Contact number</label>
                        <div class="contact-number-control">
                            <span class="contact-number-prefix" aria-hidden="true">+63 9</span>
                            <input class="input" type="tel" name="contact_number" inputmode="numeric" maxlength="9" pattern="\d{9}" autocomplete="tel-national" required>
                        </div>
                    </div>
                    <div>
                        <label>Birthday</label>
                        <input class="input" type="date" name="birthdate" max="2010-12-31" required>
                    </div>
                    <div>
                        <label>Birthplace</label>
                        <input class="input" type="text" name="birthplace" required>
                    </div>
                    <div>
                        <label>Age</label>
                        <input class="input" type="number" name="age" readonly aria-readonly="true" tabindex="-1">
                    </div>
                    <div class="full">
                        <label>Home address</label>
                        <textarea name="home_address" class="input" rows="2" required></textarea>
                    </div>
                </div>

                <div class="family-background-step academic-step-panel" data-non-academic-step="3">
                    <div class="family-background-subpage active" data-family-background-page="1">
                        <fieldset class="family-background-parent">
                            <legend>MOTHER</legend>
                            <div class="family-background-fields">
                                <div class="family-background-status">
                                    <span class="family-background-label">Living Status</span>
                                    <label><input type="radio" name="family_background[mother_status]" value="Living"> Living</label>
                                    <label><input type="radio" name="family_background[mother_status]" value="Deceased"> Deceased</label>
                                </div>
                                <div><label>Full Name</label><input class="input" type="text" name="family_background[mother_name]" placeholder="Last Name, First Name, M.I."></div>
                                <div><label>Birth Date</label><input class="input" type="date" name="family_background[mother_birthdate]"></div>
                                <div><label>Age</label><input class="input" type="number" min="0" name="family_background[mother_age]" placeholder="Age" readonly></div>
                                <div class="family-background-full"><label>Present Address</label><input class="input" type="text" name="family_background[mother_address]" placeholder="Complete address"></div>
                                <div><label>Occupation</label><input class="input" type="text" name="family_background[mother_occupation]" placeholder="Current job/profession"></div>
                                <div><label>Office Address<span class="office-address-desktop-note"> (N/A if not applicable)</span></label><input class="input" type="text" name="family_background[mother_office_address]" placeholder="Company address (if applicable)"><small class="office-address-mobile-note" hidden>(N/A if not applicable)</small></div>
                                <div class="family-background-full"><label>Highest Educational Attainment</label><input class="input" type="text" name="family_background[mother_education]" placeholder="e.g. College Graduate, High School"></div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="family-background-subpage" data-family-background-page="2" hidden>
                        <fieldset class="family-background-parent">
                            <legend>FATHER</legend>
                            <div class="family-background-fields">
                                <div class="family-background-status">
                                    <span class="family-background-label">Living Status</span>
                                    <label><input type="radio" name="family_background[father_status]" value="Living"> Living</label>
                                    <label><input type="radio" name="family_background[father_status]" value="Deceased"> Deceased</label>
                                </div>
                                <div><label>Full Name</label><input class="input" type="text" name="family_background[father_name]" placeholder="Last Name, First Name, M.I."></div>
                                <div><label>Birth Date</label><input class="input" type="date" name="family_background[father_birthdate]"></div>
                                <div><label>Age</label><input class="input" type="number" min="0" name="family_background[father_age]" placeholder="Age" readonly></div>
                                <div class="family-background-full"><label>Present Address</label><input class="input" type="text" name="family_background[father_address]" placeholder="Complete address"></div>
                                <div><label>Occupation</label><input class="input" type="text" name="family_background[father_occupation]" placeholder="Current job/profession"></div>
                                <div><label>Office Address<span class="office-address-desktop-note"> (N/A if not applicable)</span></label><input class="input" type="text" name="family_background[father_office_address]" placeholder="Company address (if applicable)"><small class="office-address-mobile-note" hidden>(N/A if not applicable)</small></div>
                                <div class="family-background-full"><label>Highest Educational Attainment</label><input class="input" type="text" name="family_background[father_education]" placeholder="e.g. College Graduate, High School"></div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="family-background-subpage" data-family-background-page="3" hidden>
                        <div class="family-background-shared">
                            <div><label>Contact Number (Parents/Guardian)</label><input class="input" type="text" name="family_background[parent_contact_number]" placeholder="+63 9XX XXX XXXX"></div>
                            <div><label>Email Address (Parents/Guardian)</label><input class="input" type="email" name="family_background[parent_email]" placeholder="example@email.com"></div>
                            <div><label>Estimated Monthly/Annual Family Income</label><input class="input" type="number" min="0" step="0.01" name="family_background[monthly_income]" placeholder="₱ Amount"></div>
                            <fieldset class="family-background-disability">
                                <legend>Is any family member a Differently Abled Person?</legend>
                                <label><input type="radio" name="family_background[has_differently_abled_member]" value="yes"> Yes</label>
                                <label><input type="radio" name="family_background[has_differently_abled_member]" value="no"> No</label>
                            </fieldset>
                            <div class="family-background-full" id="familyDisabilityDetails" hidden>
                                <label>Please specify Kind of Disability</label>
                                <input class="input" type="text" name="family_background[disability_details]" placeholder="Type here..." disabled>
                            </div>
                        </div>

                        <div class="family-background-siblings">
                            <div class="family-background-siblings-header">
                                <h3>Siblings</h3>
                                <button class="academic-nav-btn" type="button" id="addFamilySibling">+ Add Sibling</button>
                            </div>
                            <div class="family-background-table-wrap">
                                <table class="family-background-siblings-table">
                                    <thead><tr><th>Name</th><th>Age</th><th>Marital Status</th><th>Employment/Occupation</th><th>Remove</th></tr></thead>
                                    <tbody id="familySiblingsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="academic-form-actions family-background-pagination" id="familyBackgroundPagination">
                        <button type="button" class="academic-nav-btn academic-nav-next" id="familyBackgroundNext">Next →</button>
                    </div>
                </div>

                <div class="apply-row academic-step-panel" data-non-academic-step="4">
                    <div>
                        <label>Name of scholarship</label>
                        <input id="scholarship_name" name="scholarship_name" class="input" type="text" value="" required readonly>
                        <input type="hidden" id="scholarship_id" name="scholarship_id" value="">
                    </div>
                    <div>
                        <label>Types of scholarship</label>
                        <input id="scholarship_type" name="scholarship_type" class="input" type="text" value="" required readonly>
                    </div>
                    <div class="full">
                        <!-- CHANGE: Non-academic scholarship-specific upload requirements note. -->
                        <div id="nonAcademicRequirementsNote" style="display:none; margin-bottom:10px; padding:12px; border-radius:8px; background:#f9f9f9; border:1px solid #e5e5e5; color:#666; font-size:0.85rem; line-height:1.5;"></div>
                        <div style="background:#f9f9f9; padding:12px; border-radius:8px; margin-bottom:8px; border:1px solid #e5e5e5; font-size:0.85rem;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#333;">Supported file types & sizes:</p>
                            <ul style="margin:0; padding-left:20px; color:#666;">
                                <li><strong>Documents:</strong> PDF, DOCX (max 2MB)</li>
                            </ul>
                        </div>
                        <div data-reapplication-document-group="supporting_document" id="nonAcademicGenericUploadGroup">
                            <input type="file" name="supporting_document" id="nonAcademicSupportingDocumentInput" accept=".pdf,.docx" required style="width:115px; overflow:hidden;">
                        </div>
                    </div>
                </div>

                <div class="academic-form-actions">
                    <button type="button" class="academic-nav-btn academic-nav-next" data-non-academic-step="next">Next</button>
                    <button type="submit" class="academic-nav-btn academic-nav-submit" data-non-academic-step="submit">Submit Application</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Academic Grants Application Modal -->
    <div id="academicApplyModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <div class="application-modal-header">
                <button type="button" class="academic-nav-btn academic-nav-back" data-academic-step="prev" aria-label="Back to previous step">← Back</button>
                <h1 class="form-title">Academic Scholarship Application</h1>
                <button class="modal-close" id="academicApplyModalClose" type="button" aria-label="Close academic scholarship application">✕</button>
            </div>
            
            @php
                $academicEmailDefault = old('email', $currentUser->email ?? '');
                $academicFirstNameDefault = old('first_name', $firstNameDefault);
                $academicMiddleNameDefault = old('middle_name', $middleNameDefault);
                $academicLastNameDefault = old('last_name', $lastNameDefault);
                $academicStudentIdDefault = old('student_id_number', $currentUser->student_id ?? '');
            @endphp

            <!-- Progress Indicator (moved to top) -->
            <div class="academic-progress-wrapper">
                <div class="academic-progress-header">Step <span id="academicStepCounter">1</span> of <span data-step-total>3</span><span class="academic-progress-desktop-colon">:</span> <span id="academicStepLabel" hidden></span></div>
                <div class="academic-mobile-progress" aria-hidden="true" hidden><span></span></div>
                <div class="academic-progress-track" aria-live="polite">
                    <div class="academic-progress-step active" data-progress-step="1">
                        <div class="academic-progress-dot">1</div>
                        <div class="academic-progress-label">Student Information</div>
                    </div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-progress-step="2">
                        <div class="academic-progress-dot">2</div>
                        <div class="academic-progress-label">Personal Information</div>
                    </div>
                    <div class="academic-progress-line"></div>
                    <div class="academic-progress-step" data-progress-step="3">
                        <div class="academic-progress-dot">3</div>
                        <div class="academic-progress-label">Scholarship &amp; Requirements</div>
                    </div>
                </div>
            </div>

            <form id="academicApplyForm" method="POST" action="{{ route('student-profiles.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="application_id" value="">
                <input type="hidden" name="student_profile_id" value="">
                <input type="hidden" name="application_form_type" value="academic">

                <div class="apply-row academic-step-panel active" data-academic-step="1">
                    <div>
                        <label>First name</label>
                        <input class="input" type="text" name="first_name" value="{{ $academicFirstNameDefault }}" required>
                    </div>
                    <div>
                        <label>Middle name</label>
                        <input class="input" type="text" name="middle_name" value="{{ $academicMiddleNameDefault }}">
                    </div>
                    <div>
                        <label>Last name</label>
                        <input class="input" type="text" name="last_name" value="{{ $academicLastNameDefault }}" required>
                    </div>
                    <div>
                        <label>Suffix</label>
                        <input class="input" type="text" name="suffix" value="{{ old('suffix') }}">
                    </div>
                    <div>
                        <label>Email</label>
                        <input class="input email-readonly" type="email" name="email" value="{{ $academicEmailDefault }}" readonly aria-readonly="true" title="Verified account email">
                    </div>
                    <div>
                        <label>Student ID Number</label>
                        <input class="input" type="text" name="student_id_number" value="{{ $academicStudentIdDefault }}" inputmode="numeric" maxlength="8" pattern="\d{2}-\d{5}" autocomplete="off" required>
                    </div>
                    <div>
                        <label>Course</label>
                        <select name="course" class="input" required>
                            <option value="">Select course</option>
                            <option value="BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics">BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics</option>
                            <option value="BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive">BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive</option>
                            <option>BACHELOR OF SCIENCE IN CRIMINOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN FISHERIES</option>
                            <option>BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY</option>
                            <option>BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT</option>
                            <option>BACHELOR OF SECONDARY EDUCATION</option>
                            <option>BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM</option>
                            <option>BACHELOR  OF ELEMENTARY EDUCATION</option>
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
                        <label for="academicProgram">Program</label>
                        <input id="academicProgram" class="input" type="text" name="program" value="" readonly aria-readonly="true">
                    </div>
                    <div>
                        <label>College or Department</label>
                        <input class="input" type="text" name="college_department" value="" readonly aria-readonly="true">
                    </div>
                </div>

                <div class="apply-row academic-step-panel" data-academic-step="2">
                    <div>
                        <label>Birthday</label>
                        <input class="input" type="date" name="birthdate" max="2010-12-31" required>
                    </div>
                    <div>
                        <label>Birthplace</label>
                        <input class="input" type="text" name="birthplace" required>
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
                        </select>
                    </div>
                    <div>
                        <label>Religion</label>
                        <select name="religion" class="input" required>
                            <option value="">Select</option>
                            <option>Methodist</option>
                            <option>Iglesia ni Cristo</option>
                            <option>Catholic</option>
                            <option>Born Again</option>
                            <option>Others</option>
                        </select>
                    </div>
                    <div id="academicReligionOtherField" hidden>
                        <label>Please specify religion</label>
                        <input class="input" type="text" name="religion_other" placeholder="Please specify religion">
                    </div>
                    <div>
                        <label>Contact number</label>
                        <div class="contact-number-control">
                            <span class="contact-number-prefix" aria-hidden="true">+63 9</span>
                            <input class="input" type="tel" name="contact_number" inputmode="numeric" maxlength="9" pattern="\d{9}" autocomplete="tel-national" required>
                        </div>
                    </div>
                    <div class="full">
                        <label>Home Address</label>
                        <textarea name="home_address" class="input" rows="2" required></textarea>
                    </div>
                </div>

                <div class="apply-row academic-step-panel" data-academic-step="3">
                    <div class="full">
                        <label>Name of Scholarship / Sponsor</label>
                        <input id="academic_scholarship_name" name="scholarship_name" class="input" type="text" value="" required readonly>
                        <input type="hidden" id="academic_scholarship_id" name="scholarship_id" value="">
                    </div>
                    <div class="full">
                        <label>Types of Scholarship</label>
                        <input id="academic_scholarship_type" name="scholarship_type" class="input" type="text" value="" required readonly>
                    </div>
                    <div class="full">
                        <div style="background:#f9f9f9; padding:12px; border-radius:8px; margin-bottom:8px; border:1px solid #e5e5e5; font-size:0.85rem;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#333;">Supported file types & sizes:</p>
                            <ul style="margin:0; padding-left:20px; color:#666;">
                                <li><strong>Documents:</strong> PDF, DOCX (max 2MB)</li>
                            </ul>
                        </div>
                        <div id="academicCombinedDocumentSection" data-reapplication-document-group="supporting_document" style="display:block;">
                            <input type="file" id="academicCombinedDocumentInput" name="supporting_document" accept=".pdf,.docx" required style="width:115px; overflow:hidden;">
                            <div id="academicCombinedDocumentNames" style="margin-top:8px; font-size:13px; color:#666;"></div>
                        </div>
                    </div>
                </div>

                <div class="academic-form-actions">
                    <button type="button" class="academic-nav-btn academic-nav-next" data-academic-step="next">Next</button>
                    <button type="submit" class="academic-nav-btn academic-nav-submit" data-academic-step="submit">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

  <script>
    window.academicScholarshipIdMap = @json($academicScholarshipIdMap ?? []);
    window.nonAcademicScholarshipIdMap = @json($nonAcademicScholarshipIdMap ?? []);
        window.courseInformationMap = @json(\App\Support\CourseCollegeDepartment::informationMap());
    window.scholarshipAvailabilityMessages = @json($scholarshipAvailabilityMessages ?? ['id' => [], 'name' => []]);
    window.scholarshipApplicationDateMessages = @json($scholarshipApplicationDateMessages ?? ['id' => [], 'name' => []]);
    window.scholarshipSlotCounts = @json($scholarshipSlotCounts ?? ['id' => [], 'name' => []]);
    window.currentUserId = @json(Auth::id());
  </script>
  <script src="{{ asset('js/users.js') }}?v={{ time() }}"></script>

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

    // Show file count when files are selected
    const nonAcademicSupportingDocumentInput = document.getElementById('nonAcademicSupportingDocumentInput');
    if (nonAcademicSupportingDocumentInput) nonAcademicSupportingDocumentInput.addEventListener('change', function() {
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

        const academicCombinedDocumentInput = document.getElementById('academicCombinedDocumentInput');
        const academicCombinedDocumentNames = document.getElementById('academicCombinedDocumentNames');
        if (academicCombinedDocumentInput && academicCombinedDocumentNames) {
            academicCombinedDocumentInput.addEventListener('change', function() {
                academicCombinedDocumentNames.textContent = Array.from(this.files || [])
                    .map(file => file.name)
                    .join(', ');
            });
        }

  </script>

  <!-- Error Popup Styles -->
  <style>
   
  </style>

  <script>
    // Close popup when clicking outside

    (function () {
      const appliedScholarships = @json($appliedScholarships ?? []);
            window.appliedScholarships = appliedScholarships;
            window.reapplicationProfiles = Object.fromEntries(appliedScholarships
                .filter(item => item.reopen && item.profile && item.scholarship_id)
                .map(item => [`${item.grant_type}|${item.scholarship_id}`, item.profile]));
    const activeApplications = @json($activeApplications ?? 0);
      const maxApplications = 2;

      const cards = document.querySelectorAll('.s-card');

      cards.forEach(card => {
        const titleEl = card.querySelector('h3');
        const scholarshipTitle = titleEl ? titleEl.innerText.trim() : null;

        if (!scholarshipTitle) {
          return;
        }

                // Resolve static cards through their category-specific ID map.
                const cardGrantType = card.dataset.grantType || (
                    card.closest('.page-set')?.id.startsWith('non-') ? 'non-academic' : 'academic'
                );
                const scholarshipIdMap = cardGrantType === 'non-academic'
                    ? window.nonAcademicScholarshipIdMap
                    : window.academicScholarshipIdMap;
                const cardScholarshipId = String(card.dataset.scholarshipId || scholarshipIdMap?.[scholarshipTitle] || '');
                const normalizedScholarshipTitle = scholarshipTitle.toLowerCase().replace(/\s+/g, ' ').trim();
                const remainingSlots = window.scholarshipSlotCounts?.id?.[cardScholarshipId]
                    ?? window.scholarshipSlotCounts?.name?.[normalizedScholarshipTitle];
                if (remainingSlots !== undefined) {
                    let slotCountLabel = card.querySelector('.slot-count');
                    if (!slotCountLabel) {
                        slotCountLabel = document.createElement('small');
                        slotCountLabel.className = 'slot-count';
                    }
                    slotCountLabel.textContent = `${remainingSlots} slots available`;
                    const metaRow = card.querySelector('.card-meta-row');
                    if (metaRow && slotCountLabel.parentElement !== metaRow) {
                        metaRow.appendChild(slotCountLabel);
                    }
                }

                const application = appliedScholarships.find(item =>
                    cardScholarshipId
                    && String(item.scholarship_id) === cardScholarshipId
                    && item.grant_type === cardGrantType
                );
                const deadlinePassed = application?.correction_required
                    && Date.parse(application.resubmission_deadline) <= Date.now();
                const isExpired = Boolean(application?.expired || deadlinePassed);
                const isLocked = Boolean(application?.locked || isExpired);
                const canReopen = Boolean(application?.reopen);
                const isApplied = Boolean(application && application.status !== 'rejected' && !canReopen);
                const cardFoot = card.querySelector('.card-foot');

                if (isLocked) {
                    card.classList.add('card-unavailable');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'unavailable-text';
                        message.textContent = isExpired
                            ? 'EXPIRED — This application was not resubmitted in time. The slot has been released. You may apply to another available scholarship.'
                            : application.rejection_reason === 'Does not meet the eligibility requirements'
                                ? application.rejection_reason
                                : 'You\'ve reached the maximum number of applications for this scholarship.';
                        cardFoot.prepend(message);
                    }

                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = isExpired ? 'EXPIRED' : 'UNAVAILABLE';
                        applyButton.setAttribute('aria-disabled', 'true');
                        applyButton.setAttribute('href', 'javascript:void(0);');
                        applyButton.tabIndex = -1;
                        applyButton.disabled = true;
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
                            ? '<div class="applied-label status-approved">APPROVED</div><div>Your application for this scholarship is approved</div>'
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

                if (!application && activeApplications >= maxApplications) {
                    card.classList.add('card-unavailable');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'unavailable-text';
                        message.textContent = 'You already have two active applications, so this scholarship is not available.';
                        cardFoot.prepend(message);
                    }

                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = 'UNAVAILABLE';
                        applyButton.setAttribute('aria-disabled', 'true');
                        applyButton.setAttribute('href', 'javascript:void(0);');
                    }
                    return;
                }

                if (application && canReopen) {
                    card.classList.add('card-rejected');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'applied-text';
                        if (application.renewal_required) {
                            message.innerHTML = '<div class="applied-label">ACTION REQUIRED</div><div>See Application Status for more details.</div>';
                        } else if (application.correction_required && !deadlinePassed) {
                            message.textContent = 'ACTION REQUIRED — Your application needs corrections. See Application Status for details.';
                        } else {
                            message.innerHTML = '<div class="applied-label">REJECTED</div><div>Rejected. See Application Status for details.</div>';
                        }
                        cardFoot.prepend(message);
                    }
                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = 'VIEW & APPLY';
                        applyButton.removeAttribute('aria-disabled');
                        applyButton.setAttribute('href', '#');
                        applyButton.removeAttribute('tabindex');
                        applyButton.classList.remove('btn-applied');
                        applyButton.disabled = false;
                    }
                    return;
                }

                if (application && application.status === 'rejected') {
                    card.classList.add('card-rejected');
                    if (cardFoot) {
                        const message = document.createElement('div');
                        message.className = 'applied-text';
                        message.innerHTML = '<div class="applied-label">REJECTED</div><div>Rejected. See Application Status for details.</div>';
                        cardFoot.prepend(message);
                    }
                    const applyButton = card.querySelector('.btn-apply');
                    if (applyButton) {
                        applyButton.textContent = 'VIEW STATUS';
                        applyButton.removeAttribute('aria-disabled');
                        applyButton.setAttribute('href', '#');
                        applyButton.classList.remove('btn-applied');
                    }
                    return;
                }
      });
    })();
  </script>
</x-app-layout>
</body>
</html>