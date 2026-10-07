<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ludo-room.{roomId}', function ($user, $roomId) {
    $isPlayer = \App\Models\GameMatchPlayer::where('game_match_id', $roomId)
        ->where('user_id', $user->id)
        ->exists();

    if ($isPlayer) {
        return ['id' => $user->id, 'username' => $user->username];
    }
    
    return false;
});

Broadcast::channel('cricket.{matchId}', function ($user, $matchId) {
    return \App\Models\GameMatchPlayer::where('game_match_id', $matchId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('cricket3d.{matchId}', function ($user, $matchId) {
    return \App\Models\GameMatchPlayer::where('game_match_id', $matchId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('carrom.{matchId}', function ($user, $matchId) {
    return \App\Models\GameMatchPlayer::where('game_match_id', $matchId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('pool.{matchId}', function ($user, $matchId) {
    return \App\Models\GameMatchPlayer::where('game_match_id', $matchId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('teen-patti-room.{roomId}', function ($user, $roomId) {
    $isPlayer = \App\Models\GameMatchPlayer::where('game_match_id', $roomId)
        ->where('user_id', $user->id)
        ->exists();

    if ($isPlayer) {
        return ['id' => $user->id, 'username' => $user->username];
    }
    
    return false;
});

// Tournament Channels
Broadcast::channel('presence-tournament.{tournamentId}', function ($user, $tournamentId) {
    $tournament = \App\Models\Tournament::find($tournamentId);
    if (!$tournament) {
        return false;
    }

    // Allow registered users and admins to join
    $isRegistered = \App\Models\TournamentRegistration::where('tournament_id', $tournamentId)
        ->where('user_id', $user->id)
        ->exists();
    
    $isAdmin = $user->is_admin ?? false;

    if ($isRegistered || $isAdmin) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }

    return false;
});

Broadcast::channel('tournament.{tournamentId}', function ($user, $tournamentId) {
    $tournament = \App\Models\Tournament::find($tournamentId);
    if (!$tournament) {
        return false;
    }

    $isRegistered = \App\Models\TournamentRegistration::where('tournament_id', $tournamentId)
        ->where('user_id', $user->id)
        ->exists();
    
    $isAdmin = $user->is_admin ?? false;

    return $isRegistered || $isAdmin;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
