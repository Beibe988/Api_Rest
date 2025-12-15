<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogUserAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = $request->attributes->get('auth_user') ?? $request->user();
            if ($user && Schema::hasTable('User_Access')) {
                $ip = (string) $request->ip();
                $ua = substr((string) $request->userAgent(), 0, 255);

                $row = DB::table('User_Access')->where([
                    'id_user'    => $user->id,
                    'ip'         => $ip,
                    'user_agent' => $ua,
                ])->first();

                if ($row) {
                    DB::table('User_Access')->where('id', $row->id)->update([
                        'last_seen_at' => now(),
                        'hits'         => DB::raw('hits + 1'),
                        'updated_at'   => now(),
                    ]);
                } else {
                    DB::table('User_Access')->insert([
                        'id_user'      => $user->id,
                        'ip'           => $ip,
                        'user_agent'   => $ua,
                        'last_seen_at' => now(),
                        'hits'         => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // non rompere la risposta per problemi di logging
            // \Log::warning('LogUserAccess failed', ['err'=>$e->getMessage()]);
        }

        return $response;
    }
}

