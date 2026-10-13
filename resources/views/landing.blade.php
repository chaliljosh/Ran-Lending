<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="login-route" content="{{ route('login') }}">
  <title>RAN Serenity</title>

  <!-- ============================================================
       DARK MODE — set theme attribute BAGO mag-load ang CSS, para
       hindi "kumurap" (flash) papunta sa maling theme pag-reload.
       ============================================================ -->
  <script>
    (function () {
      try {
        var saved = localStorage.getItem('ran-theme');
        var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
      } catch (e) {}
    })();
  </script>

  <!-- Favicon -->
  <link href="{{ asset('assets/landingpage/img/ranlogo1.jpg') }}" rel="icon">

  <!-- Google Fonts -->

<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap & Icons -->
  <link href="{{ asset('assets/landingpage/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- AOS + Lightbox -->
  <link href="{{ asset('assets/landingpage/vendor/aos/aos.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/landingpage/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">

  <!-- main.css loaded FIRST, then our overrides come AFTER so they win -->
  <link href="{{ asset('assets/landingpage/css/main.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/landingpage/css/section.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/landingpage/css/user-menu.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/landingpage/css/theme-calamansi.css') }}" rel="stylesheet">
</head>

<body class="index-page">

  <!-- ============================================================
       NAVBAR
       ============================================================ -->
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="/" class="logo d-flex align-items-center me-auto">
        <img src="{{ asset('assets/landingpage/img/ranlogo.png') }}" alt="Ran Serenity logo" class="logo-img">
        <h1 class="sitename">RAN Serenity</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#features">Terms</a></li>
            <li><a href="#about">About</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <!-- ============================================================
           DARK MODE TOGGLE
           ============================================================ -->
      <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle dark mode">
        <i class="bi bi-sun-fill theme-icon-light"></i>
        <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
      </button>

      @if(session('user_id'))
        {{-- Logged in: AVATAR LANG, walang pangalan na nakikita sa navbar --}}
        <div class="landing-user-menu">
          <button type="button" class="landing-user-trigger" id="landingUserTrigger">
            <span class="landing-user-avatar">
    @if($userProfilePictureUrl)
        <img src="{{ $userProfilePictureUrl }}"
             alt="{{ session('user_name') }}"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"
             onerror="this.style.display='none'; this.parentElement.textContent='{{ strtoupper(substr(session('user_name','U'),0,1)) }}';">
    @else
        {{ strtoupper(substr(session('user_name', 'U'), 0, 1)) }}
    @endif
</span>
            <span class="landing-user-name">{{ session('user_name') }}</span>
            <i class="bi bi-chevron-down"></i>
          </button>

          <div class="landing-user-dropdown" id="landingUserDropdown">
            <div class="landing-user-dropdown-head">
              <span class="landing-user-avatar-lg">
    @if($userProfilePictureUrl)
        <img src="{{ $userProfilePictureUrl }}"
             alt="{{ session('user_name') }}"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"
             onerror="this.style.display='none'; this.parentElement.textContent='{{ strtoupper(substr(session('user_name','U'),0,1)) }}';">
    @else
        {{ strtoupper(substr(session('user_name', 'U'), 0, 1)) }}
    @endif
</span>
              <div>
                <div class="landing-user-dd-name">{{ session('user_name') }}</div>
                <div class="landing-user-dd-email">{{ session('user_email') }}</div>
              </div>
            </div>
            <div class="landing-user-dropdown-divider"></div>

                       @if(session('user_role') === 'admin')
              {{-- ADMIN: Dashboard + Account Settings + Logout. Walang My
                   Profile dahil ang "My Profile" modal ay view-only info na
                   overlap na sa laman ng Account Settings modal. --}}
              <a href="{{ route('admin.dashboard') }}" class="landing-user-dd-item">
                <i class="bi bi-speedometer2"></i> Dashboard
              </a>
              <a href="{{ route('admin.dashboard', ['open' => 'settings']) }}" class="landing-user-dd-item">
                <i class="bi bi-gear"></i> Account Settings
              </a>
          @else
  {{-- BORROWER: Dashboard, My Profile, tapos Account Settings --}}
  <a href="{{ route('borrower.dashboard') }}" class="landing-user-dd-item">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>
  <a href="{{ route('borrower.profile') }}" class="landing-user-dd-item">
    <i class="bi bi-person"></i> My Profile
  </a>
  
