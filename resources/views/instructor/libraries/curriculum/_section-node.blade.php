@foreach($sections as $section)
    @php
        $matCount = $section->materials->count();
        $childCount = $section->treeChildren->count();
        $isEmpty = $matCount === 0 && $childCount === 0;
        $indent = (int) ($depth ?? 0);
    @endphp
    <section class="id-panel id-panel--wide" style="{{ $indent > 0 ? 'margin-inline-start: '.($indent * 1.25).'rem;' : '' }}">
        <header class="id-panel__head">
            <h2>{{ $section->title }}</h2>
            <span class="id-chip id-chip--muted">
                {{ $matCount === 1 ? __('instructor.lib_curriculum_materials_one') : __('instructor.lib_curriculum_materials_many', ['count' => $matCount]) }}
            </span>
        </header>
        @if($section->description)
            <p class="id-field__hint" style="margin:-4px 0 14px">{{ $section->description }}</p>
        @endif

        @if($matCount > 0)
            <div class="id-list">
                @foreach($section->materials as $material)
                    <div class="id-list__row">
                        <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ $material->displayTitle() }}</div>
                            <div class="id-list__meta">
                                {{ strtoupper((string) ($material->file_kind ?: __('instructor.lib_curriculum_file_fallback'))) }}
                                @if(empty($material->path))
                                    · {{ __('instructor.lib_curriculum_incomplete_file') }}
                                @endif
                            </div>
                        </div>
                        <div class="id-list__actions">
                            @if(! empty($material->path))
                                @if($material->file_kind === 'html' && $material->effectiveAllowViewInPlatform())
                                    <a href="{{ route('curriculum-library.material.html', [$item, $material]) }}" target="_blank" rel="noopener" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.view') }}</a>
                                @elseif($material->file_kind === 'pptx' && $material->effectiveAllowViewInPlatform())
                                    <a href="{{ route('curriculum-library.material.presentation', [$item, $material]) }}" target="_blank" rel="noopener" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.lib_curriculum_interactive_view') }}</a>
                                @elseif($material->file_kind === 'pdf' && $material->effectiveAllowViewInPlatform())
                                    <a href="{{ route('curriculum-library.material.pdf', [$item, $material]) }}" target="_blank" rel="noopener" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.view') }}</a>
                                @endif
                                @if($material->effectiveAllowDownload())
                                    <a href="{{ route('curriculum-library.material.download', [$item, $material]) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.download') }}</a>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($isEmpty)
            <p class="id-field__hint" style="margin:0">{{ __('instructor.lib_curriculum_no_materials') }}</p>
        @endif

        @if($childCount > 0)
            <div style="margin-top:12px;display:flex;flex-direction:column;gap:12px">
                @include('instructor.libraries.curriculum._section-node', [
                    'sections' => $section->treeChildren,
                    'item' => $item,
                    'depth' => $indent + 1,
                ])
            </div>
        @endif
    </section>
@endforeach
