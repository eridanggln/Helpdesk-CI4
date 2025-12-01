<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>



<div class="max-w-7xl mx-auto px-6 py-8 bg-white p-6 rounded-lg shadow-md">

    <div class="flex flex-col md:flex-row items-start gap-6">
        <!-- Avatar -->
        <div class="flex-shrink-0">
            <div
                class="h-28 w-28 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold">
                <?= session('nama') ? strtoupper(substr(trim(session('nama')), 0, 1)) : '?' ?>
            </div>
        </div>


        <!-- Informasi Profil + Tombol -->
        <div class="flex-1 ml-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= esc(session('nama')) ?></h2>

            <div class="space-y-2 text-sm mb-6">
                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Email</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('email')) ?: '—' ?></div>
                </div>

                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Unit Level</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('unit_level_name')) ?: '-' ?></div>
                </div>

                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Unit Usaha</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('unit_usaha')) ?: '-' ?></div>
                </div>

                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Unit Kerja</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('unit_kerja')) ?: '-' ?></div>
                </div>

                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Sub Unit Kerja</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('unit_kerja_sub_name')) ?: '-' ?></div>
                </div>

                <div class="flex">
                    <div class="w-40 text-gray-500 font-medium">Lokasi</div>
                    <div class="text-gray-800 font-semibold">: <?= esc(session('unit_lokasi_name')) ?: '-' ?></div>
                </div>
            </div>

            <!-- Tombol Edit Profil -->
            <div class="mt-4">
                <button onclick="openPasswordModal()"
                    class="inline-block bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition">
                    Ubah Password
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Ubah Password -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <h2 class="text-xl font-semibold mb-4">Ubah Password</h2>
        <form action="<?= base_url('profile/change-password') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Password Lama</label>
                <input type="password" name="old_password" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Password Baru</label>
                <input type="password" name="new_password" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePasswordModal()"
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
            </div>
        </form>
        <!-- Close Button (Optional for top-right close) -->
        <button onclick="closePasswordModal()" class="absolute top-2 right-2 text-gray-500 hover:text-black">
            &times;
        </button>
    </div>
</div>


<script>
    function openPasswordModal() {
        document.getElementById('passwordModal').classList.remove('hidden');
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>