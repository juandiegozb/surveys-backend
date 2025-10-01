<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Loading State -->
        <!--[if BLOCK]><![endif]--><?php if($loading): ?>
            <div class="flex justify-center items-center py-16">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500"></div>
                <span class="ml-4 text-lg text-gray-600">Loading survey details...</span>
            </div>
        <?php elseif($survey): ?>
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="<?php echo e(route('web.surveys.index')); ?>"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to Surveys
                        </a>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                <?php echo e($survey['status'] === 'active' ? 'bg-green-100 text-green-800 border border-green-200' : ''); ?>

                                <?php echo e($survey['status'] === 'draft' ? 'bg-gray-100 text-gray-800 border border-gray-200' : ''); ?>

                                <?php echo e($survey['status'] === 'paused' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : ''); ?>

                                <?php echo e($survey['status'] === 'completed' ? 'bg-blue-100 text-blue-800 border border-blue-200' : ''); ?>

                                <?php echo e($survey['status'] === 'archived' ? 'bg-red-100 text-red-800 border border-red-200' : ''); ?>">
                                <?php echo e(ucfirst($survey['status'])); ?>

                            </span>
                            <!--[if BLOCK]><![endif]--><?php if($survey['is_public'] ?? false): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Public
                                </span>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Survey Info Card -->
            <div class="bg-white shadow-xl rounded-2xl border border-gray-200 mb-8">
                <div class="p-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo e($survey['name']); ?></h1>
                    <!--[if BLOCK]><![endif]--><?php if($survey['description']): ?>
                        <p class="text-lg text-gray-600 mb-6"><?php echo e($survey['description']); ?></p>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!-- Public Survey Links -->
                    <!--[if BLOCK]><![endif]--><?php if($survey['status'] === 'active'): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-800 mb-3">Public Survey Links</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-white border border-green-200 rounded-md">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700">Full URL:</span>
                                        <code class="block text-sm text-green-600 mt-1"><?php echo e($this->getPublicUrl()); ?></code>
                                    </div>
                                    <button wire:click="copyToClipboard('<?php echo e($this->getPublicUrl()); ?>')"
                                            class="ml-2 p-2 text-green-600 hover:bg-green-100 rounded-md transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white border border-green-200 rounded-md">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700">Short URL:</span>
                                        <code class="block text-sm text-green-600 mt-1"><?php echo e($this->getShortUrl()); ?></code>
                                    </div>
                                    <button wire:click="copyToClipboard('<?php echo e($this->getShortUrl()); ?>')"
                                            class="ml-2 p-2 text-green-600 hover:bg-green-100 rounded-md transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!-- Survey Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600"><?php echo e(count($questions)); ?></div>
                            <div class="text-sm text-blue-800">Questions</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?php echo e($survey['response_count'] ?? 0); ?></div>
                            <div class="text-sm text-green-800">Responses</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600"><?php echo e(\Carbon\Carbon::parse($survey['created_at'])->format('M d, Y')); ?></div>
                            <div class="text-sm text-purple-800">Created</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600"><?php echo e($survey['is_public'] ? 'Public' : 'Private'); ?></div>
                            <div class="text-sm text-yellow-800">Visibility</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <!--[if BLOCK]><![endif]--><?php if(count($questions) > 0 && ($survey['response_count'] ?? 0) > 0): ?>
                            <button wire:click="openResponsesModal"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                View Responses (<?php echo e($survey['response_count'] ?? 0); ?>)
                            </button>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <!--[if BLOCK]><![endif]--><?php if($survey['status'] === 'active'): ?>
                            <a href="<?php echo e($this->getPublicUrl()); ?>" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Preview Survey
                            </a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </div>
            </div>

            <!-- Questions Section -->
            <?php if(count($questions) > 0): ?>
                <div class="bg-white shadow-xl rounded-2xl border border-gray-200">
                    <div class="p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Survey Questions</h2>
                        <div class="space-y-6">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div wire:key="question-<?php echo e($question['uuid']); ?>" class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <span class="flex items-center justify-center w-8 h-8 bg-blue-500 text-white rounded-full text-sm font-semibold">
                                                <?php echo e($index + 1); ?>

                                            </span>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900"><?php echo e($question['name'] ?? 'Question ' . ($index + 1)); ?></h3>
                                                <!--[if BLOCK]><![endif]--><?php if($question['is_required'] ?? false): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Required
                                                    </span>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <?php echo e($question['question_type']['name'] ?? 'Text'); ?>

                                            </span>
                                            <!-- Delete Question Button -->
                                            <button wire:key="delete-btn-<?php echo e($question['uuid']); ?>"
                                                    wire:click="removeQuestionFromSurvey('<?php echo e($question['uuid']); ?>')"
                                                    wire:confirm="Are you sure you want to remove this question from the survey? This action cannot be undone."
                                                    class="inline-flex items-center p-2 border border-red-300 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 hover:border-red-400 transition-colors"
                                                    title="Remove question from survey">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-gray-700 mb-4"><?php echo e($question['question_text']); ?></p>

                                    <!--[if BLOCK]><![endif]--><?php if(isset($question['options']) && count($question['options']) > 0): ?>
                                        <div class="mt-4">
                                            <p class="text-sm font-medium text-gray-700 mb-2">Answer Options:</p>
                                            <div class="space-y-2">
                                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $question['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-6 h-6 flex items-center justify-center bg-gray-200 text-gray-600 rounded-full text-xs">
                                                            <?php echo e(chr(65 + $optionIndex)); ?>

                                                        </span>
                                                        <span class="text-gray-700"><?php echo e($option); ?></span>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>
                                        </div>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Questions State -->
                <div class="bg-white shadow-xl rounded-2xl border border-gray-200">
                    <div class="text-center py-16 px-6">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No Questions Yet</h3>
                        <p class="text-gray-600 mb-6">This survey doesn't have any questions yet. Add some questions to get started.</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button wire:click="$set('showCreateQuestionModal', true)"
                               class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create New Question
                            </button>
                            <button wire:click="openLinkQuestionsModal"
                               class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Link Existing Questions
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            <!-- Add Question Button for existing questions -->
            <?php if(count($questions) > 0): ?>
                <div class="mt-6 text-center space-x-4">
                    <button wire:click="$set('showCreateQuestionModal', true)"
                           class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create New Question
                    </button>
                    <button wire:click="openLinkQuestionsModal"
                           class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Link Existing Questions
                    </button>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php else: ?>
            <!-- Survey Not Found -->
            <div class="bg-white shadow-xl rounded-2xl border border-gray-200">
                <div class="text-center py-16 px-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Survey Not Found</h3>
                    <p class="text-gray-600 mb-6">The survey you're looking for doesn't exist or has been deleted.</p>
                    <a href="<?php echo e(route('web.surveys.index')); ?>"
                       class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Surveys
                    </a>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!-- Create Question Modal -->
    <!--[if BLOCK]><![endif]--><?php if($showCreateQuestionModal): ?>
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeModals">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 transform transition-all" wire:click.stop>
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-500 p-2 rounded-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Add Question to Survey</h3>
                    </div>
                    <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit.prevent="createQuestion" class="px-8 py-6 space-y-6">
                <div>
                    <label for="questionName" class="block text-sm font-semibold text-gray-700 mb-2">Question Name *</label>
                    <input
                        type="text"
                        id="questionName"
                        wire:model="questionName"
                        placeholder="e.g., Overall Satisfaction"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        required>
                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['questionName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                </div>

                <div>
                    <label for="questionText" class="block text-sm font-semibold text-gray-700 mb-2">Question Text *</label>
                    <textarea
                        id="questionText"
                        wire:model="questionText"
                        rows="3"
                        placeholder="Enter the question you want to ask..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        required></textarea>
                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['questionText'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="questionTypeId" class="block text-sm font-semibold text-gray-700 mb-2">Question Type</label>
                        <select
                            id="questionTypeId"
                            wire:model="questionTypeId"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <!--[if BLOCK]><![endif]--><?php if(count($questionTypes) > 0): ?>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $questionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type['id']); ?>"><?php echo e($type['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            <?php else: ?>
                                <option value="1">Text</option>
                                <option value="2">Multiple Choice</option>
                                <option value="3">Rating</option>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </select>
                    </div>

                    <div class="flex items-center pt-8">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="isRequired"
                                wire:model="isRequired"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="isRequired" class="ml-3 block text-sm font-medium text-gray-700">Required Question</label>
                        </div>
                    </div>
                </div>

                <!-- Options for Multiple Choice -->
                <!--[if BLOCK]><![endif]--><?php if(in_array($questionTypeId, [2, 3])): ?> <!-- Multiple Choice or Rating -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Answer Options</label>
                        <div class="space-y-3">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-center space-x-2">
                                    <span class="flex items-center justify-center w-6 h-6 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">
                                        <?php echo e(chr(65 + $index)); ?>

                                    </span>
                                    <input
                                        type="text"
                                        wire:model="options.<?php echo e($index); ?>"
                                        placeholder="Enter option text..."
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <!--[if BLOCK]><![endif]--><?php if(count($options) > 1): ?>
                                        <button
                                            type="button"
                                            wire:click="removeOption(<?php echo e($index); ?>)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                        <button
                            type="button"
                            wire:click="addOption"
                            class="mt-3 inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Option
                        </button>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <div class="flex space-x-4 pt-6 border-t border-gray-200">
                    <button
                        type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3 px-6 rounded-lg text-sm font-semibold transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Create Question
                    </button>
                    <button
                        type="button"
                        wire:click="closeModals"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-6 rounded-lg text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- Link Questions Modal -->
    <!--[if BLOCK]><![endif]--><?php if($showLinkQuestionsModal): ?>
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeModals">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 transform transition-all" wire:click.stop>
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-500 p-2 rounded-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Link Existing Questions</h3>
                    </div>
                    <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-8 py-6">
                <!-- Search and Filter Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="searchAvailable" class="block text-sm font-semibold text-gray-700 mb-2">Search Questions</label>
                        <input
                            type="text"
                            id="searchAvailable"
                            wire:model.live.debounce.300ms="searchAvailable"
                            placeholder="Search by name or question text..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label for="filterQuestionType" class="block text-sm font-semibold text-gray-700 mb-2">Filter by Type</label>
                        <select
                            id="filterQuestionType"
                            wire:model.live="filterQuestionType"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="">All Question Types</option>
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $questionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type['id']); ?>"><?php echo e($type['display_name'] ?? $type['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </select>
                    </div>
                </div>

                <!-- Bulk Selection Controls -->
                <div class="flex items-center justify-between mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo e(count($selectedQuestionUuids)); ?> of <?php echo e(count($availableQuestions)); ?> questions selected
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            type="button"
                            wire:click="selectAllQuestions"
                            class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors">
                            Select All
                        </button>
                        <button
                            type="button"
                            wire:click="deselectAllQuestions"
                            class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                            Deselect All
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <!--[if BLOCK]><![endif]--><?php if($loadingAvailable): ?>
                    <div class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        <span class="ml-3 text-gray-600">Loading available questions...</span>
                    </div>
                <?php else: ?>
                    <!-- Questions List -->
                    <div class="max-h-96 overflow-y-auto mb-6 border border-gray-200 rounded-lg">
                        <!--[if BLOCK]><![endif]--><?php if(count($availableQuestions) > 0): ?>
                            <div class="divide-y divide-gray-200">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $availableQuestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                                         wire:click="toggleQuestionSelection('<?php echo e($question['uuid']); ?>')">
                                        <div class="flex items-start space-x-3">
                                            <input
                                                type="checkbox"
                                                checked="<?php echo e(in_array($question['uuid'], $selectedQuestionUuids)); ?>"
                                                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <h4 class="text-sm font-semibold text-gray-900">
                                                        <?php echo e($question['name']); ?>

                                                    </h4>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <?php echo e($question['question_type']['display_name'] ?? $question['question_type']['name'] ?? 'Text'); ?>

                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    <?php echo e(Str::limit($question['question_text'], 100)); ?>

                                                </p>
                                                <!--[if BLOCK]><![endif]--><?php if($question['is_required'] ?? false): ?>
                                                    <span class="inline-flex items-center mt-2 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Required
                                                    </span>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                <!--[if BLOCK]><![endif]--><?php if(isset($question['options']) && count($question['options']) > 0): ?>
                                                    <div class="mt-2">
                                                        <span class="text-xs text-gray-500">
                                                            Options: <?php echo e(implode(', ', array_slice($question['options'], 0, 3))); ?>

                                                            <!--[if BLOCK]><![endif]--><?php if(count($question['options']) > 3): ?>
                                                                <span class="font-medium">... +<?php echo e(count($question['options']) - 3); ?> more</span>
                                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                        </span>
                                                    </div>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="h-12 w-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No available questions</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <!--[if BLOCK]><![endif]--><?php if($searchAvailable || $filterQuestionType): ?>
                                        No questions match your search criteria.
                                    <?php else: ?>
                                        All existing questions are already linked to this survey.
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </p>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-6 border-t border-gray-200">
                    <button
                        type="button"
                        wire:click="linkSelectedQuestions"
                        <?php if(empty($selectedQuestionUuids)): echo 'disabled'; endif; ?>
                        class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg text-sm font-semibold transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <?php if(empty($selectedQuestionUuids)): ?>
                            Select Questions to Link
                        <?php else: ?>
                            Link <?php echo e(count($selectedQuestionUuids)); ?> Question<?php echo e(count($selectedQuestionUuids) > 1 ? 's' : ''); ?>

                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </button>
                    <button
                        type="button"
                        wire:click="closeModals"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-6 rounded-lg text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- Responses Modal -->
    <!--[if BLOCK]><![endif]--><?php if($showResponsesModal): ?>
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeResponsesModal">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden" wire:click.stop>
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-500 p-2 rounded-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Survey Responses</h3>
                    </div>
                    <button wire:click="closeResponsesModal" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-8 py-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!--[if BLOCK]><![endif]--><?php if($loadingResponses): ?>
                    <div class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                        <span class="ml-3 text-gray-600">Loading responses...</span>
                    </div>
                <?php elseif(count($responsesByRespondent) > 0): ?>
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600"><?php echo e(count($responses)); ?></div>
                            <div class="text-sm text-blue-800">Total Responses</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?php echo e(count($responsesByRespondent)); ?></div>
                            <div class="text-sm text-green-800">Unique Respondents</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600"><?php echo e(count($questions) > 0 ? round((count($responses) / count($responsesByRespondent)) / count($questions) * 100, 1) : 0); ?>%</div>
                            <div class="text-sm text-purple-800">Completion Rate</div>
                        </div>
                    </div>

                    <!-- Responses by Respondent -->
                    <div class="space-y-6">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $responsesByRespondent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $respondentId => $respondentResponses): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            Respondent: <?php echo e(Str::limit($respondentId, 20)); ?>

                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo e(count($respondentResponses)); ?> responses •
                                            Submitted <?php echo e(\Carbon\Carbon::parse($respondentResponses[0]['submitted_at'] ?? now())->diffForHumans()); ?>

                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo e(round((count($respondentResponses) / count($questions)) * 100, 1)); ?>% Complete
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $respondentResponses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-start justify-between mb-2">
                                                <h5 class="font-medium text-gray-900">
                                                    <?php echo e($response['question']['name'] ?? 'Question'); ?>

                                                </h5>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?php echo e(ucfirst($response['question']['type'] ?? 'text')); ?>

                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-3"><?php echo e($response['question']['question_text'] ?? ''); ?></p>

                                            <!-- Answer Display -->
                                            <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                                                <!--[if BLOCK]><![endif]--><?php if($response['answer_text']): ?>
                                                    <p class="text-sm text-gray-900"><?php echo e($response['answer_text']); ?></p>
                                                <?php elseif($response['answer_data']): ?>
                                                    <!--[if BLOCK]><![endif]--><?php if(isset($response['answer_data']['selected_options'])): ?>
                                                        <!-- Multiple selections (checkbox) -->
                                                        <div class="flex flex-wrap gap-2">
                                                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $response['answer_data']['selected_options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    <?php echo e($option); ?>

                                                                </span>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                                        </div>
                                                    <?php elseif(isset($response['answer_data']['selected_option'])): ?>
                                                        <!-- Single selection -->
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?php echo e($response['answer_data']['selected_option']); ?>

                                                        </span>
                                                    <?php elseif(isset($response['answer_data']['numeric_value'])): ?>
                                                        <!-- Numeric value -->
                                                        <span class="text-lg font-semibold text-blue-600"><?php echo e($response['answer_data']['numeric_value']); ?></span>
                                                    <?php else: ?>
                                                        <pre class="text-xs text-gray-600"><?php echo e(json_encode($response['answer_data'], JSON_PRETTY_PRINT)); ?></pre>
                                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                                <!-- File attachments -->
                                                <!--[if BLOCK]><![endif]--><?php if($response['has_attachments']): ?>
                                                    <div class="mt-2 pt-2 border-t border-blue-200">
                                                        <p class="text-xs font-medium text-blue-800 mb-1">Attachments:</p>
                                                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $response['attachments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a href="<?php echo e($attachment); ?>" target="_blank"
                                                               class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                                                                <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                                </svg>
                                                                View File
                                                            </a>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                                    </div>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php else: ?>
                    <!-- No responses -->
                    <div class="text-center py-12">
                        <svg class="h-12 w-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No Responses Yet</h3>
                        <p class="mt-1 text-sm text-gray-500">This survey hasn't received any responses yet.</p>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>
    </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>

<!-- Copy to Clipboard Script -->
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('copy-to-clipboard', (event) => {
        navigator.clipboard.writeText(event.url).then(() => {
            console.log('URL copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy URL: ', err);
        });
    });
});
</script>
<?php /**PATH /var/www/html/resources/views/livewire/survey-details.blade.php ENDPATH**/ ?>