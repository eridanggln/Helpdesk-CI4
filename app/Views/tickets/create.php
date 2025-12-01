<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-7xl mx-auto px-6 py-8 bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-3xl font-bold mb-8 text-blue-900 select-none border-b border-blue-300 pb-2">Tambah Tiket Baru</h2>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-700 rounded shadow-sm">
            <ul class="list-disc list-inside">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="createTicketForm" method="post" action="<?= base_url('tickets/create') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nama Requestor (readonly) -->
            <div>
                <label for="nama_requestor" class="block font-semibold mb-1">Nama Requestor</label>
                <input type="text" id="nama_requestor" name="nama_requestor" value="<?= esc(session()->get('nama')) ?>"
                    readonly class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" />
            </div>

            <!-- Ruangan Otomatis dari Session -->
            <div>
                <label for="nm_unit_kerja_sub" class="block font-semibold mb-1">Sub Unit Kerja</label>
                <input type="text" id="nm_unit_kerja_sub" name="nm_unit_kerja_sub"
                    value="<?= esc(session()->get('unit_kerja_sub_name')) ?>" readonly
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" />
                <input type="hidden" name="id_unit_kerja_sub" value="<?= esc(session()->get('unit_kerja_sub_id')) ?>" />
            </div>


            <!-- Tujuan Tiket -->
            <div>
                <label for="id_unit_tujuan" class="block font-semibold mb-1">Tujuan Tiket</label>
                <select id="id_unit_tujuan" name="id_unit_tujuan" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Unit Kerja Tujuan --</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= esc($unit['id_unit_kerja']) ?>"><?= esc($unit['nm_unit_kerja']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Upload Gambar -->
            <div>
                <label for="gambar" class="block font-semibold mb-1">Upload Gambar </label>
                <input type="file" id="gambar" name="gambar" accept="image/*" class="w-full" />
            </div>

            <!-- Pilih Kategori -->
            <div>
                <label for="kategori" class="block font-semibold mb-1">Kategori</label>
                <select id="kategori" name="kategori" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategori as $kat): ?>
                        <option class="kategori-option" data-unit_kerja="<?= esc($kat['unit_kerja']) ?>"
                            value="<?= esc($kat['id_kategori']) ?>" style="display: none;">
                            <?= esc($kat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="id_unit_kerja_sub_tujuan" id="id_unit_kerja_sub_tujuan" />

            <!-- Pilih Subkategori -->
            <div>
                <label for="subkategori" class="block font-semibold mb-1">Sub Kategori</label>
                <select id="subkategori" name="subkategori" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Sub Kategori --</option>
                    <?php foreach ($subkategori as $sub): ?>
                        <option class="subkategori-option" data-kategori="<?= esc($sub['id_kategori']) ?>"
                            value="<?= esc($sub['id_subkategori']) ?>">
                            <?= esc($sub['nama_subkategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Judul -->
        <div class="mt-4">
            <label for="judul" class="block font-semibold mb-1">Judul</label>
            <input type="text" id="judul" name="judul" required class="w-full border rounded px-3 py-2" />
        </div>

        <!-- Deskripsi -->
        <div class="mt-4">
            <label for="deskripsi" class="block font-semibold mb-1">Deskripsi</label>
            <div id="editor" style="height: 300px;"></div>
            <textarea name="deskripsi" id="deskripsi" hidden></textarea>
        </div>

        <button type="submit"
            class=" mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
            Kirim Tiket
        </button>
    </form>

    <!-- Loading Spinner -->
    <div id="loadingSpinner"
        class="hidden fixed inset-0 bg-gray-500 bg-opacity-50 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full border-t-4 border-blue-600 h-16 w-16 mb-4"></div>
        <p class="text-white">Sedang memproses...</p>
    </div>

    <?= $this->section('content') ?>
    <!-- ... -->
    <style>
        .ql-editor {
            font-size: 16px;
            line-height: 1.6;
        }
    </style>
    <!-- ... -->
    <?= $this->endSection() ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Flashdata success -->
<?php if (session()->getFlashdata('success')): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '<?= session()->getFlashdata('success') ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
<?php endif; ?>

<!-- Script logic -->
<script>
    $(document).ready(function () {
        console.log("JS is running"); // DEBUG

        const $tujuan = $('#id_unit_tujuan');
        const $kategori = $('#kategori');
        const $subkategori = $('#subkategori');
        const $loading = $('#loadingSpinner');
        const $form = $('#createTicketForm');

        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Tulis deskripsi tiket...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            console.log("AJAX form submitted");

            const html = quill.root.innerHTML.trim();
            if (html === '' || html === '<p><br></p>') {
                Swal.fire('Peringatan', 'Deskripsi tidak boleh kosong.', 'warning');
                return;
            }

            $('#deskripsi').val(html);
            $loading.removeClass('hidden');

            const formData = new FormData(this);

            $.ajax({
                url: "<?= base_url('tickets/create') ?>",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $loading.addClass('hidden');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Tiket berhasil dibuat',
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = "<?= base_url('tickets') ?>";
                    });
                },
                error: function (xhr) {
                    $loading.addClass('hidden');
                    const errors = xhr.responseJSON?.errors || {};
                    const message = Object.values(errors).flat().join('\n') || 'Terjadi kesalahan saat menyimpan tiket.';
                    Swal.fire('Gagal', message, 'error');
                }
            });
        });

        $tujuan.on('change', function () {
            const tujuanSelected = $(this).val();
            $kategori.val('');
            $subkategori.val('');
            $('.subkategori-option').hide();

            // Sembunyikan semua kategori
            $('#kategori option.kategori-option').hide();

            if (!tujuanSelected) return;

            // Tampilkan kategori yang sesuai unit_kerja
            $('#kategori option.kategori-option').each(function () {
                if ($(this).data('unit_kerja') == tujuanSelected) {
                    $(this).show();
                }
            });
        });

        $kategori.on('change', function () {
            const kategoriId = $(this).val();
            $('.subkategori-option').each(function () {
                $(this).toggle($(this).data('kategori') == kategoriId);
            });
            $subkategori.val('');

            if (kategoriId) {
                $.ajax({
                    url: '<?= base_url('api/kategori/penanggung-jawab') ?>/' + kategoriId,
                    method: 'GET',
                    success: function (res) {
                        const pjs = res.penanggung_jawab || [];
                        $('#id_unit_kerja_sub_tujuan').val(pjs.length > 0 ? pjs[0] : '');
                    },
                    error: function () {
                        $('#id_unit_kerja_sub_tujuan').val('');
                    }
                });
            } else {
                $('#id_unit_kerja_sub_tujuan').val('');
            }
        });

        $tujuan.trigger('change');
    });
</script>

<?= $this->endSection() ?>