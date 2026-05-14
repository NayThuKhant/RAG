<div class="h-full flex flex-col items-center justify-center text-center px-8">

    {{-- Sidebar toggle (shown when sidebar is closed) --}}
    <button
        x-show="!$store.sidebar.open"
        x-on:click="$store.sidebar.open = true"
        class="absolute top-3 left-3 p-1.5 bg-white dark:bg-gemini-800 rounded-lg shadow border border-gray-200 dark:border-gemini-500 text-gray-500 dark:text-gemini-400 hover:text-gray-700 dark:hover:text-gemini-200 transition-colors cursor-pointer"
        style="display: none;"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mb-6">
        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </div>

    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">RAG Assistant</h2>
    <p class="text-sm text-gray-500 dark:text-gemini-400 max-w-xs leading-relaxed">
        Select a topic from the sidebar to start chatting with your documents, or create a new topic to get started.
    </p>

    <div class="mt-8 flex flex-col gap-3 text-left max-w-sm w-full">
        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gemini-800/50">
            <span class="text-lg">📁</span>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gemini-200">Create a topic</p>
                <p class="text-xs text-gray-400 dark:text-gemini-400 mt-0.5">Organise your documents into topics like "Company Policies" or "Research Papers".</p>
            </div>
        </div>
        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gemini-800/50">
            <span class="text-lg">📄</span>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gemini-200">Upload documents</p>
                <p class="text-xs text-gray-400 dark:text-gemini-400 mt-0.5">Upload PDFs, Word docs, CSVs, Markdown files and more. They'll be processed and indexed automatically.</p>
            </div>
        </div>
        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gemini-800/50">
            <span class="text-lg">💬</span>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gemini-200">Chat with your data</p>
                <p class="text-xs text-gray-400 dark:text-gemini-400 mt-0.5">Ask questions and get answers backed by your documents, with inline citations.</p>
            </div>
        </div>
    </div>
</div>
