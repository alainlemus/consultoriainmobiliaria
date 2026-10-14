<section class="relative py-28 overflow-hidden md:py-36 bg-dark-900">
    <div class="absolute top-0 left-0 w-64 h-64 -translate-x-1/2 -translate-y-1/2 border rounded-full border-gold-500/10" x-parallax.0.12></div>
    <div class="absolute bottom-0 right-0 translate-x-1/2 translate-y-1/2 border rounded-full w-96 h-96 border-gold-500/10" x-parallax.-0.1></div>
    <div class="absolute inset-0 bg-[radial-gradient(50%_40%_at_50%_0%,rgba(205,157,54,0.14),transparent_70%)]"></div>
    <div class="relative z-10 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-20 text-center">
            <p class="mb-4 section-subtitle text-gold-400" x-reveal.blur>Nuestra diferencia</p>
            <h2 class="mb-4 font-serif text-4xl font-bold leading-[1.05] text-white md:text-6xl" style="letter-spacing:-0.035em" x-split>¿Por qué <span
                    class="text-gold-shine">elegirnos?</span></h2>
            <div class="gold-divider" x-reveal.scale.delay.300></div>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3 mb-14">
            @php
                $razones = [
                    [
                        'titulo' => 'Confianza',
                        'desc' =>
                            'Trabajamos con total transparencia. Conoces cada paso del proceso y no hay sorpresas. Tu tranquilidad es nuestra responsabilidad.',
                        'icon' =>
                            'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                    ],
                    [
                        'titulo' => 'Experiencia',
                        'desc' =>
                            'Años de trayectoria en el sector inmobiliario de Hidalgo, Veracruz y San Luis Potosí. Conocemos el proceso por dentro y por fuera.',
                        'icon' =>
                            'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
                    ],
                    [
                        'titulo' => 'Resultados',
                        'desc' =>
                            'Nos enfocamos en que obtengas tu crédito aprobado y tu hogar escriturado. Acompañamiento hasta la aprobación final.',
                        'icon' =>
                            'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
                    ],
                ];
            @endphp
            @foreach ($razones as $r)
                <div class="relative p-8 text-center transition-colors duration-500 border group rounded-3xl border-white/10 bg-white/[0.03] hover:bg-white/[0.07]" x-reveal.delay.{{ $loop->index * 150 }} x-tilt.5 data-spotlight>
                    <div
                        class="flex items-center justify-center w-20 h-20 mx-auto mb-6 transition-all duration-500 border-2 rounded-full border-gold-500 group-hover:bg-gold-500 group-hover:scale-110 group-hover:rotate-6">
                        <svg class="transition-colors w-9 h-9 text-gold-400 group-hover:text-dark-900" fill="none"
                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="mb-3 font-serif text-xl font-bold text-white">{{ $r['titulo'] }}</h3>
                    <p class="text-sm leading-relaxed text-cream-300">{{ $r['desc'] }}</p>
                </div>
            @endforeach
        </div>
        <div
            x-reveal.scale
            class="p-10 text-center border rounded-3xl bg-gradient-to-r from-gold-500/10 via-gold-500/20 to-gold-500/10 border-gold-500/30">
            <p class="mb-2 text-2xl font-script text-gold-400 sm:text-3xl">"Tu futuro, nuestra prioridad."</p>
            <p class="text-sm text-cream-300">Paga al terminar el trámite &bull; Sin desembolsos iniciales &bull;
                Cobertura
                en 4 estados</p>
        </div>
    </div>
</section>
