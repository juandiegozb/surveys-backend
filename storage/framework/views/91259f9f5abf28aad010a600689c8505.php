<!-- Survey Form Layout -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!--[if BLOCK]><![endif]--><?php if($loading): ?>
            <!-- Loading State -->
            <div class="flex justify-center items-center py-16">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500"></div>
                <span class="ml-4 text-lg text-gray-600">Loading survey...</span>
            </div>
        <?php elseif($submitted): ?>
            <!-- Success State -->
            <div class="bg-white shadow-xl rounded-2xl border border-gray-200 p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Thank You!</h2>
                <p class="text-gray-600 mb-6">Your response has been submitted successfully.</p>
                <a href="/" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Return to Home
                </a>
            </div>
        <?php elseif($survey): ?>
            <!-- Survey Form -->
            <div class="bg-white shadow-xl rounded-2xl border border-gray-200">
                <!-- Survey Header -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo e($survey['name']); ?></h1>
                            <!--[if BLOCK]><![endif]--><?php if($survey['description']): ?>
                                <p class="mt-2 text-gray-600"><?php echo e($survey['description']); ?></p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                        <!--[if BLOCK]><![endif]--><?php if($totalSteps > 1): ?>
                            <div class="text-sm text-gray-500">
                                Question <?php echo e($currentStep); ?> of <?php echo e($totalSteps); ?>

                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <!-- Progress Bar -->
                    <!--[if BLOCK]><![endif]--><?php if($totalSteps > 1): ?>
                        <div class="mt-4">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                     style="width: <?php echo e(($currentStep / $totalSteps) * 100); ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>

                <!--[if BLOCK]><![endif]--><?php if($validationErrors): ?>
                    <div class="px-8 py-4 bg-red-50 border-l-4 border-red-400">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $validationErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e(is_array($error) ? $error[0] : $error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!-- Questions - Always show all questions in single view -->
                <div class="px-8 py-6">
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="mb-8 <?php echo e($loop->last ? '' : 'border-b border-gray-200 pb-8'); ?>">
                            <?php echo $__env->make('livewire.partials.question-field', ['question' => $question, 'questionIndex' => $index], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                </div>

                <!-- Submit Button Only -->
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button
                        wire:click="submitResponse"
                        wire:loading.attr="disabled"
                        wire:target="submitResponse"
                        <?php if($submitting): ?> disabled <?php endif; ?>
                        class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg">
                        <span wire:loading.remove wire:target="submitResponse">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Submit Response
                        </span>
                        <span wire:loading wire:target="submitResponse" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Survey Not Found -->
            <div class="bg-white shadow-xl rounded-2xl border border-gray-200 p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Survey Not Available</h2>
                <p class="text-gray-600 mb-6">This survey is not currently available or does not exist.</p>
                <a href="/" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Return to Home
                </a>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/livewire/public-survey-form.blade.php ENDPATH**/ ?>