<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5, user-scalable=yes" />
  <meta name="description" content="Sistem Informasi Perpustakaan Digital Sekolah Modern.">
  <title>Perpustakaan Digital — Platform Sekolah Modern</title>
  <link rel="stylesheet" href="assets/css/landing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap"
    rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
  <?php require_once __DIR__ . '/theme-loader.php'; ?>
</head>

<body>

  <!-- HEADER -->
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="brand">
        <div class="logo-icon">
          <img src="img/logo.png" alt="Logo">
        </div>
        <div class="brand-text">
          <div class="site-title">Perpustakaan<span style="color:var(--accent);">Digital</span></div>
        </div>
      </a>

      <nav class="main-nav">
        <a href="#features">Fitur</a>
        <a href="#audience">Solusi</a>
        <a href="#stats">Statistik</a>
        <a href="#testimonials">Testimoni</a>
        <!-- Mobile only -->
        <a href="school.php" class="nav-btn hide-on-desktop" style="margin-top: 10px; background: var(--primary); color: #fff; padding: 12px 32px; border-radius: 50px; text-decoration: none; justify-content: center;">Cari Perpustakaan</a>
      </nav>

      <div class="nav-right">
        <a href="school.php" class="nav-btn login"><iconify-icon icon="solar:magnifer-linear" style="margin-right: 4px; vertical-align: -2px;"></iconify-icon> Cari Sekolah</a>
      </div>

      <button class="nav-toggle" aria-label="Toggle menu">
        <iconify-icon icon="solar:hamburger-menu-linear"></iconify-icon>
      </button>
    </div>
  </header>

  <main>
    <!-- HERO -->
    <section class="hero">
      <div class="hero-bg-glow"></div>
      <div class="container hero-inner">
        <div class="hero-content">
          <div class="badge-pill">
            <span class="badge-dot"></span>Telah Hadir
          </div>
          <h1>Transformasi Digital Perpustakaan Sekolah Anda</h1>
          <p class="lede">Platform manajemen perpustakaan modern yang terintegrasi, efisien, dan menyenangkan. Kelola buku, anggota, dan peminjaman dalam satu dashboard pintar.</p>
          <div class="hero-actions">
            <a href="school.php" class="btn btn-primary btn-lg">
              <iconify-icon icon="solar:magnifer-linear"></iconify-icon> Cari Sekolah Anda
            </a>
            <a href="#features" class="btn btn-outline btn-lg">Pelajari Fitur</a>
          </div>
          <div class="hero-trust">
            <p>Dipercaya oleh 50+ Sekolah di Indonesia</p>
            <div class="trust-icons">
            </div>
          </div>
        </div>
        <div class="hero-image">
          <div class="hero-card glass-card">
            <div class="card-header">
              <div class="dot red"></div>
              <div class="dot yellow"></div>
              <div class="dot green"></div>
            </div>
            <img src="img/g1.jpg" alt="Dashboard Preview" class="dashboard-img">
            
            <!-- Elements -->
            <div class="float-stat glass-card">
              <div class="stat-icon"><iconify-icon icon="solar:book-bookmark-bold"></iconify-icon></div>
              <div>
                <span class="stat-val">25rb+</span>
                <span class="stat-label">Koleksi Buku</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="section">
      <div class="container">
        <div class="section-header text-center animate-on-scroll">
          <h2 class="section-title">Fitur Lengkap & Canggih</h2>
          <p class="section-subtitle">Semua yang Anda butuhkan untuk mengelola perpustakaan modern</p>
        </div>

        <div class="bento-grid">
          <div class="bento-item large bento-1 animate-on-scroll delay-100">
            <div class="bento-content">
              <h3>Manajemen Sirkulasi Otomatis</h3>
              <p>Peminjaman dan pengembalian buku tercatat otomatis. Notifikasi denda dan keterlambatan terkirim via sistem.</p>
            </div>
            <div class="bento-visual">
              <iconify-icon icon="solar:round-transfer-horizontal-bold-duotone" class="big-icon"></iconify-icon>
            </div>
          </div>

          <div class="bento-item medium bento-2 animate-on-scroll delay-200">
            <div class="icon-box"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon></div>
            <h3>Manajemen Anggota</h3>
            <p>Database siswa, guru, dan karyawan terpusat dengan kartu anggota digital.</p>
          </div>

          <div class="bento-item medium bento-3 animate-on-scroll delay-300">
             <div class="icon-box"><iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon></div>
            <h3>Laporan Analitik</h3>
            <p>Visualisasi data peminjaman real-time untuk keputusan yang lebih baik.</p>
          </div>

          <div class="bento-item wide bento-4 animate-on-scroll delay-100">
            <div class="bento-flex">
              <div class="bento-text">
                <h3>Katalog OPAC Modern</h3>
                <p>Pencarian buku cepat & akurat. Filter kategori, penulis, dan ketersediaan real-time.</p>
              </div>
              <div class="bento-visual-opac">
                <div class="mock-search-bar">
                   <iconify-icon icon="solar:magnifer-linear"></iconify-icon>
                   <span>Cari buku sejarah...</span>
                   <div class="search-btn">Cari</div>
                </div>
                <div class="mock-tags">
                   <span>#Sejarah</span>
                   <span>#Sains</span>
                   <span>#Novel</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AUDIENCE -->
    <section id="audience" class="section bg-white animate-on-scroll">
      <div class="container">
        <div class="section-header text-center">
          <h2 class="section-title">Solusi Untuk Semua</h2>
          <p class="section-subtitle">Didesain khusus untuk memenuhi kebutuhan ekosistem sekolah</p>
        </div>
        <div class="audience-grid">
          <div class="audience-card glass-card animate-on-scroll delay-100">
            <div class="audience-icon-wrap">
              <img src="img/murid.jpg" alt="Siswa sedang belajar digital" loading="lazy">
            </div>
            <h3>Untuk Siswa</h3>
            <ul class="audience-list">
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Akses ribuan buku digital kapan saja</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Pinjam & baca dari smartphone</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Rekomendasi buku berbasis minat</li>
            </ul>
          </div>

          <div class="audience-card glass-card animate-on-scroll delay-200">
            <div class="audience-icon-wrap accent">
              <img src="img/guru.jpg" alt="Guru mengajar menggunakan materi digital" loading="lazy">
            </div>
            <h3>Untuk Guru</h3>
            <ul class="audience-list">
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Referensi bahan ajar lengkap</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Pantau minat baca siswa</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Koleksi khusus buku paket digital</li>
            </ul>
          </div>

          <div class="audience-card glass-card animate-on-scroll delay-300">
            <div class="audience-icon-wrap dark">
              <img src="img/pustakawan.jpg" alt="Pustakawan mengelola sistem administrasi" loading="lazy">
            </div>
            <h3>Untuk Pustakawan</h3>
            <ul class="audience-list">
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Automasi sirkulasi & denda</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Laporan instan & akurat</li>
              <li><iconify-icon icon="solar:check-circle-bold"></iconify-icon> Manajemen stok buku efisien</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section id="stats" class="section stats-section animate-on-scroll">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-item">
            <h3 class="counter" data-target="50">0</h3>
            <p>Sekolah Mitra</p>
          </div>
          <div class="stat-item">
            <h3 class="counter" data-target="25000">0</h3>
            <p>Buku Digital</p>
          </div>
          <div class="stat-item">
            <h3 class="counter" data-target="15000">0</h3>
            <p>Pengguna Aktif</p>
          </div>
          <div class="stat-item">
            <h3 class="counter-percent" data-target="99">0</h3>
            <p>Kepuasan User</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section id="testimonials" class="section bg-soft">
      <div class="container split-layout">
        <div class="testimonials-col animate-on-scroll">
          <h2 class="section-title">Kata Mereka</h2>
          <div class="testimonial-slider">
            <div class="testimonial-single glass-card">
              <div class="quote-icon"><iconify-icon icon="solar:quote-up-bold"></iconify-icon></div>
              <p class="quote-text">"Sistem ini mengubah cara perpustakaan kami bekerja. Dari manual ke digital dalam hitungan hari. Sangat membantu!"</p>
              <div class="user-profile">
                <div class="avatar">BS</div>
                <div>
                  <h4>Budi Santoso</h4>
                  <p>Kepala Perpus SMAN 1</p>
                </div>
              </div>
            </div>
             <div class="testimonial-single glass-card mt-4">
              <div class="quote-icon"><iconify-icon icon="solar:quote-up-bold"></iconify-icon></div>
              <p class="quote-text">"Fitur booking bukunya keren banget. Gak perlu rebutan lagi kalau ada buku baru."</p>
              <div class="user-profile">
                <div class="avatar">AD</div>
                <div>
                  <h4>Ani Dewantara</h4>
                  <p>Siswi Kelas XII</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="faq-col animate-on-scroll delay-200">
          <h2 class="section-title">FAQ</h2>
          <div class="accordion">
            <div class="accordion-item">
              <button class="accordion-header" onclick="toggleAccordion(this)">
                Apakah sistem ini berbayar?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Kami menyediakan semua fitur gratis tanpa di pungut biaya apapun.</p>
              </div>
            </div>
            <div class="accordion-item">
               <button class="accordion-header" onclick="toggleAccordion(this)">
                Bagaimana cara daftar?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Klik tombol "Daftar Sekolah" di pojok kanan atas, isi data sekolah, dan verifikasi email Anda.</p>
              </div>
            </div>
            <div class="accordion-item">
               <button class="accordion-header" onclick="toggleAccordion(this)">
                Apakah data aman?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Ya, kami menggunakan enkripsi standar industri dan backup data harian untuk menjamin keamanan data sekolah Anda.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- CTA -->
    <section class="section cta-section animate-on-scroll hide-on-mobile">
      <div class="container text-center">
        <div class="cta-box glass-card glow">
          <h2>Siap Modernisasi Perpustakaan Sekolah Anda?</h2>
          <p>Daftarkan sekolah Anda secara mandiri untuk memulai otomasi perpustakaan terintegrasi.</p>
          <div class="cta-buttons">
            <a href="register-school.php" class="btn btn-light btn-lg">
              <iconify-icon icon="solar:buildings-bold" style="margin-right: 6px;"></iconify-icon> Daftarkan Sekolah Baru
            </a>
            <a href="school.php" class="btn btn-outline-light btn-lg" style="margin-left: 10px; border-color: rgba(255,255,255,0.4); color: white;">
              Cari Sekolah Saya
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">Perpustakaan<span style="color:var(--accent-light);">Digital</span></div>
          <p>© 2026. Platform Manajemen Perpustakaan Sekolah Terintegrasi.</p>
        </div>
        <div class="footer-links">
          <h4>Jelajahi</h4>
          <a href="#features">Fitur</a>
          <a href="#audience">Solusi</a>
          <a href="#stats">Statistik</a>
          <a href="#testimonials">Testimoni</a>
        </div>
        <div class="footer-links">
          <h4>Layanan</h4>
          <a href="school.php">Cari Perpustakaan Sekolah</a>
          <a href="register-school.php">Daftarkan Sekolah Baru</a>
        </div>
        <div class="footer-links">
          <h4>Bantuan</h4>
          <a href="public/help.php">Pusat Bantuan</a>
          <a href="#">Kebijakan Privasi</a>
          <a href="#">Syarat & Ketentuan</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Quick School Search Modal -->
  <div id="quickSearchModal" class="modal" onclick="closeQuickSearchModal(event)" style="display: none;">
    <div class="modal-content glass-modal" onclick="event.stopPropagation()" style="max-width: 480px; padding: 28px;">
      <button class="modal-close" onclick="closeQuickSearchModal()">&times;</button>
      <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 56px; height: 56px; background: #EFF6FF; color: var(--primary); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 12px;">
          <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
        </div>
        <h3 style="font-size: 19px; font-weight: 700; margin-bottom: 6px;">Cari Perpustakaan Sekolah</h3>
        <p style="font-size: 13px; color: var(--text-muted);">Ketik nama sekolah Anda untuk langsung membuka portal login</p>
      </div>

      <div class="input-group" style="margin-bottom: 12px;">
        <div style="position: relative; display: flex; align-items: center;">
          <span style="position: absolute; left: 14px; font-size: 20px; color: #94A3B8;"><iconify-icon icon="solar:magnifer-linear"></iconify-icon></span>
          <input type="text" id="quickSchoolInput" placeholder="Ketik nama sekolah atau NPSN..." oninput="debounceQuickSearch()" style="width: 100%; padding: 13px 14px 13px 44px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 14px; font-family: inherit;">
        </div>
      </div>

      <div id="quickSearchResults" style="max-height: 240px; overflow-y: auto; border: 1px solid var(--border); border-radius: 12px; display: none;"></div>
      <div id="quickSearchNotice" style="margin-top: 12px; font-size: 12px; color: var(--text-muted); text-align: center;">Ketik minimal 2 karakter untuk mencari</div>
    </div>
  </div>

  <script>
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    const toggleIcon = navToggle.querySelector('iconify-icon');

    navToggle.addEventListener('click', () => {
      mainNav.classList.toggle('active');
      
      if (mainNav.classList.contains('active')) {
        toggleIcon.setAttribute('icon', 'solar:close-circle-linear');
        navToggle.style.transform = 'rotate(90deg)';
      } else {
        toggleIcon.setAttribute('icon', 'solar:hamburger-menu-linear');
        navToggle.style.transform = 'rotate(0)';
      }
    });

    document.querySelectorAll('.main-nav a').forEach(link => {
      link.addEventListener('click', () => {
        mainNav.classList.remove('active');
        toggleIcon.setAttribute('icon', 'solar:hamburger-menu-linear');
        navToggle.style.transform = 'rotate(0)';
      });
    });

    function openQuickSearchModal(e) {
      if(e) e.preventDefault();
      const modal = document.getElementById('quickSearchModal');
      modal.style.display = 'flex';
      modal.classList.add('active');
      document.getElementById('quickSchoolInput').focus();
    }

    function closeQuickSearchModal(e) {
      if(e && e.target !== e.currentTarget) return;
      const modal = document.getElementById('quickSearchModal');
      modal.style.display = 'none';
      modal.classList.remove('active');
    }

    let quickSearchTimeout = null;
    function debounceQuickSearch() {
      clearTimeout(quickSearchTimeout);
      quickSearchTimeout = setTimeout(executeQuickSearch, 300);
    }

    async function executeQuickSearch() {
      const query = document.getElementById('quickSchoolInput').value.trim();
      const resultsBox = document.getElementById('quickSearchResults');
      const notice = document.getElementById('quickSearchNotice');

      if (query.length < 2) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        notice.innerText = 'Ketik minimal 2 karakter untuk mencari.';
        return;
      }

      notice.innerText = 'Mencari sekolah...';

      try {
        const res = await fetch(`public/api/search-schools.php?q=${encodeURIComponent(query)}`);
        const data = await res.json();

        if (data.success && data.schools.length > 0) {
          resultsBox.innerHTML = data.schools.map(s => `
            <a href="${s.url}" style="padding: 12px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: inherit; transition: background 0.15s;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
              <div>
                <div style="font-weight: 700; font-size: 13.5px; color: var(--text-main); margin-bottom: 2px;">${escapeHtml(s.name)}</div>
                <div style="font-size: 11.5px; color: var(--text-muted);">NPSN: ${escapeHtml(s.npsn)}</div>
              </div>
              <iconify-icon icon="solar:arrow-right-linear" style="color: #64748B;"></iconify-icon>
            </a>
          `).join('');
          resultsBox.style.display = 'block';
          notice.innerText = `Ditemukan ${data.schools.length} sekolah.`;
        } else {
          resultsBox.style.display = 'none';
          resultsBox.innerHTML = '';
          notice.innerText = 'Sekolah tidak ditemukan.';
        }
      } catch (err) {
        notice.innerText = 'Gagal memuat hasil pencarian.';
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function toggleAccordion(btn) {
      const item = btn.parentElement;
      item.classList.toggle('active');
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if(entry.isIntersecting) {
           const counters = entry.target.querySelectorAll('.counter, .counter-percent');
           counters.forEach(counter => {
             const target = +counter.getAttribute('data-target');
             const duration = 2000;
             const step = target / (duration / 16);
             let current = 0;
             const update = () => {
               current += step;
               if(current < target) {
                 counter.innerText = Math.ceil(current) + (counter.classList.contains('counter-percent') ? '%' : '');
                 requestAnimationFrame(update);
               } else {
                 counter.innerText = target + (counter.classList.contains('counter-percent') ? '%' : '');
               }
             };
             update();
           });
           observer.unobserve(entry.target);
        }
      });
    });
    document.querySelector('.stats-section') && observer.observe(document.querySelector('.stats-section'));

    window.addEventListener('scroll', () => {
      const header = document.querySelector('.site-header');
      if(window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    });

    const scrollObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if(entry.isIntersecting) {
          entry.target.classList.add('is-visible');
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => scrollObserver.observe(el));
  </script>
</body>
</html>