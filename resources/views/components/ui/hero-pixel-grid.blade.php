@php
    /**
     * Brutalist pixel-art hero visual: 25×25 grid of cells, each with a
     * vertical bar at the bottom. Bar heights are precomputed (deterministic)
     * to form a soft terrain dome centered on the grid with subtle ripples.
     *
     * A continuous CSS wave loops over the grid on a 2.4s cycle; per-cell
     * `animation-delay` makes the crest travel diagonally outward from the
     * grid center. Reduced-motion: the wave is fully disabled.
     */

    $size = 25;
    $cx = ($size - 1) / 2; // 12
    $cy = ($size - 1) / 2; // 12
@endphp

<div
    class="hero-pixel-grid select-none"
    aria-hidden="true"
>
    <div
        class="grid h-full w-full p-1"
        style="grid-template-columns: repeat({{ $size }}, minmax(0, 1fr)); grid-template-rows: repeat({{ $size }}, minmax(0, 1fr)); gap: 1px;"
    >
        @for ($y = 0; $y < $size; $y++)
            @for ($x = 0; $x < $size; $x++)
                @php
                    $dx = $x - $cx;
                    $dy = $y - $cy;
                    $dist2 = $dx * $dx + $dy * $dy;
                    // Deterministic terrain dome with a baked ripple
                    $base = 0.18 + 0.7 * exp(-$dist2 / 60) + 0.15 * sin(sqrt($dist2) * 0.7 + ($x + $y) * 0.3);
                    $base = max(0.1, min(0.95, $base));
                    $heightPct = round($base * 100);
                    // Wave-delay: cells farther from center crest later → diagonal sweep
                    $delay = round((($dx + $dy) * -0.09) + ((($x * 31 + $y * 17) % 7) / 100), 3);
                @endphp
                <div
                    class="relative bg-sand-100 overflow-hidden"
                    style="--base: {{ round($base, 3) }};"
                >
                    <span
                        class="absolute left-0 right-0 bottom-0 bg-coral-500
                               motion-reduce:animate-none
                               origin-bottom"
                        style="
                            height: {{ $heightPct }}%;
                            animation: heroPixelWave 2.4s linear infinite;
                            animation-delay: {{ $delay }}s;
                            transform: scaleY({{ round($base, 3) }});
                        "
                    ></span>
                </div>
            @endfor
        @endfor
    </div>
</div>
