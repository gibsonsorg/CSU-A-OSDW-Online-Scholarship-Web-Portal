<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships | CSU Aparri</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
   
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
        <div class="s-card" data-name="cagayan valley association hawaii internationally funded">
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

        <div class="s-card" data-name="university based scholarship academic inhouse">
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

        <div class="s-card" data-name="acef giahep government locally funded">
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

        <div class="s-card" data-name="dost sei project strand graduate school government">
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

        <div class="s-card" data-name="tulong dunong kuya win scholarship government">
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

        <div class="s-card" data-name="lgu aparri local government unit scholarship">
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
        <div class="s-card" data-name="natalged lasam educational assistance government">
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

        <div class="s-card" data-name="andres tamayo scholarship private foundation">
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

        <div class="s-card" data-name="gokongwei brothers foundation scholarship private">
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
        <div class="s-card" data-name="athletic scholarship sports inhouse">
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

        <div class="s-card" data-name="employees grant inhouse dependent faculty">
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

        <div class="s-card" data-name="socio cultural scholarship arts inhouse">
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

        <div class="s-card" data-name="campus student publication university based inhouse">
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

        <div class="s-card" data-name="differently abled persons pwd disability university based inhouse">
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

        <div class="s-card" data-name="university student council federation grant inhouse leadership">
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
        <div class="s-card" data-name="csu aparri alumni association inhouse">
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

        <div class="s-card" data-name="tertiary education subsidy tes unifast government locally funded">
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

        <div class="s-card" data-name="agkaykaysa government locally funded regional">
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

        <div class="s-card" data-name="tulong dunong sen loren legarda government locally funded">
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

        <div class="s-card" data-name="open heart scholarship private foundation">
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

        <div class="s-card" data-name="talamayan scholarship private community">
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

</div>
    <!-- Apply Modal -->
    <div id="applyModal" class="apply-modal" role="dialog" aria-hidden="true">
        <div class="modal-card">
            <button class="modal-close" id="applyModalClose">✕</button>
            <h1 class="form-title">Scholarship Application</h1>
            @if(session('success'))
                <div style="background:#e6ffed;padding:8px;border-radius:6px;margin-bottom:8px;color:#1f6f3a;">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div style="background:#ffe6e6;padding:8px;border-radius:6px;margin-bottom:8px;color:#8b1a1a;">{{ session('error') }}</div>
            @endif
            
                <form id="applyForm" method="POST" action="{{ route('student-profiles.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="apply-row">
                    <div>
                        <label>First name</label>
                        <input class="input" type="text" name="first_name" required>
                    </div>
                    <div>
                        <label>Middle name</label>
                        <input class="input" type="text" name="middle_name">
                    </div>
                    <div>
                        <label>Last name</label>
                        <input class="input" type="text" name="last_name" required>
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
                        <input class="input" type="email" name="email" required>
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
                        <label>Upload files (pdf,jpg,png,doc)</label><br>
                        <input type="file" name="uploads[]" multiple>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

  <script src="{{ asset('js/users.js') }}"></script>
</body>
</html>