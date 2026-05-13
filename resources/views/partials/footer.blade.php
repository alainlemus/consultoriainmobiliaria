<footer class="bg-dark-900 text-cream-200">
    <div class="h-0.5 bg-gradient-to-r from-transparent via-gold-500 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3 mb-4">
                    @if(setting('logo'))
                        <img src="{{ Storage::url(setting('logo')) }}" alt="{{ setting('site_name', 'Consultoría Inmobiliaria') }}" class="h-14 w-auto object-contain">
                    @else
                        <svg viewBox="0 0 100 110" class="w-14 h-14" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50 5 L95 25 L95 60 Q95 90 50 105 Q5 90 5 60 L5 25 Z" fill="#1a1a1a" stroke="#C9A84C" stroke-width="3"/>
                            <rect x="20" y="22" width="60" height="53" rx="2" fill="#2a2a2a"/>
                            <rect x="10" y="10" width="80" height="14" rx="2" fill="#8B1A1A"/>
                            <text x="50" y="21" text-anchor="middle" fill="#C9A84C" font-size="6" font-weight="bold" font-family="Arial">BIENES RAÍCES</text>
                        </svg>
                    @endif
                    <div>
                        <span class="block text-gold-400 font-serif font-bold text-xl tracking-wide">CONSULTORÍA</span>
                        <span class="block text-cream-300 text-xs uppercase tracking-[0.3em]">Inmobiliaria</span>
                    </div>
                </div>
                <p class="font-script text-gold-400 text-lg mb-3">Tu patrimonio, nuestra prioridad.</p>
                <p class="text-cream-300 text-sm leading-relaxed max-w-sm">Expertos en créditos INFONAVIT y FOVISSSTE, avalúos y gestión inmobiliaria. Te acompañamos en cada paso para hacer realidad tu patrimonio.</p>
                <div class="flex gap-4 mt-5">
                    <span class="text-gold-400 text-xs flex items-center gap-1">✦ Confianza</span>
                    <span class="text-gold-400 text-xs flex items-center gap-1">✦ Experiencia</span>
                    <span class="text-gold-400 text-xs flex items-center gap-1">✦ Resultados</span>
                </div>
            </div>
            <div>
                <h4 class="text-gold-400 font-serif font-semibold text-base mb-4 uppercase tracking-wider">Servicios</h4>
                <ul class="space-y-2 text-sm text-cream-300">
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Crédito INFONAVIT</a></li>
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Crédito FOVISSSTE</a></li>
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Avalúos Inmobiliarios</a></li>
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Avalúos Fiscales</a></li>
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Gestión de Escrituras</a></li>
                    <li><a href="#servicios" class="hover:text-gold-400 transition-colors">Asesoría Personalizada</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-gold-400 font-serif font-semibold text-base mb-4 uppercase tracking-wider">Contacto</h4>
                <ul class="space-y-3 text-sm text-cream-300">
                    @php $waIcon = 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'; @endphp
                    @if(setting('whatsapp_1'))
                    <li><a href="https://wa.me/{{ setting('whatsapp_1') }}" target="_blank" class="hover:text-gold-400 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-400 fill-current shrink-0" viewBox="0 0 24 24"><path d="{{ $waIcon }}"/></svg>
                        {{ setting('telefono_1', '') }}
                    </a></li>
                    @endif
                    @if(setting('whatsapp_2'))
                    <li><a href="https://wa.me/{{ setting('whatsapp_2') }}" target="_blank" class="hover:text-gold-400 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-400 fill-current shrink-0" viewBox="0 0 24 24"><path d="{{ $waIcon }}"/></svg>
                        {{ setting('telefono_2', '') }}
                    </a></li>
                    @endif
                    <li class="pt-2 text-xs text-cream-300/60">Hidalgo · Veracruz · San Luis Potosí</li>
                </ul>

                {{-- Redes sociales --}}
                @php
                    $redes = [
                        'facebook_url'  => ['label' => 'Facebook',  'path' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
                        'instagram_url' => ['label' => 'Instagram', 'path' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z'],
                        'tiktok_url'    => ['label' => 'TikTok',    'path' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
                    ];
                @endphp
                @php $hayRedes = collect(['facebook_url','instagram_url','tiktok_url'])->filter(fn($r) => setting($r))->isNotEmpty(); @endphp
                @if($hayRedes)
                <div class="mt-5 flex items-center gap-3">
                    @foreach($redes as $clave => $red)
                        @if(setting($clave))
                        <a href="{{ setting($clave) }}" target="_blank" rel="noopener"
                           class="w-9 h-9 bg-dark-700 hover:bg-gold-500 border border-dark-600 hover:border-gold-500 rounded-sm flex items-center justify-center transition-all duration-200 group"
                           title="{{ $red['label'] }}">
                            <svg class="w-4 h-4 fill-current text-cream-300 group-hover:text-dark-900 transition-colors" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="{{ $red['path'] }}"/>
                            </svg>
                        </a>
                @endif
            </div>
        </div>
    </div>
                    @endforeach
                </div>
                @endif
        </div>
    </div>
    <div class="border-t border-dark-700">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="text-cream-300 text-xs">&copy; {{ date('Y') }} Consultoría Inmobiliaria. Todos los derechos reservados.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('aviso.privacidad') }}" class="text-cream-300/60 hover:text-gold-400 text-xs transition-colors">Aviso de Privacidad</a>
                <p class="text-gold-500/50 text-xs">Confianza &bull; Transparencia &bull; Resultados</p>
            </div>
        </div>
    </div>
</footer>
