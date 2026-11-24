<?php

use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\WorkspaceController;
use App\Http\Controllers\Admin\WorkspaceSupportController;
use App\Http\Controllers\Agent\ConversationController as AgentConversationController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AgentAuthController;
use App\Http\Controllers\Auth\AgentInvitationController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SupportBroadcastAuthController;
use App\Http\Controllers\SupportWidgetController;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function (): void {
    Route::get('/', 'home')->name('home');
    Route::get('/services', 'services')->name('services');
    Route::get('/about', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
});

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/support/{workspace:public_token}', [SupportWidgetController::class, 'show'])->name('support.show');
Route::get('/support/embed/{workspace:public_token}', [SupportWidgetController::class, 'show'])
    ->name('support.embed');
Route::post('/support/{workspace:public_token}/conversations', [SupportWidgetController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('support.conversations.store');
Route::post('/support/broadcasting/auth', SupportBroadcastAuthController::class)
    ->middleware('throttle:120,1')
    ->name('support.broadcasting.auth');
Route::post('/support/conversations/{conversation}/poll', [SupportWidgetController::class, 'messages'])
    ->middleware('throttle:90,1')
    ->name('support.conversations.messages');
Route::post('/support/conversations/{conversation}/messages', [SupportWidgetController::class, 'send'])
    ->middleware('throttle:30,1')
    ->name('support.conversations.send');
Route::post('/support/conversations/{conversation}/rating', [SupportWidgetController::class, 'rate'])
    ->middleware('throttle:10,1')
    ->name('support.conversations.rating');
Route::post('/support/conversations/{conversation}/end', [SupportWidgetController::class, 'end'])
    ->middleware('throttle:30,1')
    ->name('support.conversations.end');

Route::middleware('guest')->group(function (): void {
    Route::controller(AdminAuthController::class)->group(function (): void {
        Route::get('/login', 'showLogin')->name('login');
        Route::post('/login', 'login')->name('login.store');
        Route::get('/register', 'showRegister')->name('register');
        Route::post('/register', 'register')->name('register.store');
    });

    Route::controller(AgentAuthController::class)->group(function (): void {
        Route::get('/agent/login', 'showLogin')->name('agent.login');
        Route::post('/agent/login', 'login')->name('agent.login.store');
    });
});

Route::get('/invitations/{token}', [AgentInvitationController::class, 'show'])->name('agent.invitation.show');
Route::post('/invitations/{token}', [AgentInvitationController::class, 'store'])->name('agent.invitation.store');

Route::post('/logout', [AdminAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::post('/agent/logout', [AgentAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('agent.logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('workspaces', WorkspaceController::class);
        Route::get('/workspaces/{workspace}/presence', [WorkspaceSupportController::class, 'presence'])
            ->name('workspaces.presence');
        Route::put('/workspaces/{workspace}/support-settings', [WorkspaceSupportController::class, 'update'])
            ->name('workspaces.support-settings.update');

        Route::get('/agents', [AdminAgentController::class, 'index'])->name('agents.index');
        Route::post('/workspaces/{workspace}/agents', [AdminAgentController::class, 'store'])->name('workspaces.agents.store');
        Route::delete('/workspaces/{workspace}/agents/{agent}', [AdminAgentController::class, 'destroy'])->name('workspaces.agents.destroy');
    });

Route::middleware(['auth', 'role:agent'])
    ->prefix('agent')
    ->name('agent.')
    ->group(function (): void {
        Route::get('/', AgentDashboardController::class)->name('dashboard');
        Route::get('/conversations', [AgentConversationController::class, 'index'])->name('conversations.index');
        Route::put('/presence', [AgentConversationController::class, 'presence'])->name('presence.update');
        Route::get('/conversations/{conversation}/messages', [AgentConversationController::class, 'messages'])
            ->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [AgentConversationController::class, 'send'])
            ->name('conversations.send');
        Route::post('/conversations/{conversation}/close', [AgentConversationController::class, 'close'])
            ->name('conversations.close');
    });
