<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-3xl font-bold mb-8 text-blue-900 border-b border-blue-300 pb-2 select-none">Daftar Tiket Saya</h2>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded shadow-sm">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <a href="<?= base_url('tickets/create') ?>"
        class="inline-flex items-center gap-2 mb-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition duration-200">
        <!-- Ikon Plus -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
        </svg>
        Tambah Tiket
    </a>

    <div class="overflow-x-auto rounded-lg px-2">
        <table id="ticketsTable" class="min-w-full divide-y divide-gray-200 bg-white mx-auto">
            <thead class="bg-blue-100">
                <tr>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        ID</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Tujuan</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Judul</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Kategori</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        SubKategori</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Prioritas</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Status</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">
                        Tanggal Dibuat</th>
                    <th class="px-6 py-3 !text-center text-xs font-semibold text-blue-700 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                <!-- DataTables akan render di sini -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Tiket -->
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


<!-- Modal Konfirmasi Penyelesaian -->
<div id="confirmCompletionModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full max-h-[90vh] overflow-y-auto relative p-6">
        <button id="closeConfirmModal"
            class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-3xl font-bold leading-none">&times;</button>
        <h3 class="text-2xl font-semibold mb-6 text-blue-900 select-none">Konfirmasi Penyelesaian Tiket</h3>

        <form id="confirmCompletionForm">
            <input type="hidden" name="id_tiket" id="confirm_id_tiket" />
            <label for="status" class="block font-semibold mb-1">Status Penyelesaian</label>
            <select id="status" name="status" required class="w-full border rounded px-3 py-2 mb-4">
                <option value="Open">Belum Selesai</option>
                <option value="Closed">Sudah Selesai</option>
            </select>
            <label for="komentar_penyelesaian" class="block font-semibold mb-1">Komentar Penyelesaian</label>
            <textarea id="komentar_penyelesaian" name="komentar_penyelesaian" rows="4" required
                class="w-full border rounded px-3 py-2 mb-4"></textarea>



            <div class="mb-4">
                <label class="block font-semibold mb-1">Rating Service</label>
                <select name="rating_service" id="rating_service" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Rating Service --</option>
                    <option value="1">1 - Sangat Buruk</option>
                    <option value="2">2 - Buruk</option>
                    <option value="3">3 - Cukup</option>
                    <option value="4">4 - Baik</option>
                    <option value="5">5 - Sangat Baik</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block font-semibold mb-1">Rating Waktu Penyelesaian</label>
                <select name="rating_time" id="rating_time" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Rating Waktu --</option>
                    <option value="1">1 - Sangat Lama</option>
                    <option value="2">2 - Lama</option>
                    <option value="3">3 - Cukup</option>
                    <option value="4">4 - Cepat</option>
                    <option value="5">5 - Sangat Cepat</option>
                </select>
            </div>

            <button type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition duration-200">
                Kirim Konfirmasi
            </button>
        </form>

        <!-- Loading Spinner -->
        <div id="loading" class="hidden fixed inset-0 bg-gray-500 bg-opacity-50 flex items-center justify-center z-50">
            <div class="animate-spin rounded-full border-t-4 border-blue-600 h-16 w-16 mb-4"></div>
            <p class="text-white">Sedang memproses...</p>
        </div>
    </div>
</div>




<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>



<script>
    $(document).ready(function () {
        $('#ticketsTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[6, 'desc']],
            ajax: "<?= base_url('tickets/list') ?>",
            columns: [
                {
                    data: 'id_tiket',
                    render: function (data) {
                        return `<span class="whitespace-nowrap">#${data}</span>`;
                    }
                },
                {
                    data: 'tujuan',
                    render: function (data) {
                        return `<span  class="whitespace-nowrap text-center w-full block">${data}</span>`;
                    }
                },
                {
                    data: 'judul'
                },
                {
                    data: 'nama_kategori'
                },
                {
                    data: 'nama_subkategori'
                },
                {
                    data: 'prioritas',
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        const statusColors = {
                            'Open': 'bg-green-100 text-green-800',
                            'In Progress': 'bg-yellow-100 text-yellow-800',
                            'Done': 'bg-blue-100 text-blue-800',
                            'Closed': 'bg-gray-100 text-gray-600'
                        };

                        const colorClass = statusColors[data] || 'bg-gray-100 text-gray-800';

                        // tambahkan kelas nowrap agar tidak pecah baris
                        return `<span class="whitespace-nowrap px-2 py-1 rounded text-sm font-semibold ${colorClass}">${data}</span>`;
                    }
                },
                {
                    data: 'created_at',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            if (!data) return '';
                            const dateOnly = data.split(' ')[0];
                            return dateOnly;
                        }
                        return data;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-left', // ubah agar kontennya rata kiri
                    render: function (data, type, row) {
                        let btn = `<div class="flex items-center justify-start space-x-2 whitespace-nowrap">
            <button class="detail-btn bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition" data-id="${row.id_tiket}">Detail</button>`;
                        if (row.status === 'Done' && row.confirm_by_requestor == 0) {
                            btn += `<a href="#" class="confirm-btn text-blue-600 hover:underline font-semibold" data-id="${row.id_tiket}">Konfirmasi</a>`;
                        }

                        btn += `</div>`;
                        return btn;
                    }
                }
            ],
            lengthMenu: [10, 25, 50],
            language: {
                emptyTable: "Tidak ada data tiket yang tersedia",
                processing: "Memuat data...",
                lengthMenu: "Tampilkan _MENU_ tiket",
                search: "Cari:",
                paginate: {
                    next: "Berikutnya",
                    previous: "Sebelumnya"
                }
            }
        });

        $('#ticketsTable tbody').on('click', '.print-pdf-btn', function () {
            const idTiket = $(this).data('id');
            window.open(`<?= base_url('tickets/printpdf') ?>/${idTiket}`, '_blank');
        });

        $('#ticketsTable tbody').on('click', '.detail-btn', function () {
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
                                return `${hours} dtk${minutes > 0 ? ` ${minutes} mnt` : ''}`;
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



        $('#status').on('change', function () {
            const selected = $(this).val();
            if (selected === 'Closed') {
                $('#rating_service').prop('required', true).closest('.mb-4').show();
                $('#rating_time').prop('required', true).closest('.mb-6').show();
            } else {
                $('#rating_service').prop('required', false).val('').closest('.mb-4').hide();
                $('#rating_time').prop('required', false).val('').closest('.mb-6').hide();
            }
        });

        $('#ticketsTable tbody').on('click', '.confirm-btn', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#confirm_id_tiket').val(id);
            $('#komentar_penyelesaian').val('');
            $('#rating_service').val('');
            $('#rating_time').val('');
            $('#status').val('Open').trigger('change');
            $('#confirmCompletionModal').removeClass('hidden');
        });

        $('#closeConfirmModal').on('click', function () {
            $('#confirmCompletionModal').addClass('hidden');
        });

        $('#confirmCompletionForm').on('submit', function (e) {
            e.preventDefault();
            $('#loading').removeClass('hidden');
            const formData = $(this).serialize();

            $.post("<?= base_url('tickets/confirm') ?>", formData, function (res) {
                if (res.status === 'success') {
                    $('#loading').addClass('hidden');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Konfirmasi berhasil',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    $('#confirmCompletionModal').addClass('hidden');
                    $('#ticketsTable').DataTable().ajax.reload();
                } else {
                    $('#loading').addClass('hidden');
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message || 'Gagal mengkonfirmasi tiket',
                    });
                }
            }).fail(function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal mengirim data konfirmasi',
                });
            });
        });
    });
</script>


<?= $this->endSection() ?>