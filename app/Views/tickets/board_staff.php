<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $unitLevelId = session()->get('unit_level_id'); ?>
<?php $unitUsahaId = session()->get('unit_usaha_id'); ?>

<div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">



    <h2 class="text-3xl font-bold mb-6 text-blue-900 border-b border-blue-300 pb-2 select-none">Daftar Tiket Unit Saya
    </h2>

    <div class="flex items-center gap-4 mb-4 flex-wrap">
        <!-- Pencarian -->
        <div>
            <label for="searchInput" class="sr-only">Cari Tiket</label>
            <input type="text" id="searchInput" placeholder="Cari tiket..." class="border px-3 py-2 rounded w-64">
        </div>
    </div>


    <!-- Tabs Status + Filter Unit Usaha -->
    <div class="flex justify-between items-center mb-6 border-b border-gray-300 flex-wrap gap-4">

        <!-- Tabs Status -->
        <div class="flex space-x-4">
            <?php
            $statuses = ['Open', 'In Progress', 'Done', 'Closed'];
            ?>
            <?php foreach ($statuses as $i => $status): ?>
                <button class="status-tab px-4 py-2 font-semibold rounded-t-lg cursor-pointer
                    <?= $i === 0 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                    data-status="<?= $status ?>">
                    <?= $status ?>
                </button>
            <?php endforeach ?>
        </div>

        <div class="flex space-x-4 items-center mt-4">
            <!-- Filter Tanggal -->
            <div id="filterTanggal" class="flex items-center gap-2 hidden">
                <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                    class="border rounded px-3 py-2 text-base w-44" />
                <input type="hidden" name="tanggal_selesai" id="tanggal_selesai" />
                <button id="resetTanggalBtn" class="bg-blue-500 text-white p-2 rounded-md shadow hover:bg-blue-600"
                    title="Reset Tanggal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582M20 20v-5h-.582M3.582 9A9 9 0 0112 3v0a9 9 0 018.418 6M20.418 15A9 9 0 0112 21v0a9 9 0 01-8.418-6" />
                    </svg>
                </button>
            </div>
            <!-- Dropdown Urutan Tiket -->
            <div id="filterUrutanWrapper" class="relative inline-block hidden">
                <label for="filterUrutan" class="sr-only">Urutkan Tiket</label>
                <select id="filterUrutan"
                    class="border rounded px-3 py-2 pl-8 text-base text-gray-800 bg-white focus:outline-none focus:ring focus:ring-blue-300 min-w-[100px]">
                    <option value="asc" selected>Terlama</option>
                    <option value="desc">Terbaru</option>
                </select>

                <!-- Ikon waktu -->
                <div class="absolute left-3 inset-y-0 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                    </svg>
                </div>
            </div>
        </div>


    </div>


    <!-- Card Container -->
    <div id="ticketsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 min-h-[200px]">
        <!-- Cards akan muncul di sini -->
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="hidden text-center text-gray-500 mt-4">Memuat tiket...</div>
    <div id="noTicketsMessage" class="hidden text-center text-gray-500 mt-4">Tidak ada tiket untuk status ini.</div>
</div>

<div id="ticketDetailModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-5xl w-full max-h-[90vh] overflow-y-auto relative">
        <button id="closeModal"
            class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-3xl font-bold leading-none">&times;</button>
        <h3 class="text-2xl font-semibold mb-4 text-blue-900 select-none px-6 pt-6">Detail Tiket</h3>
        <div id="ticketDetails" class="px-6 pb-6 text-gray-800 text-sm space-y-4">
            <!-- Detail tiket akan dimuat disini -->
        </div>
    </div>
</div>

