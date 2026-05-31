<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\SupportTicketNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function meta(): JsonResponse
    {
        return response()->json([
            'categories' => SupportTicket::CATEGORIES,
            'presets' => SupportTicket::PRESETS,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->serializeTicket($ticket));

        return response()->json(['tickets' => $tickets]);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        return response()->json([
            'ticket' => $this->serializeTicket($ticket->load('messages')),
        ]);
    }

    public function store(Request $request, SupportTicketNotificationService $notificationService): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'preset' => ['nullable', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'current_url' => ['nullable', 'string', 'max:2048'],
            'browser' => ['nullable', 'string', 'max:512'],
            'reported_at' => ['nullable', 'date'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = $request->user();
        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('support-screenshots', 'public');
        }

        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'category' => $validated['category'],
            'preset' => $validated['preset'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
            'current_url' => $validated['current_url'] ?? null,
            'browser' => $validated['browser'] ?? null,
            'reported_at' => $validated['reported_at'] ?? now(),
            'screenshot_path' => $screenshotPath,
        ]);

        $ticket->messages()->create([
            'user_id' => $user->id,
            'sender_type' => 'user',
            'sender_name' => $user->name ?: $user->email,
            'body' => $validated['message'],
        ]);

        $notificationService->notifyCreated($ticket);

        return response()->json([
            'success' => true,
            'ticket' => $this->serializeTicket($ticket->load('messages')),
        ], 201);
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'category' => $ticket->category,
            'category_label' => SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category,
            'preset' => $ticket->preset,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'status_label' => SupportTicket::STATUSES[$ticket->status] ?? $ticket->status,
            'current_url' => $ticket->current_url,
            'browser' => $ticket->browser,
            'reported_at' => optional($ticket->reported_at)->toIso8601String(),
            'screenshot_url' => $ticket->screenshot_path ? Storage::disk('public')->url($ticket->screenshot_path) : null,
            'created_at' => optional($ticket->created_at)->toIso8601String(),
            'messages' => $ticket->relationLoaded('messages')
                ? $ticket->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $message->sender_name,
                    'body' => $message->body,
                    'created_at' => optional($message->created_at)->toIso8601String(),
                ])->values()
                : [],
        ];
    }
}
