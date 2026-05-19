@php
    $url = $getRecord()?->foto_perfil_url;
@endphp

<div class="flex items-center gap-4 py-2">
    @if ($url)
        <img
            src="{{ $url }}"
            alt="Foto de perfil"
            class="w-24 h-24 rounded-full object-cover ring-2 ring-primary-500"
            style="width:96px;height:96px;border-radius:9999px;object-fit:cover;"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
        />
        <div class="w-24 h-24 rounded-full bg-gray-200 items-center justify-center text-gray-400 text-sm hidden">
            Sin foto
        </div>
    @else
        <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-sm border-2 border-dashed border-gray-300">
            Sin foto
        </div>
        <p class="text-sm text-gray-500">El asesor aún no ha subido su foto desde la app móvil.</p>
    @endif
</div>
