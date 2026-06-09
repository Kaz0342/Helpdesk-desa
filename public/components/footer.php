    </div><!-- Akhir area konten halaman (.p-6) -->
</main><!-- Akhir wrapper konten utama -->

<!-- ============================================ -->
<!-- SKRIP BERSAMA — Digunakan di Semua Halaman  -->
<!-- ============================================ -->
<script>
/**
 * Menampilkan tanggal hari ini di header.
 * Format: "Rabu, 28 Mei 2026"
 */
const opsiTanggal = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
const elTanggal = document.getElementById('tanggal-header');
if (elTanggal) {
    elTanggal.textContent = new Date().toLocaleDateString('id-ID', opsiTanggal);
}

/**
 * Toggle Dark Mode — simpan preferensi di localStorage.
 */
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    const isDark = document.documentElement.classList.contains('dark');
    localStorage.setItem('darkMode', isDark);
}

/**
 * Toast Notification — pengganti alert() yang lebih elegan.
 *
 * @param {string} pesan  Isi pesan notifikasi
 * @param {string} tipe   'sukses' | 'error' | 'warning' | 'info'
 */
function tampilkanToast(pesan, tipe = 'sukses') {
    const wadah = document.getElementById('wadah-toast');
    const warna = {
        sukses:  'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300',
        error:   'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300',
        warning: 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300',
        info:    'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300',
    };
    const ikon = {
        sukses:  'ph-check-circle',
        error:   'ph-warning-circle',
        warning: 'ph-warning',
        info:    'ph-info',
    };

    const toast = document.createElement('div');
    toast.className = 'flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg ' + (warna[tipe] || warna.info) + ' toast-masuk';
    toast.innerHTML = '<i class="ph ' + (ikon[tipe] || ikon.info) + ' text-xl"></i>' +
        '<p class="text-sm font-medium flex-1">' + pesan + '</p>' +
        '<button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100"><i class="ph ph-x text-lg"></i></button>';

    wadah.appendChild(toast);

    // Hapus toast otomatis setelah 4 detik
    setTimeout(() => {
        toast.classList.remove('toast-masuk');
        toast.classList.add('toast-keluar');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Custom Confirm Modal — pengganti confirm() bawaan browser.
 *
 * @param {string}   pesan      Pertanyaan konfirmasi
 * @param {function} onKonfirmasi Callback jika user menekan "Ya"
 */
function tampilkanKonfirmasi(pesan, onKonfirmasi) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[998] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity duration-300';
    overlay.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 transform scale-95 transition-transform duration-300">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                    <i class="ph ph-warning text-amber-600 dark:text-amber-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Konfirmasi</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-6">${pesan}</p>
            <div class="flex gap-3 justify-end">
                <button id="konfirmasiBatal" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">Batal</button>
                <button id="konfirmasiOk" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors">Ya, Lanjutkan</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    requestAnimationFrame(() => {
        overlay.classList.remove('opacity-0');
        overlay.querySelector('div').classList.remove('scale-95');
    });

    const tutup = () => {
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.remove(), 300);
    };

    overlay.querySelector('#konfirmasiBatal').addEventListener('click', tutup);
    overlay.querySelector('#konfirmasiOk').addEventListener('click', () => {
        tutup();
        onKonfirmasi();
    });
    overlay.addEventListener('click', (e) => { if (e.target === overlay) tutup(); });
}
</script>

<!--
============================================================
IDENTIFIKASI HAK KEKAYAAN INTELEKTUAL (HKI)
============================================================
Pengembang : Benedict Saviola Pradana
Institusi  : Universitas Atma Jaya Yogyakarta
Tahun      : 2026
============================================================
-->
</body>
</html>
