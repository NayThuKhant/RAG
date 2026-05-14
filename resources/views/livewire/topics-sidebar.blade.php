<div
    @if($hasProcessing) wire:poll.5s @endif
    x-data="{
        confirm: { open: false, title: '', message: '', label: 'Confirm', action: null },
        openConfirm(title, message, label, action) {
            this.confirm = { open: true, title, message, label, action };
        },
        runConfirm() {
            if (this.confirm.action) this.confirm.action();
            this.confirm.open = false;
        },
        closeConfirm() { this.confirm.open = false; },
        activeModal: null,
        openModal(name) { this.activeModal = name; },
        closeModal() { this.activeModal = null; }
    }"
    @keydown.escape.window="confirm.open ? closeConfirm() : closeModal()"
    @modal-closed.window="closeModal()"
    class="h-full"
>
    {{-- Mobile backdrop --}}
    <div
        x-show="$store.sidebar.open"
        x-on:click="$store.sidebar.open = false"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="lg:hidden fixed inset-0 bg-black/30 z-20"
        style="display: none;"
    ></div>

    <aside
        :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 lg:relative lg:inset-auto lg:z-auto lg:h-full lg:translate-x-0 w-72 flex flex-col bg-white dark:bg-gemini-800 border-r border-gray-200 dark:border-gemini-500 transition-transform duration-200"
    >
        {{-- Header --}}
        <div class="shrink-0 h-16 px-4 border-b border-gray-100 dark:border-gemini-500 flex items-center justify-between gap-2">
            <a href="{{ route('home') }}" wire:navigate class="text-sm font-semibold text-gray-900 dark:text-white truncate hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                RAG
            </a>
            <div class="flex items-center gap-0.5 shrink-0">
                <button
                    x-data="{
                        dark: document.documentElement.classList.contains('dark'),
                        toggle() {
                            this.dark = !this.dark;
                            document.documentElement.classList.toggle('dark', this.dark);
                            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                        }
                    }"
                    @click="toggle()"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gemini-400 dark:hover:text-gemini-200 hover:bg-gray-100 dark:hover:bg-gemini-700 transition-colors cursor-pointer"
                    title="Toggle dark mode"
                >
                    <svg x-show="!dark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="dark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
                <button
                    @click="$store.sidebar.open = false"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gemini-400 dark:hover:text-gemini-200 hover:bg-gray-100 dark:hover:bg-gemini-700 transition-colors cursor-pointer"
                    title="Collapse sidebar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- New topic button --}}
        <div class="shrink-0 px-3 py-2.5">
            <button
                @click="openModal('create-topic')"
                class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Topic
            </button>
        </div>

        {{-- Topics list --}}
        <div class="flex-1 min-h-0 overflow-y-auto py-2">
            @forelse($topics as $topic)
                @php
                    $docParts = array_filter([
                        $topic->pending_documents_count > 0 ? $topic->pending_documents_count . ' pending' : null,
                        $topic->processing_documents_count > 0 ? $topic->processing_documents_count . ' processing' : null,
                        $topic->ready_documents_count > 0 ? $topic->ready_documents_count . ' ready' : null,
                        $topic->failed_documents_count > 0 ? $topic->failed_documents_count . ' failed' : null,
                    ]);
                    $docSummary = $docParts ? implode(' / ', $docParts) : '0 docs';
                @endphp

                <div wire:key="sidebar-topic-{{ $topic->id }}" class="px-2 mb-0.5" @if($currentTopicId === $topic->id) x-init="$nextTick(() => $el.scrollIntoView({ block: 'nearest' }))" @endif>
                    <div class="rounded-lg {{ $currentTopicId === $topic->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">

                        {{-- Name --}}
                        <div class="px-2 pt-1.5 pb-0.5">
                            <span class="text-sm font-medium truncate block {{ $currentTopicId === $topic->id ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-800 dark:text-gemini-100' }}">{{ $topic->name }}</span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-0.5 px-1 pb-0.5">
                            <span class="flex-1"></span>
                            <button
                                wire:click="openTopicChat({{ $topic->id }})"
                                title="Chat"
                                class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $currentTopicId === $topic->id ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gemini-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20' }}"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </button>
                            <button
                                @click="$wire.startEditTopic({{ $topic->id }}).then(() => openModal('edit-topic'))"
                                title="Edit topic"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-gemini-400 hover:text-gray-600 dark:hover:text-gemini-200 hover:bg-gray-100 dark:hover:bg-gemini-700 transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                @click="openConfirm('Delete topic', 'All documents and chat history for this topic will be permanently deleted.', 'Delete', () => $wire.deleteTopic({{ $topic->id }}))"
                                title="Delete topic"
                                class="p-1.5 rounded-lg text-gray-400 dark:text-gemini-400 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Expand toggle --}}
                        <button
                            wire:click="toggleTopic({{ $topic->id }})"
                            class="w-full flex items-center gap-1.5 px-2 py-1 text-left hover:bg-gray-100 dark:hover:bg-gemini-700 rounded-lg transition-colors cursor-pointer mb-0.5"
                        >
                            <svg class="w-3 h-3 shrink-0 text-gray-400 dark:text-gemini-400 transition-transform duration-150 {{ $expandedTopicId === $topic->id ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-xs text-gray-400 dark:text-gemini-400">{{ $docSummary }}</span>
                        </button>
                    </div>

                    {{-- Expanded documents --}}
                    @if($expandedTopicId === $topic->id)
                        <div class="mt-1 mb-2">
                            {{-- Upload button --}}
                            <div class="px-1 mb-2">
                                <button
                                    @click="openModal('upload-doc')"
                                    class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-dashed border-indigo-300 dark:border-indigo-700/60 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Upload Document
                                </button>
                            </div>

                            @forelse($expandedDocuments as $doc)
                                <div wire:key="sidebar-doc-{{ $doc->id }}" class="px-2 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gemini-700 transition-colors">
                                    @if($doc->status === \App\Enums\DocumentStatus::Ready || $doc->status === \App\Enums\DocumentStatus::Failed)
                                        <a href="{{ route('topics.show', $topic) }}?view={{ $doc->id }}" wire:navigate class="text-xs font-medium text-gray-700 dark:text-gemini-200 truncate leading-snug hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline block">{{ $doc->filename }}</a>
                                    @else
                                        <p class="text-xs text-gray-600 dark:text-gemini-300 truncate leading-snug">{{ $doc->filename }}</p>
                                    @endif

                                    <div class="flex items-center gap-1 mt-0.5">
                                        @if($doc->status === \App\Enums\DocumentStatus::Ready)
                                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-500 mr-auto">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                                                {{ $doc->chunk_count }} chunks
                                            </span>
                                        @elseif($doc->status === \App\Enums\DocumentStatus::Failed)
                                            <span class="inline-flex items-center gap-1 text-xs text-red-500 mr-auto" title="{{ $doc->error_message }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                                {{ $doc->status->label() }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs mr-auto {{ $doc->status === \App\Enums\DocumentStatus::Processing ? 'text-yellow-600 dark:text-yellow-500' : 'text-gray-400 dark:text-gemini-400' }}">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0 animate-pulse {{ $doc->status === \App\Enums\DocumentStatus::Processing ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gemini-500' }}"></span>
                                                {{ $doc->status->label() }}
                                            </span>
                                        @endif

                                        @if($doc->status === \App\Enums\DocumentStatus::Ready || $doc->status === \App\Enums\DocumentStatus::Failed)
                                            <button
                                                @click="openConfirm('Re-process document', 'This will re-queue the document for processing from scratch.', 'Re-process', () => $wire.retryDocument({{ $doc->id }}))"
                                                title="Re-process document"
                                                class="p-1 text-gray-400 dark:text-gemini-400 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors cursor-pointer shrink-0"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        @endif

                                        <button
                                            @click="openConfirm('Delete document', 'This document and all its chunks will be permanently deleted.', 'Delete', () => $wire.deleteDocument({{ $doc->id }}))"
                                            title="Delete document"
                                            class="p-1 text-gray-400 dark:text-gemini-400 hover:text-red-400 transition-colors cursor-pointer shrink-0"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 dark:text-gemini-500 px-2 py-2">No documents yet.</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gemini-400">No topics yet</p>
                    <p class="text-xs text-gray-400 dark:text-gemini-500 mt-1">Create one to get started.</p>
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Shared modal backdrop + panel template --}}
    @php
        $modalTransitions = '
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ';
        $panelTransitions = '
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
        ';
        $inputClass = 'w-full border border-gray-200 dark:border-gemini-600 bg-gray-50 dark:bg-gemini-800 text-gray-900 dark:text-gemini-100 placeholder:text-gray-400 dark:placeholder:text-gemini-500 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent';
        $cancelBtn = 'flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gemini-200 bg-gray-100 dark:bg-gemini-600 hover:bg-gray-200 dark:hover:bg-gemini-500 rounded-xl transition-colors cursor-pointer';
        $primaryBtn = 'flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors cursor-pointer';
    @endphp

    {{-- Confirm modal --}}
    <div
        x-show="confirm.open"
        style="display: none;"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
    >
        <div @click="closeConfirm()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div
            x-show="confirm.open"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            class="relative bg-white dark:bg-gemini-700 rounded-2xl shadow-2xl w-full max-w-sm p-6"
        >
            <h3 x-text="confirm.title" class="text-base font-semibold text-gray-900 dark:text-gemini-100"></h3>
            <p x-text="confirm.message" class="mt-2 text-sm text-gray-500 dark:text-gemini-400 leading-relaxed"></p>
            <div class="mt-6 flex gap-2 justify-end">
                <button @click="closeConfirm()" class="{{ $cancelBtn }}">Cancel</button>
                <button @click="runConfirm()" x-text="confirm.label" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors cursor-pointer"></button>
            </div>
        </div>
    </div>

    {{-- Create topic modal --}}
    <div
        x-show="activeModal === 'create-topic'"
        style="display: none;"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
    >
        <div @click="closeModal(); $wire.cancelCreateTopic()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div
            x-show="activeModal === 'create-topic'"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            class="relative bg-white dark:bg-gemini-700 rounded-2xl shadow-2xl w-full max-w-sm p-6"
        >
            <h3 class="text-base font-semibold text-gray-900 dark:text-gemini-100">New Topic</h3>
            <form wire:submit="createTopic" class="mt-4 flex flex-col gap-3">
                <div>
                    <input wire:model="name" type="text" placeholder="Topic name" autofocus class="{{ $inputClass }}"/>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <textarea wire:model="description" rows="3" placeholder="Description (optional)" class="{{ $inputClass }} resize-none"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 mt-2">
                    <button type="button" @click="closeModal(); $wire.cancelCreateTopic()" class="{{ $cancelBtn }}">Cancel</button>
                    <button type="submit" class="{{ $primaryBtn }}">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit topic modal --}}
    <div
        x-show="activeModal === 'edit-topic'"
        style="display: none;"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
    >
        <div @click="closeModal(); $wire.cancelEditTopic()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div
            x-show="activeModal === 'edit-topic'"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            class="relative bg-white dark:bg-gemini-700 rounded-2xl shadow-2xl w-full max-w-sm p-6"
        >
            <h3 class="text-base font-semibold text-gray-900 dark:text-gemini-100">Edit Topic</h3>
            <form wire:submit="updateTopic" class="mt-4 flex flex-col gap-3">
                <div>
                    <input wire:model="editName" type="text" placeholder="Topic name" autofocus class="{{ $inputClass }}"/>
                    @error('editName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <textarea wire:model="editDescription" rows="3" placeholder="Description (optional)" class="{{ $inputClass }} resize-none"></textarea>
                    @error('editDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 mt-2">
                    <button type="button" @click="closeModal(); $wire.cancelEditTopic()" class="{{ $cancelBtn }}">Cancel</button>
                    <button type="submit" class="{{ $primaryBtn }}">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Upload document modal --}}
    <div
        x-show="activeModal === 'upload-doc'"
        style="display: none;"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
    >
        <div @click="closeModal(); $wire.cancelUploadDocument()" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div
            x-show="activeModal === 'upload-doc'"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            class="relative bg-white dark:bg-gemini-700 rounded-2xl shadow-2xl w-full max-w-sm p-6"
        >
            <h3 class="text-base font-semibold text-gray-900 dark:text-gemini-100">Upload Document</h3>
            <form wire:submit="uploadDocument" class="mt-4 flex flex-col gap-3">

                {{-- File picker --}}
                <label class="block cursor-pointer">
                    <div class="border-2 border-dashed border-gray-200 dark:border-gemini-600 rounded-xl px-4 py-5 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                        <input type="file" wire:model="uploadedFile" accept=".txt,.md,.html,.htm,.pdf,.csv,.json,.xml,.docx,.xlsx,.jpg,.jpeg,.png,.gif,.webp" class="hidden"/>
                        @if($uploadedFile)
                            <svg class="w-8 h-8 mx-auto mb-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">{{ $uploadedFile->getClientOriginalName() }}</p>
                            <p class="text-xs text-gray-400 dark:text-gemini-500 mt-0.5">Click to change</p>
                        @else
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gemini-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gemini-400">Click to select a file</p>
                            <p class="text-xs text-gray-400 dark:text-gemini-500 mt-0.5">PDF, DOCX, XLSX, CSV, TXT, JPG, PNG and more</p>
                        @endif
                    </div>
                </label>
                @error('uploadedFile') <p class="text-red-500 text-xs -mt-1">{{ $message }}</p> @enderror

                {{-- OCR language picker (PDF only) --}}
                @if($uploadedFile)
                    @if($requiresOcrLanguages)
                        @if(count($availableOcrLanguages) > 0)
                            <div
                                x-data="{
                                    search: '',
                                    open: false,
                                    selected: $wire.entangle('selectedOcrLanguages'),
                                    languages: @js($availableOcrLanguages),
                                    get filtered() {
                                        if (!this.search) return this.languages;
                                        const q = this.search.toLowerCase();
                                        return this.languages.filter(l => l.toLowerCase().includes(q));
                                    },
                                    toggle(lang) {
                                        const idx = this.selected.indexOf(lang);
                                        if (idx >= 0) { this.selected.splice(idx, 1); }
                                        else if (this.selected.length < 3) { this.selected.push(lang); }
                                    },
                                    isSelected(lang) { return this.selected.includes(lang); }
                                }"
                                @click.outside="open = false; search = ''"
                            >
                                <p class="text-xs text-gray-500 dark:text-gemini-400 mb-1.5">OCR languages <span class="text-gray-400 dark:text-gemini-500">(1–3 required)</span></p>
                                <div
                                    @click="open = true; $nextTick(() => $refs.search.focus())"
                                    class="flex flex-wrap gap-1 p-1.5 border rounded-xl cursor-text transition-colors"
                                    :class="open ? 'border-indigo-500 ring-1 ring-indigo-500 bg-gray-50 dark:bg-gemini-800' : 'border-gray-200 dark:border-gemini-600 bg-gray-50 dark:bg-gemini-800'"
                                >
                                    <template x-for="lang in selected" :key="lang">
                                        <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs rounded-full shrink-0">
                                            <span x-text="lang"></span>
                                            <button type="button" @click.stop="toggle(lang)" class="hover:text-indigo-900 dark:hover:text-indigo-100 leading-none">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </span>
                                    </template>
                                    <input
                                        x-ref="search"
                                        x-model="search"
                                        @focus="open = true"
                                        @keydown.escape.stop="open = false; search = ''"
                                        :placeholder="selected.length === 0 ? 'Search languages…' : ''"
                                        class="flex-1 min-w-16 bg-transparent text-xs text-gray-700 dark:text-gemini-200 placeholder:text-gray-400 dark:placeholder:text-gemini-500 outline-none py-0.5"
                                    />
                                </div>
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="mt-1 max-h-40 overflow-y-auto border border-gray-200 dark:border-gemini-600 bg-white dark:bg-gemini-700 rounded-xl shadow-lg"
                                    style="display:none"
                                >
                                    <template x-for="lang in filtered" :key="lang">
                                        <button
                                            type="button"
                                            @click="toggle(lang); search = ''"
                                            :disabled="!isSelected(lang) && selected.length >= 3"
                                            class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-left transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                            :class="isSelected(lang) ? 'text-indigo-600 dark:text-indigo-400 font-medium bg-indigo-50 dark:bg-indigo-900/20' : 'text-gray-700 dark:text-gemini-200 hover:bg-gray-50 dark:hover:bg-gemini-600'"
                                        >
                                            <span x-text="lang" class="flex-1"></span>
                                            <svg x-show="isSelected(lang)" class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </button>
                                    </template>
                                    <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400 dark:text-gemini-500">No languages found.</p>
                                </div>
                                @error('selectedOcrLanguages') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="rounded-xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5">
                                <p class="text-xs font-medium text-amber-700 dark:text-amber-400">Tesseract OCR not configured</p>
                                <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">Run <code class="font-mono bg-amber-100 dark:bg-amber-900/40 px-1 rounded">tesseract --list-langs</code> to verify your installation and set <code class="font-mono bg-amber-100 dark:bg-amber-900/40 px-1 rounded">TESSERACT_BINARY</code> in .env</p>
                            </div>
                        @endif
                    @endif
                @endif

                {{-- Actions --}}
                <div class="flex gap-2 mt-1">
                    <button type="button" @click="closeModal(); $wire.cancelUploadDocument()" class="{{ $cancelBtn }}">Cancel</button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        @if($uploadedFile) @disabled($requiresOcrLanguages && count($selectedOcrLanguages) === 0) @endif
                        class="{{ $primaryBtn }} disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="uploadDocument">Upload & Process</span>
                        <span wire:loading wire:target="uploadDocument">Uploading…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