<!-- Modal Ambil Tiket (Staff) -->
<div id="takeTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full max-h-[90vh] overflow-y-auto relative p-6">
        <button id="closeTakeTicketModal"
            class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-3xl font-bold leading-none">&times;</button>
        <h3 class="text-2xl font-semibold mb-6 text-blue-900 select-none">Ambil Tiket</h3>

        <form id="takeTicketForm">
            <input type="hidden" name="id_tiket" id="take_id_tiket" />

            <label for="status" class="block font-semibold mb-1">Status</label>
            <select id="status" name="status" required class="w-full border rounded px-3 py-2 mb-4">
                <option value="In Progress" selected>In Progress</option>
                <option value="Done">Done</option>
            </select>

            <label for="prioritas" class="block font-semibold mb-1">Prioritas</label>
            <select id="prioritas" name="prioritas" required class="w-full border rounded px-3 py-2 mb-4">
                <option value="" disabled selected hidden>-- Pilih Prioritas --</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select>

            <label for="komentar_staff" class="block font-semibold mb-1">Komentar Staff</label>
            <textarea id="komentar_staff" name="komentar_staff" rows="4" placeholder="Masukkan komentar..."
                class="w-full border rounded px-3 py-2 mb-6"></textarea>

            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelTakeTicket"
                    class="px-5 py-2 rounded border border-gray-300 hover:bg-gray-100 transition">Batal</button>
                <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition duration-200">Ambil
                    Tiket</button>
            </div>
        </form>

        <div id="loading" class="hidden fixed inset-0 bg-gray-500 bg-opacity-50 flex items-center justify-center z-50">
            <div class="text-center flex flex-col items-center">
                <div class="animate-spin rounded-full border-t-4 border-blue-600 h-16 w-16 mb-4"></div>
                <p class="text-white">Sedang memproses...</p>
            </div>
        </div>
    </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>