@endif

            <div class="landing-user-dropdown-divider"></div>
            <a href="javascript:void(0);" class="landing-user-dd-item text-danger" id="landingLogoutBtn">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </div>
        </div>
      @else
        <a class="cta-btn" href="{{ route('login') }}">Sign In</a>
      @endif

    </div>
  </header>
  
  <main class="main">

    <!-- ============================================================
         HERO
         ============================================================ -->
    <section id="hero">
      <div class="hero-inner">

        <!-- Left: headline + stats -->
   <div class="hero-text">
<h1>Smart. Secure.<br><span class="gray">Simple Lending.</span></h1>
</div>
        <!-- Right: calculator card -->
        <div class="calc-card">
          <h3>Try it yourself</h3>
          <p class="calc-subtitle">See your estimated monthly payment</p>

          <!-- Amount -->
          <div class="calc-row">
            <div class="calc-top">
              <span class="calc-label">Loan Amount</span>
              <span class="calc-value" id="amountDisplay">21,500 <span class="unit">PHP</span></span>
            </div>
            <input type="range" id="amountSlider" min="1000" max="100000" step="500" value="21500">
            <div class="slider-labels">
              <span>₱1,000</span>
              <span>₱100,000</span>
            </div>
          </div>

          <!-- Term -->
<div class="calc-row">
  <div class="calc-top">
    <span class="calc-label">Loan Term</span>
    <span class="calc-value" id="termDisplay">3 <span class="unit">Month</span></span>
  </div>
  <select id="termSelect" class="calc-term-select">
    <option value="1">1 Month</option>
    <option value="2">2 Months</option>
    <option value="3" selected>3 Months</option>
    <option value="4">4 Months</option>
    <option value="5">5 Months</option>
    <option value="6">6 Months</option>
    <option value="7">7 Months</option>
    <option value="8">8 Months</option>
    <option value="9">9 Months</option>
    <option value="10">10 Months</option>
    <option value="11">11 Months</option>
    <option value="12">12 Months</option>
