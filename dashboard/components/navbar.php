<?php
if (!isset($_SESSION)) session_start();
$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';
$initial = strtoupper(substr($name ?: 'U', 0, 1));
?>

<header class="bg-white border-b border-gray-200 text-gray-800 px-4 py-3 flex justify-between items-center sticky top-0 z-40 shadow-sm">
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide"><?= htmlspecialchars(ucfirst($role)) ?> Panel</p>
        <h1 class="text-base sm:text-lg font-semibold text-purple-900">Adullam Dashboard</h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="profile" class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-purple-50 transition">
            <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-800 flex items-center justify-center text-sm font-bold"><?= htmlspecialchars($initial) ?></span>
            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($name) ?></span>
        </a>
        <a href="profile" class="sm:hidden w-9 h-9 rounded-full bg-purple-100 text-purple-800 flex items-center justify-center text-sm font-bold">
            <?= htmlspecialchars($initial) ?>
        </a>
        <button id="mobileSidebarToggle" class="text-purple-900 text-2xl focus:outline-none lg:hidden" aria-label="Open menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>
    </div>
</header>
