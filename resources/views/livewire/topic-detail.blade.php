@php use App\Enums\DocumentStatus; use App\Enums\FileViewerMode; use App\Enums\MessageRole; use App\Models\TopicMessage; @endphp
<div
    class="h-full flex flex-col bg-white dark:bg-gemini-900"
    @if($hasProcessingDocuments) wire:poll.5s @endif
    x-data="{
        confirm: { open: false, title: '', message: '', label: 'Confirm', action: null },
        openConfirm(title, message, label, action) {
            this.confirm = { open: true, title, message, label, action };
        },
        runConfirm() {
            if (this.confirm.action) this.confirm.action();
            this.confirm.open = false;
        },
        closeConfirm() { this.confirm.open = false; }
    }"
    @keydown.escape.window="closeConfirm()"
>
    {{-- Header --}}
    <header
        class="shrink-0 h-16 bg-white dark:bg-gemini-800 border-b border-gray-200 dark:border-gemini-500 px-4 flex items-center gap-3">
        {{-- Sidebar toggle (mobile: always visible; desktop: visible when sidebar is closed) --}}
        <button
            x-show="!$store.sidebar.open"
            x-on:click="$store.sidebar.open = true"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gemini-400 dark:hover:text-gemini-200 hover:bg-gray-100 dark:hover:bg-gemini-700 transition-colors cursor-pointer shrink-0"
            title="Open sidebar"
            style="display: none;"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Topic info --}}
        <div class="flex-1 min-w-0">
            <h1 class="font-semibold text-gray-900 dark:text-white truncate text-sm">{{ $topic->name }}</h1>
        </div>

        {{-- Clear chat --}}
        @if($messages->isNotEmpty() || $isStreaming)
            <button
                @click="openConfirm('Clear chat', 'All messages in this conversation will be permanently deleted.', 'Clear', () => $wire.clearChat())"
                class="shrink-0 text-xs text-gray-400 hover:text-red-500 hover:bg-red-50 dark:text-gemini-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors cursor-pointer px-2.5 py-1.5 rounded-lg"
            >Clear chat
            </button>
        @endif
    </header>

    {{-- Chat area --}}
    <div class="flex-1 min-h-0 flex flex-col overflow-hidden relative">

        {{-- Messages --}}
        <div class="flex-1 min-h-0 overflow-y-auto" id="chat-messages">
            <div class="min-h-full max-w-2xl mx-auto px-6 py-8 flex flex-col gap-6">
                @forelse($messages as $msg)
                    @if($msg->role === MessageRole::User)
                        <div wire:key="msg-{{ $msg->id }}" class="flex justify-end">
                            <p class="bg-gray-100 dark:bg-gemini-700 text-gray-900 dark:text-gemini-100 text-sm rounded-2xl px-4 py-2.5 max-w-lg whitespace-pre-wrap">{{ $msg->content }}</p>
                        </div>
                    @else
                        <div wire:key="msg-{{ $msg->id }}">
                            @if($msg->content === TopicMessage::ASSISTANT_ERROR)
                                <div
                                    class="flex items-center gap-2 text-sm text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 rounded-lg px-3 py-2.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                    Unable to generate a response. Please try again.
                                </div>
                            @else
                                <div class="text-sm text-gray-800 dark:text-gemini-100 ai-response">
                                    {!! $msg->renderedContent !!}
                                </div>
                                @if($msg->inputTokens !== null || $msg->outputTokens !== null)
                                    <div
                                        class="mt-1.5 flex items-center gap-2 text-xs text-gray-400 dark:text-gemini-500">
                                        @if($msg->inputTokens !== null)
                                            <span title="Input tokens">↑ {{ number_format($msg->inputTokens) }}</span>
                                        @endif
                                        @if($msg->outputTokens !== null)
                                            <span title="Output tokens">↓ {{ number_format($msg->outputTokens) }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                @empty
                    @if (!$pendingMessage && !$isStreaming)
                        <div class="flex-1 flex flex-col items-center justify-center text-center py-20">
                            <div
                                class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-indigo-500 dark:text-indigo-400" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <p class="font-medium text-gray-700 dark:text-gemini-200">Chat with <span
                                    class="text-indigo-600 dark:text-indigo-400">{{ $topic->name }}</span></p>
                            @php $readyCount = $documents->where('status', DocumentStatus::Ready)->count(); @endphp
                            <p class="text-sm text-gray-400 dark:text-gemini-400 mt-1 max-w-xs">
                                @if($readyCount > 0)
                                    Ask anything about your {{ $readyCount }} {{ Str::plural('document', $readyCount) }}
                                    .
                                @else
                                    Upload documents from the sidebar to get started.
                                @endif
                            </p>
                        </div>
                    @endif
                @endforelse

                {{-- Optimistic user message --}}
                @if ($pendingMessage)
                    <div class="flex justify-end">
                        <p class="bg-gray-100 dark:bg-gemini-700 text-gray-900 dark:text-gemini-100 text-sm rounded-2xl px-4 py-2.5 max-w-lg whitespace-pre-wrap">{{ $pendingMessage }}</p>
                    </div>
                @endif

                {{-- Streaming assistant response --}}
                @if ($isStreaming)
                    <div
                        class="text-sm text-gray-800 dark:text-gemini-100"
                        x-data="{
                            hasContent: false,
                            init() {
                                new MutationObserver(() => {
                                    this.hasContent = this.$refs.stream.textContent.length > 0;
                                }).observe(this.$refs.stream, { childList: true, characterData: true, subtree: true });
                            }
                        }"
                    >
                        <div x-show="!hasContent" class="flex gap-1 items-center h-5">
                            <span class="w-2 h-2 bg-gray-400 dark:bg-gemini-600 rounded-full animate-bounce"
                                  style="animation-delay: 0ms"></span>
                            <span class="w-2 h-2 bg-gray-400 dark:bg-gemini-600 rounded-full animate-bounce"
                                  style="animation-delay: 150ms"></span>
                            <span class="w-2 h-2 bg-gray-400 dark:bg-gemini-600 rounded-full animate-bounce"
                                  style="animation-delay: 300ms"></span>
                        </div>
                        <span x-ref="stream" wire:stream="streamingMessage" class="whitespace-pre-wrap"></span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Document viewer overlay --}}
        @if($viewingDocumentId !== null)
            <div class="absolute inset-0 bg-white dark:bg-gemini-900 flex flex-col z-10"
                 x-data="{ tab: '{{ $viewingIsFailed ? 'parsed' : 'original' }}' }">

                <div class="shrink-0 px-4 pt-3 pb-2">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-gemini-100 truncate flex-1 min-w-0">{{ $viewingFilename }}</span>
                        <button wire:click="closeDocument"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gemini-400 dark:hover:text-gemini-200 hover:bg-gray-100 dark:hover:bg-gemini-700 transition-colors cursor-pointer shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex gap-1 bg-gray-100 dark:bg-gemini-700 rounded-lg p-0.5 w-fit">
                        <button
                            @click="tab = 'original'"
                            :class="tab === 'original' ? 'bg-white dark:bg-gemini-600 text-gray-700 dark:text-gemini-100 shadow-sm' : 'text-gray-400 dark:text-gemini-400 hover:text-gray-600'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer"
                        >Original</button>
                        <button
                            @click="tab = 'parsed'"
                            :class="tab === 'parsed' ? 'bg-white dark:bg-gemini-600 text-gray-700 dark:text-gemini-100 shadow-sm' : 'text-gray-400 dark:text-gemini-400 hover:text-gray-600'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer {{ $viewingIsFailed ? 'text-red-500 dark:text-red-400' : '' }}"
                        >{{ $viewingIsFailed ? 'Parsing errors' : 'Parsed text' }}</button>
                    </div>
                </div>

                <div class="flex-1 min-h-0 relative">
                    <div x-show="tab === 'original'" class="absolute inset-0 flex flex-col">
                        @if($viewingViewerMode === FileViewerMode::Image)
                            <div class="overflow-y-auto flex-1 flex items-center justify-center p-6">
                                <img src="{{ $viewingFileUrl }}" alt="{{ $viewingFilename }}" class="max-w-full max-h-full object-contain rounded-lg shadow-sm"/>
                            </div>
                        @elseif($viewingViewerMode === FileViewerMode::Download)
                            <div class="flex-1 flex flex-col items-center justify-center gap-3 p-8 text-center">
                                <p class="text-sm text-gray-500 dark:text-gemini-400">This file format cannot be previewed in the browser.</p>
                                <a
                                    href="{{ $viewingFileUrl }}"
                                    download="{{ $viewingFilename }}"
                                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors cursor-pointer"
                                >Download original</a>
                            </div>
                        @elseif($viewingViewerMode === FileViewerMode::Html)
                            <iframe sandbox srcdoc="{{ $viewingContent }}" class="w-full h-full border-0"></iframe>
                        @elseif($viewingViewerMode === FileViewerMode::Markdown)
                            <div class="overflow-y-auto flex-1">
                                <div class="max-w-2xl mx-auto px-6 py-8">
                                    <div class="text-sm text-gray-800 dark:text-gemini-100 ai-response">
                                        {!! Str::markdown($viewingContent, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="overflow-y-auto flex-1">
                                <div class="max-w-2xl mx-auto px-6 py-8">
                                    <pre class="text-xs text-gray-700 dark:text-gemini-200 whitespace-pre-wrap font-mono leading-relaxed">{{ $viewingContent }}</pre>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'parsed'" class="absolute inset-0 overflow-y-auto">
                        <div class="max-w-2xl mx-auto px-6 py-8">
                            @if(!$viewingIsFailed && $viewingParsedBy)
                                <div
                                    class="flex flex-wrap items-center gap-2 mb-5 pb-4 border-b border-gray-200 dark:border-gemini-600">
                                    <span
                                        class="text-xs font-medium text-gray-500 dark:text-gemini-300">Parsed by</span>
                                    <span
                                        class="text-xs font-semibold text-gray-800 dark:text-white bg-gray-200 dark:bg-gemini-600 px-2 py-0.5 rounded-md">{{ $viewingParsedBy['class'] }}</span>
                                    @foreach($viewingParsedBy['metadata'] as $flag => $value)
                                        @if($value)
                                            <span
                                                class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 rounded-md">{{ strtoupper($flag) }}</span>
                                        @endif
                                    @endforeach
                                    @if(!empty($viewingParsedBy['languages']))
                                        <span class="text-xs text-gray-500 dark:text-gemini-300 ml-1">Languages:</span>
                                        @foreach($viewingParsedBy['languages'] as $lang)
                                            <span
                                                class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-md">{{ strtoupper($lang) }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                            @if($viewingIsFailed)
                                <div
                                    class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 rounded-lg p-4">
                                    <svg class="w-4 h-4 text-red-500 dark:text-red-400 shrink-0 mt-0.5" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-red-700 dark:text-red-400 mb-1">Processing
                                            failed</p>
                                        @if($viewingErrorMessage)
                                            <pre
                                                class="text-xs text-red-600 dark:text-red-300 whitespace-pre-wrap break-all font-mono leading-relaxed">{{ $viewingErrorMessage }}</pre>
                                        @else
                                            <p class="text-xs text-red-500 dark:text-red-400">No error details
                                                available.</p>
                                        @endif
                                    </div>
                                </div>
                            @elseif($viewingExtractedText)
                                <pre
                                    class="text-xs text-gray-700 dark:text-gemini-200 whitespace-pre-wrap font-mono leading-relaxed">{{ $viewingExtractedText }}</pre>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gemini-400 text-center py-10">Parsed text is
                                    not available for this document.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Composer --}}
        <div class="shrink-0 bg-gray-50 dark:bg-gemini-900 px-6 pb-6 pt-4">
            <div class="max-w-2xl mx-auto">

                {{-- Follow-up suggestions --}}
                @if(!empty($followUpSuggestions) && !$isStreaming)
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($followUpSuggestions as $suggestion)
                            <button
                                wire:click="sendFollowUp({{ Js::from($suggestion) }})"
                                class="text-xs px-3 py-1.5 rounded-full border border-gray-200 dark:border-gemini-500 bg-white dark:bg-gemini-800 text-gray-600 dark:text-gemini-200 hover:border-indigo-400 dark:hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer"
                            >{{ $suggestion }}</button>
                        @endforeach
                    </div>
                @endif

                @if($documents->where('status', DocumentStatus::Ready)->isEmpty())
                    <p class="text-xs text-center text-amber-500 mb-3">Upload and process at least one document using
                        the sidebar to start chatting.</p>
                @endif

                <form
                    wire:submit="sendMessage"
                    x-data
                    x-on:submit="setTimeout(() => $el.querySelector('textarea').focus(), 50)"
                >
                    <div
                        class="relative rounded-2xl border border-gray-200 dark:border-gemini-500 bg-white dark:bg-gemini-700 focus-within:border-gray-300 dark:focus-within:border-gemini-400 transition-colors">
                        <textarea
                            wire:model="message"
                            placeholder="Ask a question about your documents…"
                            rows="2"
                            x-on:keydown.enter.prevent="!$event.shiftKey && !$wire.isStreaming && $el.closest('form').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}))"
                            class="w-full bg-transparent text-sm text-gray-900 dark:text-gemini-100 placeholder:text-gray-400 dark:placeholder:text-gemini-400 px-4 pt-3.5 pb-12 resize-none focus:outline-none"
                        ></textarea>
                        <div class="absolute bottom-3 right-3">
                            <button
                                type="submit"
                                :disabled="$wire.isStreaming"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                :class="$wire.isStreaming ? 'opacity-50 cursor-not-allowed bg-gray-300 dark:bg-gemini-600' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-white transition-colors shrink-0"
                            >
                                <svg x-show="!$wire.isStreaming" class="w-4 h-4" viewBox="0 0 24 24"
                                     fill="currentColor">
                                    <path fill-rule="evenodd"
                                          d="M12 20.25a.75.75 0 01-.75-.75V6.31l-5.47 5.47a.75.75 0 01-1.06-1.06l6.75-6.75a.75.75 0 011.06 0l6.75 6.75a.75.75 0 11-1.06 1.06l-5.47-5.47v13.19a.75.75 0 01-.75.75z"
                                          clip-rule="evenodd"/>
                                </svg>
                                <svg x-show="$wire.isStreaming" class="w-4 h-4 animate-spin" fill="none"
                                     viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </form>
            </div>
        </div>
    </div>

    {{-- Confirmation modal --}}
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
                <button @click="closeConfirm()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gemini-200 bg-gray-100 dark:bg-gemini-600 hover:bg-gray-200 dark:hover:bg-gemini-600 rounded-xl transition-colors cursor-pointer">
                    Cancel
                </button>
                <button @click="runConfirm()" x-text="confirm.label"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors cursor-pointer"></button>
            </div>
        </div>
    </div>
</div>

<script>
    function scrollToBottom() {
        const el = document.getElementById('chat-messages');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    function attachScrollObserver() {
        const el = document.getElementById('chat-messages');
        if (!el) return;
        new MutationObserver(scrollToBottom).observe(el, {
            childList: true,
            subtree: true,
            characterData: true,
        });
    }

    document.addEventListener('livewire:navigated', () => {
        scrollToBottom();
        attachScrollObserver();
    });

    attachScrollObserver();
    scrollToBottom();
</script>