</select>
</div>

          <!-- Result -->
          <div class="calc-summary">
            <div class="sum-label">
              Est. Monthly Payment
              <small>Interest: 5% / month</small>
            </div>
            <div class="sum-amount" id="monthlyPayment">₱22,575</div>
          </div>

          @if(session('user_id'))
            <a href="{{ session('user_role') === 'admin' ? route('admin.dashboard') : route('borrower.apply-loan') }}" class="calc-cta">Get Loan Proposal</a>
          @else
            <a href="{{ route('login') }}" class="calc-cta">Get Loan Proposal</a>
          @endif
        </div>
      </div>
    </section>

    <!-- ============================================================
         PROCESS
         ------------------------------------------------------------
         Buong card clickable papunta sa Sign In gamit ang
         .stretched-link (fills the whole .service-item box).
         ============================================================ -->
    <section id="services-2" class="services-2 section light-background">
      <div class="container section-title" data-aos="fade-up">
        <h2>Process</h2>
        <p>How it works</p>
      </div>
      <div class="container">
        <div class="row gy-4">
          <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item d-flex position-relative h-100">
              <i class="bi bi-card-text icon flex-shrink-0"></i>
              <div>
                <h4 class="title"><a href="{{ route('login') }}" class="stretched-link">1. Fill up a form</a></h4>
                <p class="description">Complete the loan application form by providing all required personal and loan information.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item d-flex position-relative h-100">
              <i class="bi bi-file-earmark-arrow-up icon flex-shrink-0"></i>
              <div>
                <h4 class="title"><a href="{{ route('login') }}" class="stretched-link">2. Send Documents</a></h4>
                <p class="description">Upload or submit the necessary documents required  for verification and loan processing.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item d-flex position-relative h-100">
              <i class="bi bi-clock-history icon flex-shrink-0"></i>
              <div>
                <h4 class="title"><a href="{{ route('login') }}" class="stretched-link">3. Wait for approval</a></h4>
                <p class="description">The admin reviews the submitted application and documents before approving the loan request.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item d-flex position-relative h-100">
              <i class="bi bi-cash-stack icon flex-shrink-0"></i>
              <div>
                <h4 class="title">4. <a href="{{ route('login') }}" class="stretched-link">Get your money</a></h4>
                <p class="description">Once approved, the loan is released and recorded in the system for monitoring.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================================================
         TERMS (tabbed)
         ============================================================ -->
    <section id="features" class="features section py-4">
      <div class="container">
        <div class="container section-title" data-aos="fade-up">
          <h2 class="mb-1">Terms</h2>
          <p class="mb-0">Rules & Regulation</p>
        </div>

        <ul class="nav nav-tabs row d-flex mb-3" data-aos="fade-up" data-aos-delay="100">
          <li class="nav-item col-3">
            <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#features-tab-1">
              <i class="bi bi-cash-stack"></i>
              <h4 class="d-none d-lg-block">Interest Rate</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
              <i class="bi bi-file-text"></i>
              <h4 class="d-none d-lg-block">Penalty Policy</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-3">
              <i class="bi bi-shield-lock"></i>
              <h4 class="d-none d-lg-block">Late Fee</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-4">
              <i class="bi bi-calendar-check"></i>
              <h4 class="d-none d-lg-block">Due Date</h4>
            </a>
          </li>
        </ul>

        <div class="tab-content pt-3" data-aos="fade-up" data-aos-delay="200">

          <div class="tab-pane fade active show" id="features-tab-1">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Transparent Interest Computation</h3>
                <p class="fst-italic">A 5% monthly interest rate is applied to all approved loans. Interest is calculated based on the outstanding balance to ensure fair and consistent loan computation.</p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Encourages timely loan payments</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Prevents repeated loan defaults</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Supports responsible borrowing behavior</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="{{ asset('assets/landingpage/img/working-1.jpg') }}" alt="" class="img-fluid">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="features-tab-2">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Penalty Rules for Non-Payment</h3>
                <p>Borrowers who fail to pay for three consecutive months will receive one penalty point. A maximum of three penalty points may be accumulated.</p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Encourages timely loan payments</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Prevents repeated loan defaults</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Supports responsible borrowing behavior</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="{{ asset('assets/landingpage/img/working-2.jpg') }}" alt="" class="img-fluid">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="features-tab-3">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Late Payment Charges</h3>
                <p>A ₱50 late fee is added for every 7 days of delayed payment. Late fees are automatically recorded and reflected in the borrower's balance.</p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Weekly late fee computation</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Real-time balance adjustment</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Reduced manual computation errors</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="{{ asset('assets/landingpage/img/working-3.jpg') }}" alt="" class="img-fluid">
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="features-tab-4">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Assigned Loan Due Dates</h3>
                <p>Loan due dates are manually set by the administrator to define clear payment schedules and ensure borrowers are aware of their payment deadlines.</p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Manually assigned due dates</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Clear and fixed payment schedules</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Improved loan monitoring and compliance</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="{{ asset('assets/landingpage/img/working-4.jpg') }}" alt="" class="img-fluid">
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================================================
         ABOUT
         ============================================================ -->

          <section id="about" class="about section">
      <div class="container section-title" data-aos="fade-up">
        <h2>About</h2>
        <p>About Ran Serenity</p>
      </div>
      <div class="container">
        <div class="row gy-4 align-items-start">
          <div class="col-lg-6" data-aos="fade-up" data-aosg-delay="100">
            <div class="about-logo-wrap">
              <img src="{{ asset('assets/landingpage/img/ranlogo2.png') }}" class="about-logo-light" alt="Ran Serenity logo">
              <img src="{{ asset('assets/landingpage/img/ranlogo-dark.png') }}" class="about-logo-dark" alt="Ran Serenity logo">
            </div>
          </div>
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="250">
            <div class="content ps-0 ps-lg-5">
             <p>Ran Serenity is a lending company established in 2022 in Olongapo City. Founded by three individuals, the company began as a small-scale, trust-based lending business focused on providing accessible financial assistance.</p>
            <p>As demand increased, the company transitioned into a more structured operation, formalizing loan processes and improving record management while maintaining integrity, reliability, and personalized customer service.</p>
              <ul>
                <li><i class="bi bi-check-circle-fill"></i> <span>Centralized loan application and approval management</span></li>
                <li><i class="bi bi-check-circle-fill"></i> <span>Organized borrower and loan record tracking</span></li>
                <li><i class="bi bi-check-circle-fill"></i> <span>Secure data handling and structured workflows</span></li>
              </ul>
             
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         CONTACT
         ============================================================ -->
    <section id="contact" class="contact section">
  <div class="container section-title" data-aos="fade-up">
    <h2>Contact</h2>
    <p>Help & Support</p>
  </div>

  <div class="container" data-aos="fade-up" data-aos-delay="100">
    <div class="row gy-4 justify-content-center">
      <!-- CENTER COLUMN -->
      <div class="col-lg-8">
        <div class="row gy-4 text-center">
          
          <div class="col-12">
            <div class="info-item d-flex flex-column justify-content-center align-items-center"
              data-aos="fade-up" data-aos-delay="200">
              <i class="bi bi-geo-alt"></i>
              <h3>Address</h3>
              <p>Olongapo, PH</p>
            </div>
          </div>

          <div class="col-md-6">
            <div class="info-item d-flex flex-column justify-content-center align-items-center"
              data-aos="fade-up" data-aos-delay="300">
              <i class="bi bi-telephone"></i>
              <h3>Call Us</h3>
              <p>+639691898835</p>
            </div>
          </div>

          <div class="col-md-6">
            <div class="info-item d-flex flex-column justify-content-center align-items-center"
              data-aos="fade-up" data-aos-delay="400">
              <i class="bi bi-envelope"></i>
              <h3>Email Us</h3>
              <p>ranserenity77@gmail.com</p>
            </div>
          </div>

        </div>
      </div>
      <!-- END CENTER COLUMN -->
    </div>
  </div>
