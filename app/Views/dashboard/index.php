<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $unitKerjaSubId = session()->get('unit_kerja_sub_id'); ?>

<div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-2xl font-semibold mb-6">Dashboard Tiket </h1>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Tiket Masuk per Bulan -->
    <div class="w-full h-[300px]">
      <h3 class="font-semibold mb-2 text-gray-700">Total Tiket Kerjakan</h3>
      <canvas id="monthlyChart" class="w-full h-full"></canvas>
    </div>

    <!-- Status Tiket per Bulan -->
    <div class="w-full h-[300px]">
      <h3 class="font-semibold mb-2 text-gray-700">Status Tiket Kerjakan Bulan Ini</h3>
      <canvas id="statusChart" class="w-full h-full"></canvas>
    </div>
  </div>

  <!-- Rating -->
  <div class="mt-10 mb-10">
    <h3 class="font-semibold mb-4 text-gray-700">Rating yang Kamu Dapatkan</h3>
    <div class="grid grid-cols-2 gap-6">
      <div class="bg-green-100 rounded-xl p-6 shadow text-center">
        <h3 class="text-sm text-green-800 font-semibold">Rata-Rata Rating Waktu</h3>
        <p class="text-4xl font-bold text-green-900 mt-2"><?= esc($avgTime) ?></p>
      </div>
      <div class="bg-yellow-100 rounded-xl p-6 shadow text-center">
        <h3 class="text-sm text-yellow-800 font-semibold">Rata-Rata Rating Layanan</h3>
        <p class="text-4xl font-bold text-yellow-900 mt-2"><?= esc($avgService) ?></p>
      </div>
    </div>
  </div>

  <h1 class="text-2xl font-semibold mb-6 text-blue-900">Cetak Tiket</h1>

  <!-- Filter & Cetak -->
  <div class="bg-white rounded-2xl p-6 shadow mb-8">
    <form action="<?= base_url('dashboard/print-report-pdf') ?>" method="get"
      class="flex flex-wrap gap-4 items-end justify-between">
      <div class="flex flex-wrap gap-4">

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
      <div class="flex gap-2">
        <button type="submit"
          class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition duration-200">
          Cetak Laporan
        </button>
        <!-- <button type="button" id="cetakSemuaBtn"
          class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition duration-200">
          Cetak Semua Data
        </button> -->
      </div>
    </form>
  </div>

  <!-- TABEL DATATABLE -->
  <div class="bg-white rounded-2xl p-6 shadow mb-10 overflow-x-auto">
    <table id="ticketsTable" class="min-w-full divide-y divide-gray-200 bg-white text-sm">
      <thead class="bg-blue-100 text-blue-700 text-xs uppercase font-semibold text-center">
        <tr>
          <th class="px-6 py-3" style="min-width: 100px;">Nama Requestor</th>
          <th class="px-6 py-3" style="min-width: 100px;">Unit Usaha</th>
          <th class="px-6 py-3" style="min-width: 100px;">Judul</th>
          <th class="px-6 py-3" style="min-width: 100px;">Kategori</th>
          <th class="px-6 py-3" style="min-width: 100px;">SubKategori</th>
          <th class="px-6 py-3" style="min-width: 110px;">Waktu Dibuat</th>
          <!-- <th class="px-6 py-3" style="min-width: 100px;">Waktu Mulai</th> -->
          <th class="px-6 py-3" style="min-width: 110px;">Waktu Selesai</th>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>

