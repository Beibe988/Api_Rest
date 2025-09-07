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

        $user = $request->user();
        if ($user && Schema::hasTable('User_Access')) {
            $ip = (string) $request->ip(); // assicurati di configurare TrustProxies se sei dietro proxy
            $ua = substr((string) $request->userAgent(), 0, 255);

            $exists = DB::table('User_Access')->where([
                'id_user'    => $user->id,
                'ip'         => $ip,
                'user_agent' => $ua,
            ])->first();

            if ($exists) {
                DB::table('User_Access')->where('id', $exists->id)->update([
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

        return $response;
    }
}

