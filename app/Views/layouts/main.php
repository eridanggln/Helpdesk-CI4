<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= isset($title) ? esc($title) : 'Help Desk System' ?></title>

    <!-- Load Tailwind CSS dulu -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Load plugin Typography CSS (bisa langsung CSSnya saja) -->
    <link href="https://cdn.jsdelivr.net/npm/@tailwindcss/typography@0.5.9/dist/typography.min.css" rel="stylesheet">



    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
    <style>
        .prose ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            list-style-position: outside;
            /* lebih umum agar bullet berada di luar teks */
        }

        .prose ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            list-style-position: outside;
        }

        .prose ul li,
        .prose ol li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>


<body class="bg-white text-blue-900 flex h-screen font-sans leading-relaxed">

    <!-- Sidebar -->
    <?php $uri = service('uri'); ?>
    <?php $unitLevelId = session()->get('unit_level_id'); ?>
    <?php $unitKerjaId = session()->get('unit_kerja_id'); ?>
    <?php $unitKerjaSubId = session()->get('unit_kerja_sub_id'); ?>
    <?php $idPegawai = session()->get('id_pegawai'); ?>


    <aside
        class="w-64 bg-white shadow-md fixed top-4 left-4 h-[calc(100vh-2rem)] flex flex-col rounded-lg p-5 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-400 scrollbar-track-blue-100">
        <div
            class="mt-8 mb-5 text-2xl font-bold text-blue-800 select-none cursor-default tracking-wide text-center w-full">
            Helpdesk
        </div>

        <!-- Tambahkan Alpine.js jika belum -->
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

        <?php
        // Cek apakah salah satu submenu aktif
        $isMasterActive = in_array($uri->getSegment(2), ['kategori', 'subkategori']);
        ?>

        <nav class="flex-1 flex flex-col text-sm font-medium text-gray-500 bg-white">
            <!-- Dashboard (in_array($unitKerjaSubId, ['F38', 'F39', 'F40', 'F45']) || $pegawaiId === 'PG_35'): -->
            <?php if (in_array($unitKerjaSubId, ['F38', 'F39', 'F40', 'F45'])): ?> 
                <a href="/dashboard"
                    class="block py-2 px-4 rounded-lg transition duration-200 ease-in-out
           <?= $uri->getSegment(1) == 'dashboard' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                    Dashboard
                </a>
            <?php endif; ?>


            <a href="/tickets"
                class="block py-2 px-4 rounded-lg transition duration-200 ease-in-out
               <?= $uri->getSegment(1) == 'tickets' && $uri->getSegment(2) == '' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                Tickets
            </a>

            <?php if (in_array($unitKerjaSubId, ['F38', 'F39', 'F40', 'F45'])): ?>
                <a href="/tickets/board-staff"
                    class="block py-2 px-4 rounded-lg transition duration-200 ease-in-out
               <?= $uri->getSegment(1) == 'tickets/board-staff' && $uri->getSegment(2) == '' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                    Ticket Board
                </a>
            <?php endif; ?>


            <?php if (in_array(session('unit_level_id'), ['A13', 'A8', 'A7']) && in_array(session('unit_kerja_sub_id'), ['F37', 'F38', 'F39', 'F40', 'F45'])): ?>
                <a href="/tickets/report"
                    class="block py-2 px-4 rounded-lg transition duration-200 ease-in-out
                        <?= $uri->getSegment(2) == 'tickets/report' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                    Report Tickets
                </a>
            <?php endif; ?>


            <?php
            use CodeIgniter\HTTP\URI;
            $uri = service('uri');
            $isMasterActive = in_array($uri->getSegment(2), ['kategori', 'subkategori']);
            ?>

            <?php if (
                in_array($unitLevelId, ['A13', 'A7', 'A8']) &&
                in_array($unitKerjaSubId, ['F38', 'F39', 'F40', 'F45']) || $idPegawai === 'PG_29'
            ): ?>
                <!-- Dropdown Master Data -->
                <div x-data="{ open: <?= json_encode($isMasterActive) ?> }" class="relative">
                    <!-- Trigger -->
                    <button @click="open = !open" class="w-full text-left block py-2 px-4 rounded-lg transition duration-200 ease-in-out
            hover:bg-blue-100 hover:text-blue-700 flex items-center justify-between
            <?= $isMasterActive ? 'text-blue-600 bg-blue-50 font-semibold' : '' ?>">
                        <span>Master Data</span>
                        <svg :class="{ 'rotate-180': open }" class="w-4 h-4 transform transition-transform duration-200"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="open" x-transition class="ml-4 mt-1 space-y-1" x-cloak>
                        <a href="/master/kategori"
                            class="block py-1 px-4 rounded-lg transition duration-200 ease-in-out
                <?= $uri->getSegment(2) == 'kategori' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                            Kategori
                        </a>
                        <a href="/master/subkategori"
                            class="block py-1 px-4 rounded-lg transition duration-200 ease-in-out
                <?= $uri->getSegment(2) == 'subkategori' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                            Sub Kategori
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile -->
            <a href="/profile"
                class="block py-2 px-4 rounded-lg transition duration-200 ease-in-out
                <?= $uri->getSegment(1) == 'profile' ? 'text-blue-600 bg-blue-50 font-semibold' : 'hover:bg-blue-100 hover:text-blue-700' ?>">
                Profile
            </a>


            <!-- Spacer agar Logout selalu di bawah -->
            <div class="flex-1"></div>

            <!-- Logout Button -->
            <a href="/auth/logout"
                class="block py-3 px-4 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-center font-semibold transition duration-200 ease-in-out mt-4">
                Logout
            </a>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-auto" style="margin-left: 280px;">
        <header
            class="sticky top-4 z-20 bg-white border border-blue-200 rounded-lg shadow-md px-8 py-2 flex items-center justify-between mx-6">

            <!-- Ganti h1 dengan logo -->
            <img src="/assets/logo.png" alt="Logo BTM" class="h-12 w-auto select-none"
                style="width: 150px; height: auto;" />

            <!-- Container kanan untuk nama user + role + avatar -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <div class="text-blue-800 select-none cursor-default text-right">
                        <div class="font-medium px-5 py-1">
                            <?= esc(session()->get('nama') ?? 'User') ?>
                        </div>
                        <div class="text-sm text-blue-600 px-5">
                            <?= esc(session()->get('unit_level_name') ?? '-') ?> |
                            <?= esc(session()->get('unit_usaha') ?? '-') ?> -
                            <?= esc(session()->get('unit_kerja_sub_name') ?? '-') ?>
                        </div>
                    </div>

                    <div class="relative flex items-center space-x-4">
                        <!-- Avatar Button -->
                        <button id="avatarBtn"
                            class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold uppercase select-none cursor-pointer hover:bg-blue-700 transition">
                            <?= session('nama') ? strtoupper(substr(trim(session('nama')), 0, 1)) : '?' ?>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="dropdownMenu"
                            class="hidden absolute top-12 right-0 w-52 bg-white rounded-md shadow-xl z-50 border border-gray-200 animate-fade-in">
                            <a href="<?= base_url('auth/logout') ?>"
                                class="block px-5 py-3 text-base text-red-600 hover:bg-red-50 hover:text-red-700 transition font-medium">
                                Logout
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-4 overflow-auto bg-white mx-6 my-3 rounded-lg ">
            <?= $this->renderSection('content') ?>
        </main>
    </div>


</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

</html>

<script>
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    avatarBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('hidden');
    });

    // Sembunyikan dropdown saat klik di luar
    document.addEventListener('click', function () {
        dropdownMenu.classList.add('hidden');
    });
</script>