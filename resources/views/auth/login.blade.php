<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - E-Learning Villa Merah</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('images/villa-merah-logo.svg') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/villa-merah-logo.svg') }}">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    .auth-page {
      position: fixed;
      inset: 0;
      z-index: 60;
      overflow-y: auto;
      display: grid;
      place-items: center;
      min-height: 100vh;
      padding: 32px;
      background:
        radial-gradient(circle at 14% 15%, rgba(99, 102, 241, .18) 0 90px, transparent 92px),
        radial-gradient(circle at 86% 82%, rgba(20, 184, 166, .16) 0 110px, transparent 112px),
        linear-gradient(135deg, #eef2ff 0%, #f5f3ff 48%, #ecfeff 100%);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .auth-page::before,
    .auth-page::after {
      content: "";
      position: fixed;
      border-radius: 999px;
      pointer-events: none;
    }

    .auth-page::before {
      width: 86px;
      height: 86px;
      left: 9%;
      top: 11%;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      box-shadow: 0 22px 55px rgba(79, 70, 229, .28);
    }

    .auth-page::after {
      width: 118px;
      height: 118px;
      right: 9%;
      bottom: 13%;
      background: rgba(255, 255, 255, .84);
      box-shadow: 0 24px 70px rgba(99, 102, 241, .14);
    }

    .auth-card {
      position: relative;
      z-index: 1;
      width: min(1120px, 100%);
      min-height: 620px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(420px, .95fr);
      overflow: hidden;
      border-radius: 30px;
      background: rgba(255, 255, 255, .94);
      box-shadow: 0 36px 90px rgba(79, 70, 229, .18);
    }

    .auth-form-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 56px 42px;
    }

    .auth-form {
      width: min(390px, 100%);
    }

    .brand-mark {
      width: 54px;
      height: 54px;
      display: grid;
      place-items: center;
      margin: 0 auto 22px;
      overflow: hidden;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 18px 38px rgba(239, 29, 45, .28);
    }

    .brand-mark img,
    .course-logo {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .auth-title {
      margin: 0;
      color: #111827;
      font-size: 34px;
      line-height: 1.1;
      font-weight: 800;
      text-align: center;
      letter-spacing: .01em;
    }

    .auth-subtitle {
      margin: 12px auto 28px;
      color: #6b7280;
      font-size: 14px;
      line-height: 1.65;
      text-align: center;
    }

    .auth-alert {
      margin-bottom: 16px;
      padding: 12px 14px;
      border-radius: 14px;
      font-size: 13px;
    }

    .auth-alert.success {
      color: #166534;
      background: #dcfce7;
    }

    .auth-alert.error {
      color: #991b1b;
      background: #fee2e2;
    }

    .input-wrap {
      position: relative;
      margin-bottom: 16px;
    }

    .input-wrap i {
      position: absolute;
      left: 18px;
      top: 50%;
      color: #5b5f72;
      transform: translateY(-50%);
    }

    .input-wrap input {
      width: 100%;
      border: 1px solid transparent;
      border-radius: 17px;
      background: #f1f0ff;
      color: #111827;
      padding: 16px 52px;
      font-size: 14px;
      outline: none;
      transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }

    .input-wrap input:focus {
      border-color: #8b5cf6;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(139, 92, 246, .14);
    }

    .password-toggle {
      position: absolute;
      top: 50%;
      right: 16px;
      border: 0;
      color: #6b7280;
      background: transparent;
      cursor: pointer;
      transform: translateY(-50%);
    }

    .auth-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin: 2px 0 24px;
      color: #5b5f72;
      font-size: 13px;
    }

    .auth-options label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .auth-options input {
      accent-color: #6d5dfc;
    }

    .auth-link {
      color: #5b45f2;
      font-weight: 700;
      text-decoration: none;
    }

    .primary-btn {
      width: 100%;
      border: 0;
      border-radius: 17px;
      padding: 15px 18px;
      color: #fff;
      font-weight: 800;
      cursor: pointer;
      background: linear-gradient(135deg, #38bdf8, #6d5dfc 46%, #7c3aed);
      box-shadow: 0 18px 32px rgba(109, 93, 252, .28);
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .primary-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 22px 38px rgba(109, 93, 252, .34);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 28px 0 18px;
      color: #8b8fa3;
      font-size: 13px;
    }

    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: #e5e7eb;
    }

    .social-grid {
      display: grid;
      gap: 12px;
    }

    .social-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      min-height: 48px;
      border: 1px solid #e7e8f3;
      border-radius: 16px;
      color: #374151;
      background: #fff;
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .social-btn:hover {
      border-color: #c7d2fe;
      box-shadow: 0 14px 30px rgba(79, 70, 229, .1);
      transform: translateY(-1px);
    }

    .register-note {
      margin-top: 22px;
      color: #6b7280;
      font-size: 14px;
      text-align: center;
    }

    .auth-visual-panel {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      padding: 48px;
      color: #fff;
      background:
        linear-gradient(120deg, rgba(255, 255, 255, .13) 0 1px, transparent 1px) 0 0 / 72px 72px,
        radial-gradient(circle at 78% 20%, rgba(45, 212, 191, .34), transparent 34%),
        linear-gradient(135deg, #4338ca 0%, #6957ff 52%, #2dd4bf 130%);
    }

    .auth-visual-panel::before {
      content: "";
      position: absolute;
      inset: -20%;
      background:
        radial-gradient(closest-side at 22% 42%, transparent 72%, rgba(255, 255, 255, .1) 73% 75%, transparent 76%),
        radial-gradient(closest-side at 82% 78%, transparent 68%, rgba(255, 255, 255, .1) 69% 71%, transparent 72%);
      opacity: .9;
    }

    .learning-card {
      position: relative;
      width: min(380px, 100%);
      border: 1px solid rgba(255, 255, 255, .34);
      border-radius: 34px;
      padding: 26px;
      background: rgba(255, 255, 255, .16);
      box-shadow: 0 35px 90px rgba(25, 25, 112, .2);
      backdrop-filter: blur(12px);
    }

    .course-window {
      overflow: hidden;
      border-radius: 26px;
      background: #fff;
      color: #111827;
      box-shadow: 0 22px 50px rgba(15, 23, 42, .2);
    }

    .course-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px;
      background: #f8fafc;
    }

    .course-dots {
      display: flex;
      gap: 6px;
    }

    .course-dots span {
      width: 9px;
      height: 9px;
      border-radius: 999px;
      background: #c4b5fd;
    }

    .course-badge {
      border-radius: 999px;
      padding: 6px 10px;
      color: #4338ca;
      background: #eef2ff;
      font-size: 11px;
      font-weight: 800;
    }

    .course-body {
      padding: 20px;
    }

    .course-hero {
      display: grid;
      place-items: center;
      height: 150px;
      border-radius: 22px;
      color: #fff;
      background:
        radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .44), transparent 23%),
        linear-gradient(135deg, #fff5f5, #fee2e2 48%, #fff);
    }

    .course-logo {
      width: 86px;
      height: 86px;
      filter: drop-shadow(0 15px 18px rgba(153, 27, 27, .2));
    }

    .course-line {
      height: 12px;
      margin-top: 16px;
      border-radius: 999px;
      background: #e5e7eb;
    }

    .course-line.short {
      width: 70%;
    }

    .course-progress {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 12px;
      align-items: center;
      margin-top: 18px;
      color: #475569;
      font-size: 12px;
      font-weight: 800;
    }

    .progress-bar {
      height: 9px;
      overflow: hidden;
      border-radius: 999px;
      background: #e0e7ff;
    }

    .progress-bar span {
      display: block;
      width: 72%;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, #38bdf8, #7c3aed);
    }

    .floating-pill,
    .floating-bolt {
      position: absolute;
      display: grid;
      place-items: center;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
    }

    .floating-pill {
      left: -34px;
      bottom: 70px;
      width: 112px;
      height: 42px;
      color: #4338ca;
      font-size: 12px;
      font-weight: 900;
    }

    .floating-bolt {
      top: 102px;
      right: -24px;
      width: 64px;
      height: 64px;
      color: #f59e0b;
      font-size: 27px;
    }

    .visual-caption {
      position: relative;
      margin-top: 24px;
      text-align: center;
    }

    .visual-caption h3 {
      margin: 0;
      font-size: 26px;
      line-height: 1.2;
      font-weight: 900;
    }

    .visual-caption p {
      margin: 10px auto 0;
      max-width: 320px;
      color: rgba(255, 255, 255, .82);
      font-size: 14px;
      line-height: 1.65;
    }

    @media (max-width: 900px) {
      .auth-card {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .auth-visual-panel {
        min-height: 410px;
        order: -1;
      }
    }

    @media (max-width: 560px) {
      .auth-page {
        padding: 18px;
        place-items: start center;
      }

      .auth-form-panel,
      .auth-visual-panel {
        padding: 34px 22px;
      }

      .auth-card {
        border-radius: 22px;
      }

      .auth-visual-panel {
        display: none;
      }

      .auth-title {
        font-size: 28px;
      }

      .auth-options {
        align-items: flex-start;
        flex-direction: column;
      }

      .floating-pill {
        left: 18px;
      }

      .floating-bolt {
        right: 18px;
      }
    }
  </style>
</head>
<body>

  <section class="auth-page">
    <div class="auth-card">
      <div class="auth-form-panel">
        <div class="auth-form">
          <div class="brand-mark">
            <img src="{{ asset('images/villa-merah-logo.svg') }}" alt="Logo Villa Merah">
          </div>
          <h1 class="auth-title">Masuk Kelas</h1>
          <p class="auth-subtitle">Lanjutkan belajar menggambar, tonton video pembelajaran terbaru, dan kumpulkan tugas dari satu ruang belajar.</p>

          @if(session('success'))
            <div class="auth-alert success">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="auth-alert error">{{ $errors->first() }}</div>
          @endif

          <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="input-wrap">
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
              <input name="email" value="{{ old('email') }}" type="email" required autofocus placeholder="Email pengguna">
            </div>

            <div class="input-wrap">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              <input id="passwordInput" name="password" type="password" required placeholder="Kata sandi">
              <button class="password-toggle" type="button" aria-label="Tampilkan kata sandi" onclick="toggleLoginPassword(this)">
                <i class="fa-regular fa-eye" aria-hidden="true"></i>
              </button>
            </div>

            <div class="auth-options">
              <label>
                <input type="checkbox" name="remember">
                <span>Ingat saya</span>
              </label>
              <a class="auth-link" href="#">Lupa kata sandi?</a>
            </div>

            <button class="primary-btn" type="submit">Login Sekarang</button>
          </form>

          <div class="divider">atau login dengan</div>

          <div class="social-grid">
            <a class="social-btn" href="#">
              <i class="fa-brands fa-google" style="color:#ea4335" aria-hidden="true"></i>
              Google
            </a>
            <a class="social-btn" href="#">
              <i class="fa-brands fa-facebook" style="color:#1877f2" aria-hidden="true"></i>
              Facebook
            </a>
          </div>

          <p class="register-note">
            Akun siswa dibuat oleh admin atau pengajar yang berwenang.
          </p>
        </div>
      </div>

      <div class="auth-visual-panel">
        <div>
          <div class="learning-card">
            <div class="course-window">
              <div class="course-top">
                <div class="course-dots"><span></span><span></span><span></span></div>
                <div class="course-badge">72% selesai</div>
              </div>
              <div class="course-body">
                <div class="course-hero">
                  <img src="{{ asset('images/villa-merah-logo.svg') }}" alt="Logo Villa Merah" class="course-logo">
                </div>
                <div class="course-line"></div>
                <div class="course-line short"></div>
                <div class="course-progress">
                  <div class="progress-bar"><span></span></div>
                  <span>Level 4</span>
                </div>
              </div>
            </div>
            <div class="floating-pill">Kelas Aktif</div>
            <div class="floating-bolt"><i class="fa-solid fa-bolt" aria-hidden="true"></i></div>
          </div>
          <div class="visual-caption">
            <h3>Belajar visual terasa lebih ringan.</h3>
            <p>Masuk untuk membuka video pembelajaran, latihan, dan progres belajar menggambar Anda.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    function toggleLoginPassword(button) {
      const input = document.getElementById('passwordInput');
      const icon = button.querySelector('i');
      const showing = input.type === 'text';

      input.type = showing ? 'password' : 'text';
      button.setAttribute('aria-label', showing ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
      icon.className = showing ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
    }
  </script>
</body>
</html>
