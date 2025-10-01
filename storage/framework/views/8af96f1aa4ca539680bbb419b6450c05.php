<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Survey Management System</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite Assets with Tailwind CSS -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <!-- Livewire Styles -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="<?php echo e(route('web.dashboard')); ?>" class="text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors duration-200">
                                Survey Manager
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="<?php echo e(route('web.dashboard')); ?>" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium <?php echo e(request()->routeIs('web.dashboard') ? 'border-b-2 border-blue-500 text-gray-900' : ''); ?>">
                                Dashboard
                            </a>
                            <a href="<?php echo e(route('web.surveys.index')); ?>" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium <?php echo e(request()->routeIs('web.surveys.*') ? 'border-b-2 border-blue-500 text-gray-900' : ''); ?>">
                                Surveys
                            </a>
                            <a href="<?php echo e(route('web.questions.index')); ?>" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium <?php echo e(request()->routeIs('web.questions.*') ? 'border-b-2 border-blue-500 text-gray-900' : ''); ?>">
                                Questions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <?php echo $__env->yieldContent('content'); ?>
            <?php echo e($slot ?? ''); ?>

        </main>
    </div>
    <!-- Livewire Scripts -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>
    <script>
        // Utility function for toast notifications
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
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        };
        // Listen to Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (event) => {
                showToast(event.message, event.type);
            });
        });
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>