</section>

  </main>

  <!-- ============================================================
       FOOTER
       ============================================================ -->
  <footer id="footer" class="footer dark-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="/" class="logo d-flex align-items-center">
            <span class="sitename">Ran Serenity</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Olongapo, PH</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+639691898835</span></p>
            <p><strong>Email:</strong> <span>ranserenity77@gmail.com</span></p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><i class="bi bi-chevron-right"></i> <a href="/">Home</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="/#about">About us</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="/#services-2">Services</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="{{ route('legal.terms') }}">Terms of service</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="{{ route('legal.privacy') }}">Privacy policy</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">RAN Serenity</strong> <span>All Rights Reserved</span></p>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <div id="preloader"></div>

  <!-- Vendor JS -->
  <script src="{{ asset('assets/landingpage/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/landingpage/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('assets/landingpage/vendor/aos/aos.js') }}"></script>
  <script src="{{ asset('functions/session-sync.js') }}"></script>
  <script src="{{ asset('assets/landingpage/vendor/glightbox/js/glightbox.min.js') }}"></script>
  <script src="{{ asset('assets/landingpage/vendor/purecounter/purecounter_vanilla.js') }}"></script>
  <script src="{{ asset('assets/landingpage/js/main.js') }}"></script>
  <script src="{{ asset('functions/landing-page.js') }}"></script>
  <script src="{{ asset('functions/theme-toggle.js') }}"></script>

  @if(session('user_id'))
  <script>
    document.getElementById('landingUserTrigger').addEventListener('click', function (e) {
      e.stopPropagation();
      document.getElementById('landingUserDropdown').classList.toggle('is-open');
    });
    document.addEventListener('click', function () {
      document.getElementById('landingUserDropdown').classList.remove('is-open');
    });

    document.getElementById('landingLogoutBtn').addEventListener('click', function () {
      SessionSync.broadcastLogout();
      fetch('{{ route('logout') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
        },
      }).finally(() => {
        window.location.reload();
      });
    });
  </script>
  @endif

</body>
</html>