<script>
  $(document).ready(function () {
    const $tanggalMulai = $('#tanggal_mulai');
    const $tanggalSelesai = $('#tanggal_selesai');
    const $filterUrutan = $('#filterUrutan');
    $('#ticketsTable').DataTable({
      processing: true,
      serverSide: false,
      order: [[5, 'desc']], // Urutkan berdasarkan kolom ke-6 (indeks dimulai dari 0), yaitu 'waktu_mulai' secara DESC (terbaru-terlama)
      ajax: {
        url: '<?= base_url('dashboard/ticketTable') ?>',
        data: function (d) {
          d.status = [];
          $('input[name="status[]"]:checked').each(function () {
            d.status.push($(this).val());
          });
          d.tanggal_mulai = $('input[name="tanggal_mulai"]').val();
          d.tanggal_selesai = $('input[name="tanggal_selesai"]').val();
        }
      },
      columns: [
        { data: 'nama_requestor' },
        {
          data: 'nama_unit_usaha',
        },
        { data: 'judul' },
        { data: 'nama_kategori' },
        { data: 'nama_subkategori' },
        {
          data: 'waktu_dibuat',
        },
        // {
        //     data: 'waktu_mulai',
        //     render: function (data, type, row) {
        //         return row.status === 'Open' ? '-' : data || '-';
        //     }
        // },
        {
          data: 'waktu_selesai',
          render: function (data, type, row) {
            return row.status === 'Open' ? '-' : data || '-';
          }
        },
        { data: 'prioritas' },
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
          data: 'rating_time',
          render: function (data) {
            return data || '-';
          },
          createdCell: function (td) {
            td.style.paddingLeft = '35px';
          }
        },
        {
          data: 'rating_service',
          render: function (data) {
            return data || '-';
          },
          createdCell: function (td) {
            td.style.paddingLeft = '35px';
          }
        },
        { data: 'nama_petugas' },
        {
          data: null,
          orderable: false,
          searchable: false,
          className: 'text-left', // ubah agar kontennya rata kiri
          render: function (data, type, row) {
            let btn = `<div class="flex items-center justify-start space-x-2 whitespace-nowrap">
            <button class="detail-btn bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition" data-id="${row.id_tiket}">Detail</button>`;
            btn += `</div>`;
            return btn;
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

    $('#ticketsTable tbody').on('click', '.print-pdf-btn', function () {
      const tglMulai = $('#tanggal_mulai').val();
      const tglSelesai = $('#tanggal_selesai').val();

      const url = `/dashboard/printReportPdf?tanggal_mulai=${tglMulai}&tanggal_selesai=${tglSelesai}`;
      window.open(url, '_blank');
    });

    // Tombol Cetak Semua Data
    $('#cetakSemuaBtn').on('click', function () {
      window.open('<?= base_url('dashboard/print-report-pdf') ?>', '_blank');
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

          // Ambil tanggal dari form filter
          const tanggalMulai = $('input[name="tanggal_mulai"]').val();
          const tanggalSelesai = $('input[name="tanggal_selesai"]').val();

          let url = '<?= base_url('dashboard/print-report-pdf') ?>';
          const params = [];

          if (tanggalMulai) params.push('tanggal_mulai=' + tanggalMulai);
          if (tanggalSelesai) params.push('tanggal_selesai=' + tanggalSelesai);

          if (params.length > 0) {
            url += '?' + params.join('&');
          }

          const printBtn = `
                    <div class="text-right mt-4">
                        <a href="${url}"
                          target="_blank"
                          class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                          🖨️ Cetak Laporan Tiket Saya (PDF)
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
  // Grafik jumlah tiket per bulan
  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($bulanLabels) ?>,
      datasets: [{
        label: 'Tiket Masuk',
        data: <?= json_encode($jumlahTiketBulan) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });


  // Grafik status tiket (urutan: In Progress, Done, Closed)
  new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($statusLabels) ?>, // Pastikan urutannya dari controller
      datasets: [{
        label: 'Jumlah Tiket',
        data: <?= json_encode($statusChartData) ?>,
        backgroundColor: [
          '#FACC15', // blue untuk In Progress
          '#10B981', // green untuk Done
          '#6b7280'  // gray untuk Closed
        ],
        borderWidth: 1
      }]
    },
    options: {
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
</script>

<?= $this->endSection() ?>