<script>
    $(function () {
        const $ticketsContainer = $('#ticketsContainer');
        const $loading = $('#loadingSpinner');
        const $noTickets = $('#noTicketsMessage');
        const $filterTanggal = $('#filterTanggal');
        const tanggalMulaiInput = document.getElementById('tanggal_mulai');
        const tanggalSelesaiInput = document.getElementById('tanggal_selesai');
        const $searchInput = $('#searchInput');
        const $filterUrutan = $('#filterUrutan');
        const resetTanggalBtn = $('#resetTanggalBtn');

        let currentStatus = 'Open';

        // Mapping tanggal Indonesia ke ISO
        function convertTanggalIndonesiaToISO(tanggalIndo) {
            const parts = tanggalIndo.split('/');
            if (parts.length !== 3) return '';
            return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        }

        // Tampilkan/hidden filter berdasarkan status
        function updateFilterVisibility(status) {
            // Filter urutan tampil di semua status
            $('#filterUrutanWrapper').removeClass('hidden');

            // Filter tanggal hanya untuk Done & Closed
            if (status === 'Done' || status === 'Closed') {
                $('#filterTanggal').removeClass('hidden');
            } else {
                $('#filterTanggal').addClass('hidden');
            }
        }


        // Render setiap kartu tiket
        function renderTicketCard(ticket) {
            const assignedText = ticket.assigned_nama ? `: ${ticket.assigned_nama}` : ': Belum ditugaskan';

            const statusColors = {
                'Open': 'bg-green-100 text-green-800',
                'In Progress': 'bg-yellow-100 text-yellow-800',
                'Done': 'bg-blue-100 text-blue-800',
                'Closed': 'bg-gray-100 text-gray-600'
            };

            const priorityColors = {
                'High': 'text-red-600',
                'Medium': 'text-yellow-600',
                'Low': 'text-green-600'
            };

            const statusColor = statusColors[ticket.status] || 'bg-gray-100 text-gray-600';
            const priorityColor = priorityColors[ticket.prioritas] || 'text-gray-700';

            // Ambil ID pegawai dari session (harus PG_xx agar cocok dengan ticket.assigned_to)
            const sessionId = "<?= session()->get('id_pegawai') ?>";

            let btns = `
        <button class="detail-btn bg-blue-600 text-white px-3 py-1 rounded mr-2 hover:bg-blue-700 transition" data-id="${ticket.id_tiket}">Detail</button>
        <a href="<?= base_url('tickets/printpdf/') ?>${ticket.id_tiket}" target="_blank" class="bg-red-600 text-white px-3 py-1 rounded mr-2 hover:bg-red-700 transition">PDF</a>
    `;

            // Kondisi tombol aksi
            if (!ticket.assigned_to && ticket.can_take) {
                // Belum ditugaskan dan bisa diambil
                btns += `<button class="take-ticket-btn bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition" data-id="${ticket.id_tiket}">Ambil Tiket</button>`;
            } else if (ticket.assigned_to === sessionId && ticket.status === 'In Progress') {
                // Tiket sedang dikerjakan oleh user ini
                btns += `<button class="finish-ticket-btn bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition" data-id="${ticket.id_tiket}">Selesai</button>`;
            } else if (ticket.assigned_to && ticket.assigned_to !== sessionId) {
                // Tiket diambil oleh orang lain
                btns += `<span class="text-gray-600 italic">Diambil orang lain</span>`;
            }

            return `
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200 flex flex-col justify-between mb-4">
            <div>
                <h4 class="text-lg font-bold mb-2 text-blue-900">${ticket.judul}</h4>
                <p class="mb-1"><span class="font-semibold">ID Tiket:</span> ${ticket.id_tiket}</p>
                <p class="mb-1"><span class="font-semibold">Prioritas:</span> <span class="${priorityColor}">${ticket.prioritas}</span></p>
                <p class="mb-1"><span class="font-semibold">Status:</span> <span class="px-2 py-1 rounded ${statusColor} text-xs font-semibold">${ticket.status}</span></p>
                <p class="mb-1"><span class="font-semibold">Requestor:</span> ${ticket.requestor_nama}</p>
                <p class="mb-1"><span class="font-semibold">Unit Level:</span> ${ticket.nm_unit_level}</p>
                <p class="mb-1"><span class="font-semibold">Unit Usaha:</span> ${ticket.nm_unit_usaha}</p>
                <p class="mb-1"><span class="font-semibold">Ditugaskan kepada</span>${assignedText}</p>
                <p class="mb-1"><span class="font-semibold">Tanggal Dibuat:</span> ${ticket.created_at}</p>
            </div>
            <div class="mt-3 flex flex-wrap items-center">${btns}</div>
        </div>
    `;
        }


        function loadTickets(status) {
            updateFilterVisibility(status);
            currentStatus = status;

            $loading.show();
            $noTickets.hide();
            $ticketsContainer.empty();

            const tanggalMulai = tanggalMulaiInput.value;
            const tanggalSelesai = tanggalSelesaiInput.value;

            const selectedUrutan = $('#filterUrutan').val(); 
            const defaultUrutan = (status === 'Open' || status === 'In Progress') ? 'asc' : 'desc';
            const urutanToSend = selectedUrutan || defaultUrutan;

            let url = `<?= base_url('tickets/list-for-unit') ?>?status=${encodeURIComponent(status)}&order=${encodeURIComponent(urutanToSend)}`;

            if (tanggalMulai) {
                url += `&tanggal_mulai=${encodeURIComponent(tanggalMulai)}`;
            }
            if (tanggalSelesai) {
                url += `&tanggal_selesai=${encodeURIComponent(tanggalSelesai)}`;
            }

            fetch(url)
                .then(res => res.json())
                .then(response => {
                    const data = response.data;
                    $loading.hide();

                    if (!data || data.length === 0) {
                        $noTickets.show();
                        return;
                    }

                    data.forEach(ticket => {
                        $ticketsContainer.append(renderTicketCard(ticket));
                    });

                    if (data.length > 0) {
                        const lastDateRaw = data[data.length - 1]?.created_at;
                        if (lastDateRaw) {
                            const isoDate = convertTanggalIndonesiaToISO(lastDateRaw);
                            tanggalSelesaiInput.value = isoDate;
                        }
                    }
                })
                .catch(error => {
                    $loading.hide();
                    console.error('Gagal memuat tiket:', error);
                });
        }



        function debounce(func, delay) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        function ratingTimeText(rating) {
            switch (parseInt(rating)) {
                case 1:
                    return "Sangat Lambat";
                case 2:
                    return "Lambat";
                case 3:
                    return "Cukup";
                case 4:
                    return "Cepat";
                case 5:
                    return "Sangat Cepat";
                default:
                    return "-";
            }
        }

        function ratingServiceText(rating) {
            switch (parseInt(rating)) {
                case 1:
                    return "Sangat Buruk";
                case 2:
                    return "Buruk";
                case 3:
                    return "Cukup";
                case 4:
                    return "Baik";
                case 5:
                    return "Sangat Baik";
                default:
                    return "-";
            }
        }

        // Event Tab Status
        $('.status-tab').click(function () {
            $('.status-tab').removeClass('bg-blue-600 text-white').addClass('bg-gray-100 text-gray-700 hover:bg-gray-200');
            $(this).addClass('bg-blue-600 text-white').removeClass('bg-gray-100 text-gray-700 hover:bg-gray-200');
            const selectedStatus = $(this).data('status');
            loadTickets(selectedStatus);
        });

        tanggalMulaiInput.addEventListener('change', function () {
            const tanggalMulai = tanggalMulaiInput.value;
            loadTickets(currentStatus, tanggalMulai);
        });

        resetTanggalBtn.on('click', function () {
            tanggalMulaiInput.value = '';
            tanggalSelesaiInput.value = '';
            loadTickets(currentStatus);
        });

        // Debounce pencarian
        $searchInput.on('input', debounce(function () {
            loadTickets(currentStatus);
        }, 300));

        // Modal Ambil Tiket
        const $modal = $('#takeTicketModal');
        const $form = $('#takeTicketForm');

        $ticketsContainer.on('click', '.take-ticket-btn', function () {
            const idTiket = $(this).data('id');
            $('#take_id_tiket').val(idTiket);
            $form[0].reset();
            $modal.removeClass('hidden');
        });

        $('#closeTakeTicketModal, #cancelTakeTicket').on('click', () => {
            $modal.addClass('hidden');
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            $('#loading').removeClass('hidden');

            const status = $('#status').val();
            const komentar = $('#komentar_staff').val().trim();

            if (status === 'Done' && komentar.length === 0) {
                $('#loading').addClass('hidden');
                Swal.fire({
                    icon: 'warning',
                    title: 'Komentar wajib diisi',
                    text: 'Silakan isi komentar staff saat memilih status Done.'
                });
                return;
            }

            const formData = $(this).serialize();
            $.post("<?= base_url('tickets/take') ?>", formData, function (response) {
                $('#loading').addClass('hidden');
                Swal.fire({
                    icon: response.status === 'success' ? 'success' : 'error',
                    title: response.status === 'success' ? 'Berhasil!' : 'Gagal!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                if (response.status === 'success') {
                    $modal.addClass('hidden');
                    loadTickets(currentStatus);
                }
            }, 'json').fail(() => {
                $('#loading').addClass('hidden');
                Swal.fire('Error', 'Terjadi kesalahan saat mengambil tiket', 'error');
            });
        });

        // Selesai Tiket
        $ticketsContainer.on('click', '.finish-ticket-btn', function () {
            const idTiket = $(this).data('id');

            Swal.fire({
                title: 'Apakah tiket sudah selesai dikerjakan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, selesai',
                cancelButtonText: 'Belum',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.close();
                    setTimeout(() => {
                        $('#loading').removeClass('hidden');

                        $.post("<?= base_url('tickets/finish') ?>", { id_tiket: idTiket }, function (response) {
                            $('#loading').addClass('hidden');
                            Swal.fire({
                                icon: response.status === 'success' ? 'success' : 'error',
                                title: response.status === 'success' ? 'Berhasil!' : 'Gagal!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            if (response.status === 'success') {
                                loadTickets(currentStatus);
                            }
                        }, 'json').fail(() => {
                            $('#loading').addClass('hidden');
                            Swal.fire('Error', 'Terjadi kesalahan saat menyelesaikan tiket', 'error');
                        });
                    }, 300);
                }
            });
        });

        $ticketsContainer.on('click', '.detail-btn', function () {
            const idTiket = $(this).data('id');
            $.getJSON(`<?= base_url('tickets/detail') ?>/${idTiket}`, function (response) {
                if (response.status === 'success') {
                    const data = response.data;

                    function formatDuration(seconds) {
                        if (seconds === 0) return '0 detik';           // Menampilkan 0 detik
                        if (!seconds || seconds < 0) return '-';       // Handle negatif/null

                        const d = Math.floor(seconds / 86400);         // Hari
                        const h = Math.floor((seconds % 86400) / 3600); // Jam
                        const m = Math.floor((seconds % 3600) / 60);    // Menit
                        const s = seconds % 60;                         // Detik

                        let str = '';
                        if (d) str += `${d} hari `;
                        if (h) str += `${h} jam `;
                        if (m) str += `${m} mnt `;
                        if (s) str += `${s} dtk`;

                        return str.trim(); // Hilangkan spasi di akhir
                    }


                    // Ambil last assignee (petugas terakhir)
                    const lastAssignee = data.assignees && data.assignees.length > 0 ? data.assignees[data.assignees.length - 1] : null;
                    let waktuPengerjaanHtml = '';

                    if (data.status === 'Open') {
                        // Belum dikonfirmasi atau belum diproses, tampilkan tanda "-"
                        waktuPengerjaanHtml = `
        <h4 class="font-semibold mb-2 text-blue-900">Waktu Pengerjaan</h4>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Tiket diproses</div><div class="px-1">:</div><div class="text-gray-800">-</div></div>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Tiket selesai</div><div class="px-1">:</div><div class="text-gray-800">-</div></div>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Durasi pengerjaan</div><div class="px-1">:</div><div class="text-gray-800">-</div></div>
    `;
                    } else if (lastAssignee) {
                        let assignedAt = lastAssignee.assigned_at ? dayjs(lastAssignee.assigned_at).format('D MMM YYYY, HH:mm') : '-';
                        let finishedAt = lastAssignee.finished_at ? dayjs(lastAssignee.finished_at).format('D MMM YYYY, HH:mm') : '-';
                        let durasi = '-';

                        if (lastAssignee.assigned_at && lastAssignee.finished_at) {
                            let dur = dayjs(lastAssignee.finished_at).diff(dayjs(lastAssignee.assigned_at), 'second');
                            durasi = formatDuration(dur);
                        }

                        waktuPengerjaanHtml = `
        <h4 class="font-semibold mb-2 text-blue-900">Waktu Pengerjaan</h4>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Tiket diproses</div><div class="px-1">:</div><div class="text-gray-800">${assignedAt}</div></div>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Tiket selesai</div><div class="px-1">:</div><div class="text-gray-800">${finishedAt}</div></div>
        <div class="flex"><div class="w-32 font-semibold text-gray-700">Durasi pengerjaan</div><div class="px-1">:</div><div class="text-gray-800">${durasi}</div></div>
    `;
                    }


                    const assigneesHtml = data.assignees?.length > 0 ? `
                <h4 class="font-semibold mb-2 text-blue-900">Histori Petugas</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full border text-xs text-left mb-3">
                        <thead>
                            <tr>
                                <th class="py-2 px-3 border-b text-center">No</th>
                                <th class="py-2 px-3 border-b text-center">Nama Petugas</th>
                                <th class="py-2 px-3 border-b text-center">Telepon</th>
                                <th class="py-2 px-3 border-b text-center">Waktu Mulai</th>
                                <th class="py-2 px-3 border-b text-center">Waktu Selesai</th>
                                <th class="py-2 px-3 border-b text-center">Durasi Pengerjaan</th>
                                <th class="py-2 px-3 border-b text-center">Komentar Petugas</th>
                                <th class="py-2 px-3 border-b text-center">Komentar Feedback</th>
                                <th class="py-2 px-3 border-b text-center">Rating Waktu</th>
                                <th class="py-2 px-3 border-b text-center">Rating Layanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.assignees.map((a, idx) => {
                        const start = a.assigned_at ? dayjs(a.assigned_at) : null;
                        const end = a.finished_at ? dayjs(a.finished_at) : null;
                        const durationText = (start && end) ? (() => {
                            const diffInSeconds = end.diff(start, 'second');
                            if (diffInSeconds === 0) return `0 detik`;
                            if (diffInSeconds < 60) {
                                return `${diffInSeconds} detik`;
                            } else if (diffInSeconds < 3600) {
                                const minutes = Math.floor(diffInSeconds / 60);
                                const seconds = diffInSeconds % 60;
                                return `${minutes} mnt${seconds > 0 ? ` ${seconds} dtk` : ''}`;
                            } else {
                                const hours = Math.floor(diffInSeconds / 3600);
                                const minutes = Math.floor((diffInSeconds % 3600) / 60);
                                return `${hours} jam${minutes > 0 ? ` ${minutes} mnt` : ''}`;
                            }
                        })() : '-';
                        return `
                                    <tr>
                                        <td class="py-2 px-3 border-b">${a.sequence}</td>
                                        <td class="py-2 px-3 border-b">${a.assigned_nama || '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.assigned_telpon1 || '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.assigned_at ? dayjs(a.assigned_at).format('DD MMM YYYY HH:mm') : '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.finished_at ? dayjs(a.finished_at).format('DD MMM YYYY HH:mm') : '-'}</td>
                                        <td class="py-2 px-3 border-b">${durationText}</td>
                                        <td class="py-2 px-3 border-b">${a.komentar_staff || data.komentar_staff || '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.komentar_penyelesaian || '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.rating_time ? ratingTimeText(a.rating_time) : '-'}</td>
                                        <td class="py-2 px-3 border-b">${a.rating_service ? ratingServiceText(a.rating_service) : '-'}</td>
                                    </tr>`;
                    }).join('')}
                        </tbody>
                    </table>
                </div>
            ` : `<p class="italic text-gray-500">Belum ada histori petugas.</p>`;

                    const imgHtml = data.gambar ? `
                <a href="<?= base_url('uploads/') ?>${encodeURIComponent(data.gambar)}" target="_blank">
                    <img src="<?= base_url('uploads/') ?>${encodeURIComponent(data.gambar)}"
                        alt="Gambar Tiket"
                        class="w-full max-h-72 object-contain rounded-lg mb-6 border border-gray-300 hover:opacity-80 transition duration-200" />
                </a>` : '<p class="italic text-gray-500">Tidak ada gambar.</p>';

                    const statusColor = data.status === 'Closed' ? 'gray' : data.status === 'Done' ? 'blue' : 'yellow';

                    let html = `
                ${imgHtml}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-4">
                    <div>
                        <h4 class="font-semibold mb-2 text-blue-900">Informasi Requestor & Penempatan</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Dibuat oleh</div><div class="px-1">:</div><div class="text-gray-800">${data.requestor_nama}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Telepon</div><div class="px-1">:</div><div class="text-gray-800">${data.requestor_telpon1 || '-'}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Email</div><div class="px-1">:</div><div class="text-gray-800">${data.requestor_email || '-'}</div></div>
                        </div>
                        <p class="font-semibold text-blue-900 mt-4 mb-2">Penempatan</p>
                        <div class="space-y-1 text-sm">
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Level</div><div class="px-1">:</div><div class="text-gray-800">${data.req_penempatan.unit_level}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Usaha</div><div class="px-1">:</div><div class="text-gray-800">${data.req_penempatan.unit_usaha}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Kerja</div><div class="px-1">:</div><div class="text-gray-800">${data.req_penempatan.unit_kerja}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Sub Kerja</div><div class="px-1">:</div><div class="text-gray-800">${data.req_penempatan.unit_kerja_sub}</div></div>
                            <div class="flex"><div class="w-20 font-semibold text-gray-700">Lokasi</div><div class="px-1">:</div><div class="text-gray-800">${data.req_penempatan.unit_lokasi}</div></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2 text-blue-900">Informasi Tiket</h4>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">ID</div><div class="px-1">:</div><div class="text-gray-800">${data.id_tiket}</div></div>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Kategori</div><div class="px-1">:</div><div class="text-gray-800">${data.kategori}</div></div>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Sub Kategori</div><div class="px-1">:</div><div class="text-gray-800">${data.subkategori}</div></div>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Judul</div><div class="px-1">:</div><div class="text-gray-800">${data.judul}</div></div>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Deskripsi</div><div class="px-1">:</div></div>
                        <div class="prose max-w-none text-gray-800">${data.deskripsi}</div>
                        <br>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Prioritas</div><div class="px-1">:</div><div class="text-${data.prioritas.toLowerCase()}-600 font-semibold">${data.prioritas}</div></div>
                        <div class="flex"><div class="w-28 font-semibold text-gray-700">Status</div><div class="px-1">:</div>
                            <div><span class="inline-block px-2 py-1 rounded bg-${statusColor}-100 text-${statusColor}-600 text-xs font-semibold">${data.status}</span></div>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-300 my-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="font-semibold mb-2 text-blue-900">Penugasan</h4>
                        <div class="flex"><div class="w-32 font-semibold text-gray-700">Ditugaskan kepada</div><div class="px-1">:</div><div class="text-gray-800">${data.assigned_nama || '-'}</div></div>
                        <div class="flex"><div class="w-32 font-semibold text-gray-700">Telepon</div><div class="px-1">:</div><div class="text-gray-800">${data.assigned_telpon1 || '-'}</div></div>
                    </div>
                    <div>${waktuPengerjaanHtml}</div>
                    <div>
                        <h4 class="font-semibold mb-2 text-blue-900">Komentar & Rating</h4>
                        <p><strong>Komentar Penyelesaian:</strong></p>
                        <p class="italic text-gray-600 mb-3">${data.komentar_penyelesaian || 'Tidak ada komentar.'}</p>
                        <p><strong>Rating Waktu:</strong> ${data.rating_time ? ratingTimeText(data.rating_time) : '-'}</p>
                        <p><strong>Rating Layanan:</strong> ${data.rating_service ? ratingServiceText(data.rating_service) : '-'}</p>
                    </div>
                </div>

                <div class="mt-6">${assigneesHtml}</div>
                <p class="text-right text-sm text-gray-500 mt-6">Dibuat pada: ${data.created_at}</p>
            `;

                    const printBtn = `
                    <div class="text-right mt-4">
                        <a href="<?= base_url('tickets/printpdf') ?>/${idTiket}" target="_blank"
                            class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                            🖨️ Cetak Tiket (PDF)
                        </a>
                    </div>`;
                    html += printBtn;

                    $('#ticketDetails').html(html);
                    $('#ticketDetailModal').removeClass('hidden');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Gagal memuat detail tiket',
                    });
                }
            });
        });

        // Close modal detail
        $('#closeModal').on('click', () => {
            $('#ticketDetailModal').addClass('hidden');
            $('#ticketDetails').html('');
        });

        $('#filterUrutan').on('change', function () {
            loadTickets(currentStatus);
        });

        $('#searchInput').on('input', function () {
            loadTickets(currentStatus);
        });

        // Load awal
        loadTickets(currentStatus);
        updateFilterVisibility(currentStatus);
    });
</script>


<?= $this->endSection() ?>