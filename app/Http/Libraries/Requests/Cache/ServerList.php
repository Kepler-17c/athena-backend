<?php

namespace App\Http\Libraries\Requests\Cache;


use App\Models\Server;
use Http;

class ServerList implements CacheContract
{

    public function refreshRate(): int
    {
        return 30;
    }

    public function generate(): array
    {
        $wynnOnlinePlayers = Http::wynn()->get(config('athena.api.wynn.onlinePlayers'))->collect()->forget('request');
        if ($wynnOnlinePlayers === null) {
            throw new \Exception('Failed to fetch online players from Wynn API');
        }

        $result = [];

        // generating server data
        $validServers = [];
        foreach ($wynnOnlinePlayers as $key => $onlinePlayer) {
            $server = [];

            $validServers[] = $key;

            $server['firstSeen'] = Server::firstOrCreate(
                ['_id' => $key],
                ['firstSeen' => currentTimeMillis()]
            )->firstSeen;

            $server['players'] = $onlinePlayer;

            $result['servers'][$key] = $server;
        }

        // clean old servers
        Server::whereNotIn('_id', $validServers)->delete();

        return $result;
    }
}
