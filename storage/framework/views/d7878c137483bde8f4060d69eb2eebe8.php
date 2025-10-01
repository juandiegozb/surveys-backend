<!-- Question Field Partial -->
<div class="space-y-4">
    <!-- Question Header -->
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900">
                <?php echo e($question['name']); ?>

                <!--[if BLOCK]><![endif]--><?php if($question['is_required']): ?>
                    <span class="text-red-500">*</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </h3>
            <p class="mt-1 text-sm text-gray-600"><?php echo e($question['question_text']); ?></p>
        </div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            <?php echo e(ucfirst($question['question_type']['name'] ?? 'text')); ?>

        </span>
    </div>

    <!-- Question Input Field -->
    <div class="mt-4">
        <?php
            $questionType = $question['question_type']['name'] ?? 'text';
            $questionUuid = $question['uuid'];
        ?>

        <!--[if BLOCK]><![endif]--><?php switch($questionType):
            case ('text'): ?>
            <?php case ('email'): ?>
            <?php case ('url'): ?>
                <input
                    type="<?php echo e($questionType === 'email' ? 'email' : ($questionType === 'url' ? 'url' : 'text')); ?>"
                    wire:model="responses.<?php echo e($questionUuid); ?>"
                    placeholder="Enter your answer..."
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : 'border-gray-300'); ?>"
                    <?php if($question['is_required']): ?> required <?php endif; ?>>
                <?php break; ?>

            <?php case ('textarea'): ?>
                <textarea
                    wire:model="responses.<?php echo e($questionUuid); ?>"
                    rows="4"
                    placeholder="Enter your answer..."
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-vertical <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : 'border-gray-300'); ?>"
                    <?php if($question['is_required']): ?> required <?php endif; ?>></textarea>
                <?php break; ?>

            <?php case ('number'): ?>
                <input
                    type="number"
                    wire:model="responses.<?php echo e($questionUuid); ?>"
                    placeholder="Enter a number..."
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : 'border-gray-300'); ?>"
                    <?php if($question['is_required']): ?> required <?php endif; ?>>
                <?php break; ?>

            <?php case ('rating'): ?>
                <div class="flex items-center space-x-2">
                    <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= 5; $i++): ?>
                        <button
                            type="button"
                            wire:click="$set('responses.<?php echo e($questionUuid); ?>', <?php echo e($i); ?>)"
                            class="p-2 rounded-full transition-colors <?php echo e((int)($responses[$questionUuid] ?? 0) >= $i ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-300'); ?>">
                            <svg class="h-8 w-8 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </button>
                    <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->
                    <!--[if BLOCK]><![endif]--><?php if($responses[$questionUuid] ?? 0): ?>
                        <span class="ml-4 text-sm text-gray-600"><?php echo e($responses[$questionUuid]); ?> out of 5</span>
                        <button
                            type="button"
                            wire:click="$set('responses.<?php echo e($questionUuid); ?>', null)"
                            class="ml-2 text-sm text-red-600 hover:text-red-800 underline">
                            Clear
                        </button>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
                <!--[if BLOCK]><![endif]--><?php if(isset($validationErrors['responses.'.$questionUuid])): ?>
                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                        <p class="text-sm text-red-600">Please select a rating</p>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php break; ?>

            <?php case ('multiple_choice'): ?>
            <?php case ('multiple-choice'): ?>
            <?php case ('radio'): ?>
                <!--[if BLOCK]><![endif]--><?php if(isset($question['options']) && count($question['options']) > 0): ?>
                    <div class="space-y-3 <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'p-3 bg-red-50 border border-red-200 rounded' : ''); ?>">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $question['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="radio"
                                    wire:model="responses.<?php echo e($questionUuid); ?>"
                                    value="<?php echo e($option); ?>"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    <?php if($question['is_required']): ?> required <?php endif; ?>>
                                <span class="ml-3 text-sm text-gray-700"><?php echo e($option); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">No options available for this question.</p>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php break; ?>

            <?php case ('checkbox'): ?>
                <!--[if BLOCK]><![endif]--><?php if(isset($question['options']) && count($question['options']) > 0): ?>
                    <div class="space-y-3 <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'p-3 bg-red-50 border border-red-200 rounded' : ''); ?>">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $question['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="responses.<?php echo e($questionUuid); ?>"
                                    value="<?php echo e($option); ?>"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-3 text-sm text-gray-700"><?php echo e($option); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">No options available for this question.</p>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php break; ?>

            <?php case ('dropdown'): ?>
            <?php case ('select'): ?>
                <!--[if BLOCK]><![endif]--><?php if(isset($question['options']) && count($question['options']) > 0): ?>
                    <select
                        wire:model="responses.<?php echo e($questionUuid); ?>"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : 'border-gray-300'); ?>"
                        <?php if($question['is_required']): ?> required <?php endif; ?>>
                        <option value="">Select an option...</option>
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $question['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>"><?php echo e($option); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </select>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">No options available for this question.</p>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php break; ?>

            <?php case ('file-upload'): ?>
            <?php case ('file'): ?>
            <?php case ('attachment'): ?>
                <div class="space-y-4">
                    <!--[if BLOCK]><![endif]--><?php if(!isset($uploadedFiles[$questionUuid]) || empty($uploadedFiles[$questionUuid])): ?>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : ''); ?>">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 48 48" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21l3-3 7 7 7-7 3 3m-9-4v12m0 0H9m9 0h9"/>
                            </svg>
                            <div class="mt-4">
                                <label for="file-<?php echo e($questionUuid); ?>" class="cursor-pointer inline-block">
                                    <span class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        Choose File
                                    </span>
                                    <input
                                        id="file-<?php echo e($questionUuid); ?>"
                                        type="file"
                                        wire:model.live="uploadedFiles.<?php echo e($questionUuid); ?>"
                                        class="sr-only"
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.csv,.xlsx"
                                        <?php if($question['is_required']): ?> required <?php endif; ?>>
                                </label>
                                <p class="mt-2 text-sm text-gray-500">
                                    Select PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT, CSV, XLSX (max 10MB)
                                </p>
                            </div>

                            <!-- Loading indicator for file upload -->
                            <div wire:loading wire:target="uploadedFiles.<?php echo e($questionUuid); ?>" class="mt-4">
                                <div class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-blue-600">Uploading file...</span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">
                                            <?php echo e($uploadedFiles[$questionUuid]->getClientOriginalName()); ?>

                                        </p>
                                        <p class="text-xs text-green-600">
                                            <?php echo e(number_format($uploadedFiles[$questionUuid]->getSize() / 1024 / 1024, 2)); ?> MB
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeFile('<?php echo e($questionUuid); ?>')"
                                    class="text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Option to replace file -->
                        <div class="text-center">
                            <label for="file-replace-<?php echo e($questionUuid); ?>" class="cursor-pointer text-sm text-blue-600 hover:text-blue-800 underline">
                                Replace file
                                <input
                                    id="file-replace-<?php echo e($questionUuid); ?>"
                                    type="file"
                                    wire:model.live="uploadedFiles.<?php echo e($questionUuid); ?>"
                                    class="sr-only"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.csv,.xlsx">
                            </label>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!-- File Upload Errors -->
                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['uploadedFiles.' . $questionUuid];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-sm text-red-600 mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php if(isset($validationErrors["uploadedFiles.{$questionUuid}"])): ?>
                        <p class="text-sm text-red-600 mt-2"><?php echo e($validationErrors["uploadedFiles.{$questionUuid}"]); ?></p>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
                <?php break; ?>

            <?php default: ?>
                <!-- Fallback for unknown question types -->
                <input
                    type="text"
                    wire:model="responses.<?php echo e($questionUuid); ?>"
                    placeholder="Enter your answer..."
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors <?php echo e(isset($validationErrors['responses.'.$questionUuid]) ? 'border-red-500 bg-red-50' : 'border-gray-300'); ?>"
                    <?php if($question['is_required']): ?> required <?php endif; ?>>
        <?php endswitch; ?><!--[if ENDBLOCK]><![endif]-->

        <!-- Field Error Messages -->
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["responses.{$questionUuid}"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><?php if(isset($validationErrors["responses.{$questionUuid}"])): ?>
            <p class="mt-2 text-sm text-red-600"><?php echo e($validationErrors["responses.{$questionUuid}"]); ?></p>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <!-- File Upload Error Messages -->
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["uploadedFiles.{$questionUuid}"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-sm text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><?php if(isset($validationErrors["uploadedFiles.{$questionUuid}"])): ?>
            <p class="text-sm text-red-600"><?php echo e($validationErrors["uploadedFiles.{$questionUuid}"]); ?></p>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/livewire/partials/question-field.blade.php ENDPATH**/ ?>