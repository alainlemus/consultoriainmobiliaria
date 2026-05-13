<section class="py-24 bg-dark-900 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-64 h-64 border border-gold-500/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 border border-gold-500/10 rounded-full translate-x-1/2 translate-y-1/2"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16">
            <p class="section-subtitle text-gold-400 mb-3">Nuestra diferencia</p>
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4">¿Por qué <span class="text-gold-400">elegirnos?</span></h2>
            <div class="gold-divider"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-14">
            @php
            $razones = [
                ['titulo'=>'Confianza','desc'=>'Trabajamos con total transparencia. Conoces cada paso del proceso y no hay sorpresas. Tu tranquilidad es nuestra responsabilidad.','icon'=>'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                ['titulo'=>'Experiencia','desc'=>'Años de trayectoria en el sector inmobiliario de Hidalgo, Veracruz y San Luis Potosí. Conocemos el proceso por dentro y por fuera.','icon'=>'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
                ['titulo'=>'Resultados','desc'=>'Nos enfocamos en que obtengas tu crédito aprobado y tu hogar escriturado. Acompañamiento hasta la aprobación final.','icon'=>'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
            ];
            @endphp
            @foreach($razones as $r)
            <div class="text-center group">
                <div class="w-20 h-20 border-2 border-gold-500 rounded-full flex items-center justify-center mx-auto mb-5 group-hover:bg-gold-500 transition-colors duration-300">
                    <svg class="w-9 h-9 text-gold-400 group-hover:text-dark-900 transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}"/></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-white mb-3">{{ $r['titulo'] }}</h3>
                <p class="text-cream-300 text-sm leading-relaxed">{{ $r['desc'] }}</p>
            </div>
            @endforeach
        </div>
        <div class="bg-gradient-to-r from-gold-500/10 via-gold-500/20 to-gold-500/10 border border-gold-500/30 rounded-sm p-8 text-center">
            <p class="font-script text-gold-400 text-2xl sm:text-3xl mb-2">"Tu futuro, nuestra prioridad."</p>
            <p class="text-cream-300 text-sm">Trámite 100% financiado &bull; Sin desembolsos iniciales &bull; Cobertura en 3 estados</p>
        </div>
    </div>
</section>
