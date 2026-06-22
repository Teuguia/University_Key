<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->isCommunicationUser($user)) {
            return response()->json(['message' => 'Cette fonctionnalite est reservee aux etudiants et conseillers.'], 403);
        }

        $targetRole = $user->isEtudiant() ? 'conseiller' : 'etudiant';

        $contacts = DB::table('users')
            ->where('role', $targetRole)
            ->where('statut', 'actif')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'role' => $targetRole,
            ]);

        return response()->json(['data' => $contacts]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->markExpiredCallsAsMissed($user->id);

        $conversations = DB::table('conversations')
            ->join('users as student', 'student.id', '=', 'conversations.etudiant_id')
            ->join('users as counselor', 'counselor.id', '=', 'conversations.conseiller_id')
            ->where(fn ($query) => $query->where('conversations.etudiant_id', $user->id)->orWhere('conversations.conseiller_id', $user->id))
            ->orderByDesc('conversations.dernier_message_le')
            ->orderByDesc('conversations.updated_at')
            ->get([
                'conversations.id', 'conversations.sujet', 'conversations.statut', 'conversations.dernier_message_le',
                'student.id as student_id', 'student.name as student_name', 'counselor.id as counselor_id', 'counselor.name as counselor_name',
            ])
            ->map(function ($conversation) use ($user): array {
                $isStudent = (int) $conversation->student_id === (int) $user->id;
                $counterpartId = $isStudent ? $conversation->counselor_id : $conversation->student_id;
                $unread = DB::table('messages')
                    ->where('conversation_id', $conversation->id)
                    ->where('expediteur_id', '!=', $user->id)
                    ->whereNull('lu_le')
                    ->count();

                return [
                    'id' => $conversation->id,
                    'subject' => $conversation->sujet,
                    'status' => $conversation->statut,
                    'last_message_at' => $conversation->dernier_message_le,
                    'counterpart' => [
                        'id' => $counterpartId,
                        'name' => $isStudent ? $conversation->counselor_name : $conversation->student_name,
                        'role' => $isStudent ? 'conseiller' : 'etudiant',
                    ],
                    'unread_count' => $unread,
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'counterpart_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:160'],
        ]);

        $counterpart = User::findOrFail($validated['counterpart_id']);

        if (! $this->canCommunicate($user, $counterpart)) {
            return response()->json(['message' => 'Vous ne pouvez pas demarrer une conversation avec ce compte.'], 422);
        }

        $studentId = $user->isEtudiant() ? $user->id : $counterpart->id;
        $counselorId = $user->isConseiller() ? $user->id : $counterpart->id;
        $conversation = DB::table('conversations')
            ->where('etudiant_id', $studentId)
            ->where('conseiller_id', $counselorId)
            ->first();

        if (! $conversation) {
            $id = DB::table('conversations')->insertGetId([
                'etudiant_id' => $studentId,
                'conseiller_id' => $counselorId,
                'sujet' => $validated['subject'] ?? 'Echange d orientation',
                'statut' => 'ouverte',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $conversation = DB::table('conversations')->find($id);
        }

        return response()->json(['data' => $this->conversationPayload($conversation, $user)], 201);
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();
        $row = $this->conversationForUser($conversation, $user);

        if (! $row) {
            return response()->json(['message' => 'Conversation introuvable.'], 404);
        }

        DB::table('messages')
            ->where('conversation_id', $row->id)
            ->where('expediteur_id', '!=', $user->id)
            ->whereNull('lu_le')
            ->update(['lu_le' => now()]);

        $messages = DB::table('messages')
            ->join('users', 'users.id', '=', 'messages.expediteur_id')
            ->where('messages.conversation_id', $row->id)
            ->orderBy('messages.created_at')
            ->get(['messages.id', 'messages.contenu', 'messages.type', 'messages.created_at', 'messages.lu_le', 'messages.expediteur_id', 'users.name as sender_name'])
            ->map(fn ($message): array => [
                'id' => $message->id,
                'content' => $message->contenu,
                'type' => $message->type,
                'sent_at' => $message->created_at,
                'read_at' => $message->lu_le,
                'is_mine' => (int) $message->expediteur_id === (int) $user->id,
                'sender' => $message->sender_name,
            ]);

        return response()->json(['data' => ['conversation' => $this->conversationPayload($row, $user), 'messages' => $messages]]);
    }

    public function storeMessage(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();
        $row = $this->conversationForUser($conversation, $user);

        if (! $row || $row->statut !== 'ouverte') {
            return response()->json(['message' => 'Cette conversation est indisponible.'], 404);
        }

        $validated = $request->validate(['content' => ['required', 'string', 'max:4000']]);
        $now = now();
        $messageId = DB::table('messages')->insertGetId([
            'conversation_id' => $row->id,
            'expediteur_id' => $user->id,
            'type' => 'texte',
            'contenu' => trim($validated['content']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('conversations')->where('id', $row->id)->update(['dernier_message_le' => $now, 'updated_at' => $now]);

        $recipientId = $this->counterpartId($row, $user->id);
        $this->notify($recipientId, 'Nouveau message', "{$user->name} vous a envoye un message.", 'message', ['conversation_id' => $row->id]);

        return response()->json(['data' => ['id' => $messageId, 'content' => trim($validated['content']), 'sent_at' => $now, 'is_mine' => true, 'sender' => $user->name]], 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = DB::table('notifications')->where('user_id', $request->user()->id)->orderByDesc('created_at')->limit(50)->get()
            ->map(fn ($notification): array => ['id' => $notification->id, 'title' => $notification->titre, 'content' => $notification->contenu, 'type' => $notification->type, 'data' => json_decode($notification->data ?? '[]', true), 'read_at' => $notification->lu_le, 'created_at' => $notification->created_at]);

        return response()->json(['data' => $notifications]);
    }

    public function markNotificationRead(Request $request, int $notification): JsonResponse
    {
        $updated = DB::table('notifications')->where('id', $notification)->where('user_id', $request->user()->id)->whereNull('lu_le')->update(['lu_le' => now()]);
        return response()->json(['data' => ['updated' => $updated]]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $updated = DB::table('notifications')->where('user_id', $request->user()->id)->whereNull('lu_le')->update(['lu_le' => now()]);
        return response()->json(['data' => ['updated' => $updated]]);
    }

    public function startCall(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();
        $row = $this->conversationForUser($conversation, $user);
        $validated = $request->validate(['offer' => ['required', 'array'], 'offer.type' => ['required', Rule::in(['offer'])], 'offer.sdp' => ['required', 'string', 'max:20000']]);

        if (! $row || $row->statut !== 'ouverte') {
            return response()->json(['message' => 'Conversation indisponible pour un appel.'], 404);
        }

        DB::table('call_sessions')->where('conversation_id', $row->id)->where('status', 'ringing')->update(['status' => 'cancelled', 'ended_at' => now(), 'updated_at' => now()]);
        $recipientId = $this->counterpartId($row, $user->id);
        $callId = DB::table('call_sessions')->insertGetId([
            'conversation_id' => $row->id,
            'initiator_id' => $user->id,
            'recipient_id' => $recipientId,
            'status' => 'ringing',
            'offer_sdp' => json_encode($validated['offer']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->notify($recipientId, 'Appel audio entrant', "{$user->name} souhaite vous appeler.", 'appel', ['call_id' => $callId, 'conversation_id' => $row->id]);

        return response()->json(['data' => $this->callPayload(DB::table('call_sessions')->find($callId), $user)], 201);
    }

    public function incomingCalls(Request $request): JsonResponse
    {
        $this->markExpiredCallsAsMissed($request->user()->id);
        $calls = DB::table('call_sessions')->join('users', 'users.id', '=', 'call_sessions.initiator_id')->where('call_sessions.recipient_id', $request->user()->id)->where('call_sessions.status', 'ringing')->orderByDesc('call_sessions.created_at')->get(['call_sessions.*', 'users.name as initiator_name'])->map(fn ($call): array => $this->callPayload($call, $request->user()));
        return response()->json(['data' => $calls]);
    }

    public function showCall(Request $request, int $call): JsonResponse
    {
        $row = $this->callForUser($call, $request->user());
        return $row ? response()->json(['data' => $this->callPayload($row, $request->user())]) : response()->json(['message' => 'Appel introuvable.'], 404);
    }

    public function answerCall(Request $request, int $call): JsonResponse
    {
        $user = $request->user();
        $row = $this->callForUser($call, $user);
        $validated = $request->validate(['answer' => ['required', 'array'], 'answer.type' => ['required', Rule::in(['answer'])], 'answer.sdp' => ['required', 'string', 'max:20000']]);

        if (! $row || (int) $row->recipient_id !== (int) $user->id || $row->status !== 'ringing') {
            return response()->json(['message' => 'Cet appel ne peut plus etre accepte.'], 422);
        }

        DB::table('call_sessions')->where('id', $row->id)->update(['status' => 'accepted', 'answer_sdp' => json_encode($validated['answer']), 'answered_at' => now(), 'updated_at' => now()]);
        return response()->json(['data' => $this->callPayload(DB::table('call_sessions')->find($row->id), $user)]);
    }

    public function finishCall(Request $request, int $call): JsonResponse
    {
        $row = $this->callForUser($call, $request->user());
        if (! $row || ! in_array($row->status, ['ringing', 'accepted'], true)) {
            return response()->json(['message' => 'Appel introuvable ou deja termine.'], 404);
        }

        $status = $request->input('status') === 'rejected' && (int) $row->recipient_id === (int) $request->user()->id ? 'rejected' : 'ended';
        DB::table('call_sessions')->where('id', $row->id)->update(['status' => $status, 'ended_at' => now(), 'updated_at' => now()]);
        return response()->json(['data' => ['id' => $row->id, 'status' => $status]]);
    }

    private function conversationForUser(int $id, User $user): ?object
    {
        return DB::table('conversations')->where('id', $id)->where(fn ($query) => $query->where('etudiant_id', $user->id)->orWhere('conseiller_id', $user->id))->first();
    }

    private function callForUser(int $id, User $user): ?object
    {
        return DB::table('call_sessions')->where('id', $id)->where(fn ($query) => $query->where('initiator_id', $user->id)->orWhere('recipient_id', $user->id))->first();
    }

    private function counterpartId(object $conversation, int $userId): int
    {
        return (int) ((int) $conversation->etudiant_id === $userId ? $conversation->conseiller_id : $conversation->etudiant_id);
    }

    private function conversationPayload(object $conversation, User $user): array
    {
        $isStudent = (int) $conversation->etudiant_id === (int) $user->id;
        $counterpart = User::find($isStudent ? $conversation->conseiller_id : $conversation->etudiant_id);
        return ['id' => $conversation->id, 'subject' => $conversation->sujet, 'status' => $conversation->statut, 'counterpart' => ['id' => $counterpart?->id, 'name' => $counterpart?->name, 'role' => $counterpart?->role]];
    }

    private function callPayload(object $call, User $user): array
    {
        $initiatorName = $call->initiator_name ?? User::find($call->initiator_id)?->name;
        return ['id' => $call->id, 'conversation_id' => $call->conversation_id, 'status' => $call->status, 'is_initiator' => (int) $call->initiator_id === (int) $user->id, 'initiator' => ['id' => $call->initiator_id, 'name' => $initiatorName], 'offer' => json_decode($call->offer_sdp, true), 'answer' => $call->answer_sdp ? json_decode($call->answer_sdp, true) : null, 'created_at' => $call->created_at];
    }

    private function notify(int $userId, string $title, string $content, string $type, array $data = []): void
    {
        DB::table('notifications')->insert(['user_id' => $userId, 'titre' => $title, 'contenu' => $content, 'type' => $type, 'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function canCommunicate(User $first, User $second): bool
    {
        return $this->isCommunicationUser($first) && $this->isCommunicationUser($second) && $first->role !== $second->role && $second->statut === 'actif';
    }

    private function isCommunicationUser(User $user): bool
    {
        return ($user->isEtudiant() || $user->isConseiller()) && $user->statut === 'actif';
    }

    private function markExpiredCallsAsMissed(int $userId): void
    {
        DB::table('call_sessions')->where('recipient_id', $userId)->where('status', 'ringing')->where('created_at', '<', now()->subMinute())->update(['status' => 'missed', 'ended_at' => now(), 'updated_at' => now()]);
    }
}
