<?php
/**
 * ============================================================
 * KOMPONEN BERSAMA: HEADER & LAYOUT
 * ============================================================
 * Sistem Helpdesk Pelayanan Publik Desa
 * Dikembangkan oleh: Benedict Saviola Pradana (2026)
 *
 * Template layout bersama untuk seluruh halaman dashboard.
 * Di-include di awal setiap halaman (index, pengaduan, pengaturan).
 *
 * Variabel yang HARUS di-set sebelum require:
 * - $currentPage  (string): 'dashboard' | 'pengaduan' | 'pengaturan'
 * - $pageTitle    (string): Judul halaman yang tampil di header
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Admin — Sistem Helpdesk Pelayanan Publik Desa">
    <title><?= htmlspecialchars($pageTitle) ?> — Helpdesk Desa</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                }
            }
        }
    }
    </script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        /* Gaya scrollbar custom */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        /* Indikator navigasi aktif di sidebar */
        .nav-active { position: relative; }
        .nav-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #2563eb;
            border-radius: 0 4px 4px 0;
        }

        /* Animasi toast notification */
        @keyframes toastMasuk {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastKeluar {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .toast-masuk { animation: toastMasuk 0.3s ease-out forwards; }
        .toast-keluar { animation: toastKeluar 0.3s ease-in forwards; }

        /* Animasi loading spinner */
        @keyframes putarLoading {
            to { transform: rotate(360deg); }
        }
        .putar-loading { animation: putarLoading 0.6s linear infinite; }
    </style>

    <script>
    // Dark Mode: terapkan sebelum render untuk mencegah flash
    if (localStorage.getItem('darkMode') === 'true' ||
        (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased min-h-screen">

<!-- ============================================ -->
<!-- SIDEBAR — Navigasi Utama                     -->
<!-- ============================================ -->
<aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col z-40 shadow-sm">

    <!-- Area Logo -->
    <div class="px-5 pt-6 pb-4">
        <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center mb-3 shadow-lg shadow-blue-600/20">
            <i class="ph ph-buildings text-white text-xl"></i>
        </div>
        <h2 class="text-blue-600 dark:text-blue-400 font-bold text-sm tracking-wide">Village Helpdesk</h2>
        <p class="text-gray-400 dark:text-gray-500 text-xs mt-0.5">Admin Dashboard</p>
    </div>

    <!-- Menu Navigasi -->
    <nav class="flex-1 px-3 mt-2">
        <a href="index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-all duration-200
           <?= $currentPage === 'dashboard'
               ? 'nav-active bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
               : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-700 dark:hover:text-gray-200' ?>">
            <i class="ph ph-squares-four text-lg"></i>
            Dashboard
        </a>

        <a href="pengaduan.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-all duration-200
           <?= $currentPage === 'pengaduan'
               ? 'nav-active bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
               : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-700 dark:hover:text-gray-200' ?>">
            <i class="ph ph-chat-circle-dots text-lg"></i>
            Data Pengaduan
        </a>

        <a href="pengaturan.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-all duration-200
           <?= $currentPage === 'pengaturan'
               ? 'nav-active bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
               : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-700 dark:hover:text-gray-200' ?>">
            <i class="ph ph-gear text-lg"></i>
            Pengaturan
        </a>
    </nav>

    <!-- Bagian Bawah: Dark Mode + Versi -->
    <div class="px-3 pb-4 border-t border-gray-100 dark:border-gray-700 pt-3">
        <!-- Tombol Dark Mode -->
        <button onclick="toggleDarkMode()" id="tombolDarkMode"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium w-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-200">
            <i class="ph ph-moon text-lg dark:hidden"></i>
            <i class="ph ph-sun text-lg hidden dark:inline-flex"></i>
            <span class="dark:hidden">Dark Mode</span>
            <span class="hidden dark:inline">Light Mode</span>
        </button>

        <!-- Info Versi -->
        <div class="flex items-center gap-3 px-3 py-2 text-gray-400 dark:text-gray-600">
            <i class="ph ph-code text-lg"></i>
            <span class="text-xs">v1.0 — MVP</span>
        </div>
    </div>
</aside>

<!-- ============================================ -->
<!-- WRAPPER KONTEN UTAMA                         -->
<!-- ============================================ -->
<main class="ml-64 min-h-screen">

    <!-- Header / Top Bar -->
    <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5" id="tanggal-header"></p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Tombol Export CSV -->
                <a href="export_csv.php" id="tombol-export-csv"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm shadow-blue-600/20 transition-all duration-200 hover:shadow-md no-underline">
                    <i class="ph ph-download-simple text-base"></i>
                    Export CSV
                </a>
                <!-- Admin Info + Logout -->
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-600">
                        <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <i class="ph ph-user text-blue-600 dark:text-blue-400 text-xs"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($adminUsername ?? 'Admin') ?></span>
                    </div>
                    <a href="logout.php" title="Keluar dari dashboard"
                       class="w-9 h-9 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg flex items-center justify-center transition-colors duration-200 no-underline">
                        <i class="ph ph-sign-out text-red-500 dark:text-red-400 text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Awal Area Konten Halaman -->
    <div class="p-6">

    <!-- Toast Notification Container -->
    <div id="wadah-toast" class="fixed top-6 right-6 z-[60] flex flex-col gap-3"></div>
