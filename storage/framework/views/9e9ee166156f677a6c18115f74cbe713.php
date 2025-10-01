<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($survey['name'] ?? 'Survey'); ?> - Survey Manager</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite Assets with Tailwind CSS -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <!-- Livewire Styles -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <!-- Meta tags for sharing -->
    <meta property="og:title" content="<?php echo e($survey['name'] ?? 'Survey'); ?>">
    <meta property="og:description" content="<?php echo e($survey['description'] ?? 'Complete this survey'); ?>">
    <meta property="og:type" content="website">
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Simple Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors duration-200">
                            Survey Manager
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Public Survey</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <?php echo e($slot); ?>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center text-sm text-gray-600">
                    <p>&copy; <?php echo e(date('Y')); ?> Survey Manager. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>
    <script>
        // Toast notification utility
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `mb-2 px-4 py-2 rounded-md shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            toast.textContent = message;
            container.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
        };

        // Listen to Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('survey-submitted', () => {
                showToast('Survey submitted successfully!', 'success');
            });
        });
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/public.blade.php ENDPATH**/ ?>