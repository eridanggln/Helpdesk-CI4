<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $unitKerjaSubId = session()->get('unit_kerja_sub_id'); ?>

<div class="max-w-7xl mx-auto bg-white p-6 rounded shadow mb-8">
    <div class="bg-white rounded-2xl p-6 shadow mb-10">
        <h2 class="text-xl font-semibold mb-4">Statistik Tiket Tahun <?= date('Y') ?></h2>

        <!-- Grid 2 Kolom -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tiket Masuk per Bulan -->
            <div class="w-[500px] h-[300px]">
                <h3 class="font-semibold mb-2 text-gray-700">Tiket Masuk per Bulan</h3>
                <canvas id="monthlyChart" class="w-full h-full"></canvas>
            </div>

            <!-- Tiket per Status (Pie Chart) -->
            <div class="w-[500px] h-[300px]">
                <h3 class="font-semibold mb-2 text-gray-700">Status Tiket Bulan <?= date('F Y') ?></h3>
                <div class="flex items-center justify-center space-x-6">
                    <!-- PIE CHART -->
                    <div class="w-[200px] h-[200px]">
                        <canvas id="statusPieChart" class="w-full h-full"></canvas>
                    </div>
                    <!-- LABEL -->
                    <div class="text-sm text-gray-700 space-y-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                            <span>Open</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                            <span>In Progress</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            <span>Done</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 bg-gray-500 rounded-full"></span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tiket per Kategori (Bar Chart) -->
        <div class="mt-10 w-full">
            <h3 class="font-semibold mb-2 text-gray-700">Tiket Berdasarkan Kategori dan Sub Kategori -
                <?= date('F Y') ?>
            </h3>

            <div class="w-full h-[300px]">
                <canvas id="categoryChart" class="w-full h-full"></canvas>
            </div>

            <!-- Legend dan Statistik -->
            <div id="categoryLegend" class="mt-6 p-6 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Legend -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Keterangan Sub Kategori:</h4>
                        <div id="legendContent" class="grid grid-cols-3 gap-4 min-w-0 break-words px-6">
                            <!-- Akan diisi oleh JavaScript -->
                        </div>

                        <!-- Statistik Ringkasan -->
                        <div id="summaryStats" class="space-y-1 text-sm text-gray-700">
                            <!-- Akan diisi oleh JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="grid grid-cols-2 gap-6 mb-10">
        <div class="bg-green-100 rounded-xl p-6 shadow text-center">
            <h3 class="text-sm text-green-800 font-semibold">Rata-Rata Rating Waktu</h3>
            <p class="text-4xl font-bold text-green-900 mt-2"><?= esc($avgTime) ?></p>
        </div>
        <div class="bg-yellow-100 rounded-xl p-6 shadow text-center">
            <h3 class="text-sm text-yellow-800 font-semibold">Rata-Rata Rating Layanan</h3>
            <p class="text-4xl font-bold text-yellow-900 mt-2"><?= esc($avgService) ?></p>
        </div>
    </div>


    <h1 class="text-2xl font-semibold mb-6 text-blue-900">Laporan Tiket</h1>

    <!-- Filter & Cetak -->
    <div class="bg-white rounded-2xl p-6 shadow mb-8">
        <form action="<?= base_url('tickets/print-report-pdf') ?>" method="get"
            class="flex flex-wrap gap-4 items-end justify-between">
            <div class="flex flex-wrap gap-4">

                <!-- STATUS -->
                <div class="relative inline-block text-left mb-4">
                    <label class="block font-semibold mb-1 text-sm text-gray-700">Status</label>
                    <div>
                        <button type="button" onclick="toggleDropdown('statusDropdown')"
                            class="dropdown-btn inline-flex justify-between items-center px-4 py-2 w-48 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 text-sm">
                            Pilih Status
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="statusDropdown"
                            class="dropdown absolute hidden z-10 bg-white border border-gray-300 rounded-md mt-1 shadow-md w-48 max-h-48 overflow-y-auto">
                            <label
                                class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm font-medium text-blue-700">
                                <input type="checkbox" onclick="toggleAllStatus(this)" class="mr-2"> Pilih Semua
                            </label>
                            <?php $statusList = ['Open', 'In Progress', 'Closed']; ?>
                            <?php foreach ($statusList as $s): ?>
                                <label class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm">
                                    <input type="checkbox" name="status[]" value="<?= $s ?>" class="mr-2">
                                    <?= $s ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php $idUnitLevel = session()->get('unit_level_id'); ?>
                <?php if (in_array($idUnitLevel, ['A7', 'A8', 'A13'])): ?>
                    <!-- Kategori Dropdown -->
                    <div class="relative inline-block text-left mb-4">
                        <label class="block font-semibold mb-1 text-sm text-gray-700">Kategori</label>
                        <div>
                            <button type="button" onclick="toggleDropdown('kategoriDropdown')"
                                class="dropdown-btn inline-flex justify-between items-center px-4 py-2 w-48 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 text-sm">
                                Pilih Kategori
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="kategoriDropdown"
                                class="dropdown absolute hidden z-10 bg-white border border-gray-300 rounded-md mt-1 shadow-md w-48 max-h-48 overflow-y-auto">
                                <label
                                    class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm font-medium text-blue-700">
                                    <input type="checkbox" onclick="toggleAllKategori(this)" class="mr-2"> Pilih Semua
                                </label>
                                <?php foreach ($kategori as $kat): ?>
                                    <label class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm">
                                        <input type="checkbox" name="kategori[]" value="<?= $kat['id_kategori'] ?>"
                                            class="mr-2">
                                        <?= esc($kat['nama_kategori']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Unit Usaha Dropdown -->
                <?php if (in_array($idUnitLevel, ['A7', 'A8'])): ?>
                    <div class="relative inline-block text-left mb-4">
                        <label class="block font-semibold mb-1 text-sm text-gray-700">Unit Usaha</label>
                        <div>
                            <button type="button" onclick="toggleDropdown('unitUsahaDropdown')"
                                class="dropdown-btn inline-flex justify-between items-center px-4 py-2 w-48 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 text-sm">
                                Pilih Unit Usaha
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="unitUsahaDropdown"
                                class="dropdown absolute hidden z-10 bg-white border border-gray-300 rounded-md mt-1 shadow-md w-48 max-h-48 overflow-y-auto">
                                <label
                                    class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm font-medium text-blue-700">
                                    <input type="checkbox" onclick="toggleAllUnitUsaha(this)" class="mr-2"> Pilih Semua
                                </label>
                                <?php foreach ($unitUsahaList as $uu): ?>
                                    <label class="flex items-center px-4 py-2 hover:bg-gray-100 text-sm">
                                        <input type="checkbox" name="unit_usaha[]" value="<?= $uu['id_unit_usaha'] ?>"
                                            class="mr-2">
                                        <?= esc($uu['nm_unit_usaha']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tanggal Mulai -->
                <div>
                    <label class="block font-semibold mb-1 text-sm text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="border rounded px-3 py-2 w-44" />
                </div>

                <!-- Tanggal Selesai -->
                <div>
                    <label class="block font-semibold mb-1 text-sm text-gray-700">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="border rounded px-3 py-2 w-44" />
                </div>
            </div>

            <!-- Tombol Cetak -->
            <div>
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition duration-200">
                    Cetak Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL DATATABLE -->
    <div class="bg-white rounded-2xl p-6 shadow mb-10 overflow-x-auto">
        <table id="reportTicketsTable" class="min-w-full divide-y divide-gray-200 bg-white text-sm">
            <thead class="bg-blue-100 text-blue-700 text-xs uppercase font-semibold text-center">
                <tr>
                    <th class="px-6 py-3" style="min-width: 100px;">Nama Requestor</th>
                    <th class="px-6 py-3" style="min-width: 100px;">Unit Usaha</th>
                    <th class="px-6 py-3" style="min-width: 100px;">Judul</th>
                    <th class="px-6 py-3" style="min-width: 100px;">Kategori</th>
                    <th class="px-6 py-3" style="min-width: 100px;">SubKategori</th>
                    <th class="px-6 py-3" style="min-width: 110px;">Waktu Dibuat</th>
                    <!-- <th class="px-6 py-3" style="min-width: 100px;">Waktu Mulai</th> -->
                    <th class="px-6 py-3" style="min-width: 100px;">Waktu Selesai</th>
                    <th class="px-6 py-3" style="min-width: 50px;">Prioritas</th>
                    <th class="px-6 py-3" style="min-width: 50px;">Status</th>
                    <th class="px-6 py-3" style="min-width: 50px;">Rating Waktu</th>
                    <th class="px-6 py-3" style="min-width: 50px;">Rating Service</th>
                    <th class="px-6 py-3" style="min-width: 100px;">Petugas</th>
                    <th class="px-6 py-3" style="min-width: 50px;">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <!-- Data diisi oleh DataTables -->
            </tbody>
        </table>
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

    <!-- CHART JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($bulanLabels) ?>,
                datasets: [{
                    label: 'Tiket Masuk',
                    data: <?= json_encode($jumlahTiketBulan) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>

    <!-- DATATABLE JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <script>
        $(document).ready(function () {
            const $tanggalMulai = $('#tanggal_mulai');
            const $tanggalSelesai = $('#tanggal_selesai');
            const $filterUrutan = $('#filterUrutan');
            $('#reportTicketsTable').DataTable({
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                ajax: {
                    url: "<?= base_url('tickets/reportData') ?>",
                    type: "POST" // tambahkan ini!
                },
                columns: [
                    { data: 'nama_requestor' },
                    { data: 'nama_unit_usaha' },
                    { data: 'judul' },
                    { data: 'nama_kategori' },
                    { data: 'nama_subkategori' },
                    { data: 'created_at' },
                    {
                        data: 'waktu_selesai',
                        render: function (data, type, row) {
                            return row.status === 'Open' ? '-' : data || '-';
                        }
                    },
                    { data: 'prioritas' },
                    {
                        data: 'status',
                        render: function (data) {
                            const statusColors = {
                                'Open': 'bg-green-100 text-green-800',
                                'In Progress': 'bg-yellow-100 text-yellow-800',
                                'Done': 'bg-blue-100 text-blue-800',
                                'Closed': 'bg-gray-100 text-gray-600'
                            };
                            const colorClass = statusColors[data] || 'bg-gray-100 text-gray-800';
                            return `<span class="whitespace-nowrap px-2 py-1 rounded text-sm font-semibold ${colorClass}">${data}</span>`;
                        }
                    },
                    {
                        data: 'rating_time',
                        render: data => data || '-',
                        createdCell: td => td.style.paddingLeft = '35px'
                    },
                    {
                        data: 'rating_service',
                        render: data => data || '-',
                        createdCell: td => td.style.paddingLeft = '35px'
                    },
                    { data: 'nama_petugas' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-left',
                        render: function (data, type, row) {
                            return `<div class="flex items-center justify-start space-x-2 whitespace-nowrap">
                            <button class="detail-btn bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition" data-id="${row.id_tiket}">Detail</button>
                        </div>`;
                        }
                    }
                ]
            });


            $tanggalMulai.on('change', function () {
                const selectedDate = this.value;
                if (selectedDate) {
                    const endDate = new Date(selectedDate);
                    endDate.setDate(endDate.getDate() + 1);
                    const formattedEndDate = endDate.toISOString().split('T')[0];
                    $tanggalSelesai.val(formattedEndDate);
                } else {
                    $tanggalSelesai.val('');
                }

                loadTickets();
            });

            // Trigger filter saat urutan diubah
            $filterUrutan.on('change', function () {
                loadTickets();
            });

            function loadTickets() {
                const tanggalMulai = $tanggalMulai.val();
                const tanggalSelesai = $tanggalSelesai.val();
                const urutan = $filterUrutan.val();
                const status = currentStatus;

                // Cegah duplikat
                $ticketsContainer.empty();
                $noTickets.hide();
                $loading.show();

                $.ajax({
                    url: `${baseUrl}/tickets/getTickets`,
                    method: 'GET',
                    data: {
                        status: status,
                        tanggal_mulai: tanggalMulai,
                        tanggal_selesai: tanggalSelesai,
                        urutan: urutan,
                    },
                    success: function (data) {
                        $loading.hide();
                        if (data.length > 0) {
                            renderTickets(data);
                        } else {
                            $noTickets.show();
                        }
                    },
                    error: function () {
                        $loading.hide();
                        alert('Gagal mengambil data tiket.');
                    }
                });
            }

            $('#reportTicketsTable tbody').on('click', '.print-pdf-btn', function () {
                const idTiket = $(this).data('id');
                window.open(`<?= base_url('tickets/printpdf') ?>/${idTiket}`, '_blank');
            });

            $('#reportTicketsTable tbody').on('click', '.detail-btn', function () {
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
                                if (diffInSeconds === 0) return `0 dtk`;
                                if (diffInSeconds < 60) {
                                    return `${diffInSeconds} dtk`;
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

            // Tutup modal
            $('#closeModal').on('click', function () {
                $('#ticketDetailModal').addClass('hidden');
                $('#ticketDetails').html('');
            });

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

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // PIE CHART
            fetch('<?= base_url('tickets/status-summary') ?>')
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('statusPieChart')?.getContext('2d');
                    if (!ctx) return console.error('statusPieChart tidak ditemukan');

                    const statusData = {
                        labels: ['Open', 'In Progress', 'Done', 'Closed'],
                        datasets: [{
                            data: [
                                data.Open ?? 0,
                                data['In Progress'] ?? 0,
                                data.Done ?? 0,
                                data.Closed ?? 0
                            ],
                            backgroundColor: ['#3B82F6', '#FACC15', '#10B981', '#6B7280'],
                            borderWidth: 1
                        }]
                    };

                    new Chart(ctx, {
                        type: 'pie',
                        data: statusData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                })
                .catch(err => console.error('Gagal ambil data chart:', err));

            fetch('<?= base_url('tickets/category-summary') ?>')
                .then(response => response.json())
                .then(chartData => {
                    const categoryCanvas = document.getElementById('categoryChart');
                    if (!categoryCanvas) return console.error('categoryChart tidak ditemukan');

                    const ctx = categoryCanvas.getContext('2d');

                    // Jika tidak ada data, tampilkan pesan
                    if (!chartData.labels || chartData.labels.length === 0) {
                        categoryCanvas.style.display = 'none';
                        const container = categoryCanvas.parentElement;
                        container.innerHTML = '<p class="text-center text-gray-500 mt-4">Tidak ada data tiket untuk bulan ini</p>';
                        document.getElementById('categoryLegend').style.display = 'none';
                        return;
                    }

                    // Buat legend
                    if (legendContent && chartData.datasets) {
                        let legendHtml = '';
                        let totalTickets = 0;
                        let categoryStats = {};

                        // Loop tiap kategori (label pada sumbu X)
                        chartData.labels.forEach((categoryLabel, labelIndex) => {
                            // Hitung total tiket untuk kategori ini
                            let categoryTotal = 0;

                            // Mulai blok kategori
                            legendHtml += `<div><p class="text-sm font-semibold text-gray-800 mb-1">Kategori: ${categoryLabel}</p>`;

                            // Loop tiap sub-kategori (datasets)
                            chartData.datasets.forEach((dataset) => {
                                const color = dataset.backgroundColor;
                                const subcategoryLabel = dataset.label;
                                const value = dataset.data[labelIndex] || 0;

                                if (value > 0) {
                                    // Tambahkan ke total
                                    totalTickets += value;
                                    categoryTotal += value;

                                    // Tambahkan subkategori ke dalam blok kategori
                                    legendHtml += `
                    <div class="flex items-center space-x-2 mb-1">
                        <div class="w-4 h-4 rounded" style="background-color: ${color}"></div>
                        <span class="text-sm text-gray-700">${subcategoryLabel} (${value})</span>
                    </div>
                `;
                                }
                            });

                            // Simpan total kategori
                            categoryStats[categoryLabel] = categoryTotal;

                            // Tutup blok kategori
                            legendHtml += `</div>`;
                        });

                        // Render legend ke halaman
                        legendContent.innerHTML = legendHtml;

                        // Tampilkan statistik ringkasan
                        if (summaryStats) {
                            let statsHtml = '';

                            statsHtml += `<div class="text-gray-600">Total Tiket: <span class="font-semibold text-blue-600">${totalTickets}</span></div>`;
                            // Render statistik ke halaman
                            summaryStats.innerHTML = statsHtml;
                        }
                    }

                    // Buang dataset yang semua nilainya nol/null
                    chartData.datasets = chartData.datasets.filter(ds =>
                        ds.data.some(v => v !== null && v !== 0)
                    );

                    // Pastikan setiap dataset memiliki data sesuai jumlah label global
                    chartData.datasets = chartData.datasets.map(ds => {
                        let newData = chartData.labels.map((label, idx) => {
                            // kalau ada nilai asli, pakai nilainya
                            if (ds.data[idx] !== null && ds.data[idx] !== 0) {
                                return ds.data[idx];
                            }
                            // kalau kategori ini tidak ada di subkategori, isi dengan 0
                            return 0;
                        });
                        return {
                            ...ds,
                            data: newData
                        };
                    });

                    // Jangan ubah chartData.labels supaya bar tetap berkelompok
                    // Urutkan dataset jika perlu
                    chartData.datasets.sort((a, b) => {
                        const aIsHardware = a.data[0] > 0;
                        const bIsHardware = b.data[0] > 0;
                        return (aIsHardware === bIsHardware) ? 0 : aIsHardware ? -1 : 1;
                    });

                    new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    filter: function (tooltipItem) {
                                        return tooltipItem.raw > 0;
                                    },
                                    callbacks: {
                                        title: function (tooltipItems) {
                                            return 'Kategori: ' + tooltipItems[0].label;
                                        },
                                        label: function (tooltipItem) {
                                            return tooltipItem.dataset.label + ': ' + tooltipItem.raw + ' tiket';
                                        }
                                    }
                                },
                                legend: {
                                    display: false // Sembunyikan legend default, gunakan custom legend
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        align: 'center',
                                        padding: 0,
                                        font: {
                                            size: 12
                                        },
                                        maxRotation: 45,
                                        minRotation: 0
                                    },
                                    grid: {
                                        display: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Kategori Tiket',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        font: {
                                            size: 12
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Jumlah Tiket',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    }
                                }
                            },
                            elements: {
                                bar: {
                                    categoryPercentage: 1.0,
                                    barPercentage: 1.0
                                }
                            },
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 10,
                                    top: 20,
                                    bottom: 10
                                }
                            }
                        }
                    });
                })
                .catch(err => {
                    console.error('Gagal ambil data chart kategori:', err);
                    document.getElementById('categoryLegend').style.display = 'none';
                });

        });

    </script>

    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const isVisible = !dropdown.classList.contains('hidden');

            // Tutup semua dropdown lain dulu
            document.querySelectorAll('.dropdown').forEach(el => el.classList.add('hidden'));

            // Toggle dropdown yang ditekan
            if (!isVisible) dropdown.classList.remove('hidden');
        }

        document.addEventListener('click', function (event) {
            const isClickInside = event.target.closest('.dropdown') || event.target.closest('.dropdown-btn');
            if (!isClickInside) {
                document.querySelectorAll('.dropdown').forEach(el => el.classList.add('hidden'));
            }
        });

        // Fungsi untuk memilih semua checkbox status
        function toggleAllStatus(source) {
            const checkboxes = document.querySelectorAll('input[name="status[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        // Fungsi untuk memilih semua checkbox kategori
        function toggleAllKategori(source) {
            const checkboxes = document.querySelectorAll('input[name="kategori[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        // Fungsi untuk memilih semua checkbox unit usaha
        function toggleAllUnitUsaha(source) {
            const checkboxes = document.querySelectorAll('input[name="unit_usaha[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>

    <?= $this->endSection() ?>