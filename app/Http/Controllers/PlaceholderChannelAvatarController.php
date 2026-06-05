<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Support\Facades\Response;

/**
 * Generates a deterministic SVG avatar (first letter of channel name) for placeholder mode.
 */
class PlaceholderChannelAvatarController extends Controller
{
    public function __invoke(Channel $channel)
    {
        $name = trim((string) $channel->name);
        $char = mb_substr($name, 0, 1, 'UTF-8');

        if ($char === '' || $char === false) {
            $letter = '?';
        } else {
            $letter = mb_strtoupper($char, 'UTF-8');
        }

        $letter = htmlspecialchars($letter, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $hue = (int) ((crc32((string) $channel->id) % 360) + 360) % 360;
        $hue2 = ($hue + 48) % 360;

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="hsl({$hue},62%,42%)"/>
      <stop offset="1" stop-color="hsl({$hue2},58%,52%)"/>
    </linearGradient>
  </defs>
  <circle cx="128" cy="128" r="120" fill="url(#g)"/>
  <text x="128" y="148" fill="#ffffff" font-family="system-ui,-apple-system,sans-serif"
        font-size="104" font-weight="600" text-anchor="middle" dominant-baseline="middle">{$letter}</text>
</svg>
SVG;

        return Response::make($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
