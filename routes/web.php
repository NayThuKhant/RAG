<?php

use App\Livewire\RAG;
use App\Livewire\TopicDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', RAG::class)->name('home');
Route::get('/topics/{topic}', TopicDetail::class)->name('topics.show');
