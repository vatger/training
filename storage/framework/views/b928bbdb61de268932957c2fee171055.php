<?php
    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributeBag = $getExtraAttributeBag();
    $canEditKeys = $canEditKeys();
    $canEditValues = $canEditValues();
    $keyPlaceholder = $getKeyPlaceholder();
    $valuePlaceholder = $getValuePlaceholder();
    $debounce = $getLiveDebounce();
    $isAddable = $isAddable();
    $isDeletable = $isDeletable();
    $isDisabled = $isDisabled();
    $isReorderable = $isReorderable();
    $statePath = $getStatePath();
    $livewireKey = $getLivewireKey();
?>

<?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $fieldWrapperView] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => $field,'class' => 'fi-fo-key-value-wrp']); ?>
    <?php if (isset($component)) { $__componentOriginal505efd9768415fdb4543e8c564dad437 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal505efd9768415fdb4543e8c564dad437 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.input.wrapper','data' => ['disabled' => $isDisabled,'valid' => ! $errors->has($statePath),'attributes' => 
            \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                ->class(['fi-fo-key-value'])
        ]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::input.wrapper'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isDisabled),'valid' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(! $errors->has($statePath)),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(
            \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                ->class(['fi-fo-key-value'])
        )]); ?>
        <div
            x-load
            x-load-src="<?php echo e(\Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('key-value', 'filament/forms')); ?>"
            x-data="keyValueFormComponent({
                        state: $wire.<?php echo e($applyStateBindingModifiers("\$entangle('{$statePath}')")); ?>,
                    })"
            wire:ignore
            wire:key="<?php echo e($livewireKey); ?>.<?php echo e(substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)); ?>"
            <?php echo e($attributes
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class(['fi-fo-key-value-table-ctn'])); ?>

        >
            <table class="fi-fo-key-value-table">
                <thead>
                    <tr>
                        <!--[if BLOCK]><![endif]--><?php if($isReorderable && (! $isDisabled)): ?>
                            <th
                                scope="col"
                                x-show="rows.length"
                                class="fi-has-action"
                            ></th>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <th scope="col">
                            <?php echo e($getKeyLabel()); ?>

                        </th>

                        <th scope="col">
                            <?php echo e($getValueLabel()); ?>

                        </th>

                        <!--[if BLOCK]><![endif]--><?php if($isDeletable && (! $isDisabled)): ?>
                            <th
                                scope="col"
                                x-show="rows.length"
                                class="fi-has-action"
                            ></th>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </tr>
                </thead>

                <tbody
                    <?php if($isReorderable): ?>
                        x-on:end.stop="reorderRows($event)"
                        x-sortable
                        data-sortable-animation-duration="<?php echo e($getReorderAnimationDuration()); ?>"
                    <?php endif; ?>
                >
                    <template
                        x-bind:key="index"
                        x-for="(row, index) in rows"
                    >
                        <tr
                            <?php if($isReorderable): ?>
                                x-bind:x-sortable-item="row.key"
                            <?php endif; ?>
                        >
                            <!--[if BLOCK]><![endif]--><?php if($isReorderable && (! $isDisabled)): ?>
                                <td class="fi-has-action">
                                    <div
                                        x-sortable-handle
                                        class="fi-fo-key-value-table-row-sortable-handle"
                                    >
                                        <?php echo e($getAction('reorder')); ?>

                                    </div>
                                </td>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <td>
                                <input
                                    <?php if((! $canEditKeys) || $isDisabled): echo 'disabled'; endif; ?>
                                    placeholder="<?php echo e($keyPlaceholder); ?>"
                                    type="text"
                                    x-model="row.key"
                                    x-on:input.debounce.<?php echo e($debounce ?? '500ms'); ?>="updateState"
                                    class="fi-input"
                                />
                            </td>

                            <td>
                                <input
                                    <?php if((! $canEditValues) || $isDisabled): echo 'disabled'; endif; ?>
                                    placeholder="<?php echo e($valuePlaceholder); ?>"
                                    type="text"
                                    x-model="row.value"
                                    x-on:input.debounce.<?php echo e($debounce ?? '500ms'); ?>="updateState"
                                    class="fi-input"
                                />
                            </td>

                            <!--[if BLOCK]><![endif]--><?php if($isDeletable && (! $isDisabled)): ?>
                                <td class="fi-has-action">
                                    <div x-on:click="deleteRow(index)">
                                        <?php echo e($getAction('delete')); ?>

                                    </div>
                                </td>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </tr>
                    </template>
                </tbody>
            </table>

            <!--[if BLOCK]><![endif]--><?php if($isAddable && (! $isDisabled)): ?>
                <div
                    x-on:click="addRow"
                    class="fi-fo-key-value-add-action-ctn"
                >
                    <?php echo e($getAction('add')); ?>

                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal505efd9768415fdb4543e8c564dad437)): ?>
<?php $attributes = $__attributesOriginal505efd9768415fdb4543e8c564dad437; ?>
<?php unset($__attributesOriginal505efd9768415fdb4543e8c564dad437); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal505efd9768415fdb4543e8c564dad437)): ?>
<?php $component = $__componentOriginal505efd9768415fdb4543e8c564dad437; ?>
<?php unset($__componentOriginal505efd9768415fdb4543e8c564dad437); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php /**PATH /Users/benboeckmann/Documents/Projects/training-rework/vendor/filament/forms/resources/views/components/key-value.blade.php ENDPATH**/ ?>