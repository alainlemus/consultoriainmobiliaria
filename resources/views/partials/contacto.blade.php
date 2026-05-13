{{-- Sección Contacto --}}
<section id="contacto" class="py-20 bg-dark-900 text-cream-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <p class="section-subtitle text-gold-400 mb-3">Contacto</p>
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4">
                Hablemos de tu <span class="text-gold-400">Patrimonio</span>
            </h2>
            <div class="gold-divider"></div>
            <p class="text-cream-300 max-w-xl mx-auto mt-4 text-sm">
                Cuéntanos tu situación y nuestros asesores te orientarán sin costo ni compromiso.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-5xl mx-auto">

            {{-- Info de contacto --}}
            <div class="space-y-8">

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-sm bg-gold-500/10 border border-gold-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Oficina Principal</h4>
                        @php
                            $oficinaId = setting('oficina_principal');
                            $oficina   = $oficinaId ? \App\Models\Cobertura::find($oficinaId) : null;
                        @endphp
                        <p class="text-cream-300 text-sm leading-relaxed">
                            @if($oficina)
                                {{ $oficina->detalle }}<br>
                                {{ $oficina->nombre }}
                            @else
                                Plaza Tecoluco, Av. Corona del Rosal<br>
                                Huejutla de Reyes, Hidalgo
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-sm bg-gold-500/10 border border-gold-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Teléfonos</h4>
                        <p class="text-cream-300 text-sm space-y-1">
                            @if(setting('telefono_1'))
                            <a href="tel:{{ setting('telefono_1') }}" class="block hover:text-gold-400 transition-colors">{{ setting('telefono_1') }}</a>
                            @endif
                            @if(setting('telefono_2'))
                            <a href="tel:{{ setting('telefono_2') }}" class="block hover:text-gold-400 transition-colors">{{ setting('telefono_2') }}</a>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-sm bg-gold-500/10 border border-gold-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gold-400 fill-current" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">WhatsApp</h4>
                        <div class="space-y-1">
                            @if(setting('whatsapp_1'))
                            <a href="https://wa.me/{{ setting('whatsapp_1') }}" target="_blank" rel="noopener"
                               class="block text-cream-300 text-sm hover:text-gold-400 transition-colors">
                                +{{ setting('whatsapp_1') }}
                            </a>
                            @endif
                            @if(setting('whatsapp_2'))
                            <a href="https://wa.me/{{ setting('whatsapp_2') }}" target="_blank" rel="noopener"
                               class="block text-cream-300 text-sm hover:text-gold-400 transition-colors">
                                +{{ setting('whatsapp_2') }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-sm bg-gold-500/10 border border-gold-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-1">Cobertura</h4>
                        <p class="text-cream-300 text-sm">
                            Hidalgo · Veracruz · San Luis Potosí
                        </p>
                    </div>
                </div>

                {{-- Redes sociales --}}
                @php
                    $redesContacto = [
                        'facebook_url'  => [
                            'label' => 'Facebook',
                            'path'  => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
                        ],
                        'instagram_url' => [
                            'label' => 'Instagram',
                            'path'  => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
                        ],
                        'tiktok_url'    => [
                            'label' => 'TikTok',
                            'path'  => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
                        ],
                    ];
                    $hayRedesContacto = collect(array_keys($redesContacto))->filter(fn($r) => setting($r))->isNotEmpty();
                @endphp

                @if($hayRedesContacto)
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-sm bg-gold-500/10 border border-gold-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white mb-3">Síguenos</h4>
                        <div class="flex items-center gap-3">
                            @foreach($redesContacto as $clave => $red)
                                @if(setting($clave))
                                <a href="{{ setting($clave) }}" target="_blank" rel="noopener"
                                   class="w-9 h-9 bg-dark-700 hover:bg-gold-500 border border-dark-600 hover:border-gold-500 rounded-sm flex items-center justify-center transition-all duration-200 group"
                                   title="{{ $red['label'] }}">
                                    <svg class="w-4 h-4 fill-current text-cream-300 group-hover:text-dark-900 transition-colors" viewBox="0 0 24 24">
                                        <path d="{{ $red['path'] }}"/>
                                    </svg>
                                </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- Formulario --}}
            <div>
                @if(session('success'))
                    <div class="mb-6 p-4 bg-gold-500/10 border border-gold-500/30 rounded-sm text-gold-400 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('contacto.store') }}" method="POST"
                      class="space-y-4"
                      x-data="{ enviando: false }"
                      @submit="enviando = true">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nombre" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Nombre *</label>
                            <input type="text" id="nombre" name="nombre"
                                   value="{{ old('nombre') }}"
                                   required
                                   placeholder="Tu nombre completo"
                                   class="input-field @error('nombre') border-crimson-500 @enderror">
                            @error('nombre')
                                <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="telefono" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Teléfono *</label>
                            <input type="tel" id="telefono" name="telefono"
                                   value="{{ old('telefono') }}"
                                   required
                                   placeholder="771 000 0000"
                                   class="input-field @error('telefono') border-crimson-500 @enderror">
                            @error('telefono')
                                <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Correo electrónico *</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               required
                               placeholder="correo@ejemplo.com"
                               class="input-field @error('email') border-crimson-500 @enderror">
                        @error('email')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="servicio" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Servicio de interés *</label>
                        <select id="servicio" name="servicio" required class="input-field @error('servicio') border-crimson-500 @enderror">
                            <option value="" class="bg-dark-800">— Selecciona un servicio —</option>
                            <option value="infonavit" class="bg-dark-800" {{ old('servicio') === 'infonavit' ? 'selected' : '' }}>Crédito INFONAVIT</option>
                            <option value="fovissste" class="bg-dark-800" {{ old('servicio') === 'fovissste' ? 'selected' : '' }}>Crédito FOVISSSTE</option>
                            <option value="avaluo"    class="bg-dark-800" {{ old('servicio') === 'avaluo'    ? 'selected' : '' }}>Avalúo</option>
                            <option value="escrituras"class="bg-dark-800" {{ old('servicio') === 'escrituras'? 'selected' : '' }}>Escrituración</option>
                            <option value="asesoria"  class="bg-dark-800" {{ old('servicio') === 'asesoria'  ? 'selected' : '' }}>Asesoría personalizada</option>
                            <option value="otro"      class="bg-dark-800" {{ old('servicio') === 'otro'      ? 'selected' : '' }}>Otro</option>
                        </select>
                        @error('servicio')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mensaje" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Mensaje *</label>
                        <textarea id="mensaje" name="mensaje" rows="4"
                                  required
                                  placeholder="Cuéntanos brevemente tu situación o consulta..."
                                  class="input-field resize-none @error('mensaje') border-crimson-500 @enderror">{{ old('mensaje') }}</textarea>
                        @error('mensaje')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="captcha" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">
                            Verificación: ¿Cuánto es
                            <span class="text-gold-400 font-bold">{{ $captcha['a'] ?? '?' }} + {{ $captcha['b'] ?? '?' }}</span>?
                            *
                        </label>
                        <input type="number" id="captcha" name="captcha"
                               required
                               min="0" max="20"
                               placeholder="Escribe el resultado"
                               autocomplete="off"
                               class="input-field @error('captcha') border-crimson-500 @enderror">
                        @error('captcha')
                            <p class="text-crimson-500 text-xs mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit"
                            :disabled="enviando"
                            class="btn-gold w-full justify-center disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!enviando">Enviar mensaje</span>
                        <span x-show="enviando" x-cloak>Enviando...</span>
                    </button>

                    <p class="text-xs text-dark-400 text-center">
                        * Campos obligatorios. Tu información es confidencial.
                    </p>
                </form>
            </div>

        </div>
    </div>
</section>
