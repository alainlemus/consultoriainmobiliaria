<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Models\TipoTramite;
use App\Models\User;
use App\Services\CargaMasivaService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class CrearDesdeCarptea extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string $resource = ExpedienteResource::class;
    protected string $view            = 'filament.pages.crear-desde-carpeta';
    protected static ?string $title   = 'Nuevo expediente desde carpeta';

    public array $data           = [];
    public array $rutasArchivos  = [];  // rutas relativas (webkitRelativePath) de cada archivo
    public array $tmpPaths       = [];  // rutas temporales de Livewire tras el upload
    public array $archivosFilepond = []; // propiedad requerida por WithFileUploads

    public function mount(): void
    {
        $this->form->fill([
            'asesor_id' => auth()->id(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->schema([
                Forms\Components\Select::make('tipo_tramite_id')
                    ->label('Tipo de trámite')
                    ->options(TipoTramite::where('activo', true)->orderBy('orden')->pluck('nombre', 'id'))
                    ->placeholder('Dejar vacío — se detecta del avalúo')
                    ->searchable(),

                Forms\Components\Select::make('asesor_id')
                    ->label('Asesor asignado')
                    ->options(User::where('activo', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->validationMessages(['required' => 'Debes asignar un asesor.']),
            ]);
    }

    public function crear(): void
    {
        $datos = $this->form->getState();

        if (empty($this->tmpPaths)) {
            Notification::make()->title('Selecciona una carpeta primero')->warning()->send();
            return;
        }

        // Reconstruir items desde los paths temporales de Livewire
        $items = [];

        foreach ($this->tmpPaths as $idx => $tmpPath) {
            // Livewire guarda temporales en livewire-tmp/ del disk local
            // El valor puede ser solo el nombre o la ruta completa
            $rutaEnDisk   = str_starts_with($tmpPath, 'livewire-tmp/') ? $tmpPath : 'livewire-tmp/' . $tmpPath;
            $rutaAbsoluta = Storage::disk('local')->path($rutaEnDisk);

            if (! file_exists($rutaAbsoluta)) {
                // Intentar como ruta absoluta directa
                $rutaAbsoluta = Storage::disk('local')->path($tmpPath);
            }

            if (! file_exists($rutaAbsoluta)) {
                \Illuminate\Support\Facades\Log::warning("Archivo tmp no encontrado: {$tmpPath}");
                continue;
            }

            $rutaRelativa = $this->rutasArchivos[$idx] ?? basename($tmpPath);

            $items[] = [
                'file' => new UploadedFile(
                    $rutaAbsoluta,
                    basename($rutaRelativa),
                    null,
                    null,
                    true
                ),
                'ruta_relativa' => $rutaRelativa,
            ];
        }

        if (empty($items)) {
            Notification::make()
                ->title('No se encontraron los archivos subidos')
                ->body('Intenta seleccionar la carpeta de nuevo.')
                ->danger()
                ->send();
            return;
        }

        $datosBase = array_filter([
            'tipo_tramite_id' => $datos['tipo_tramite_id'] ?? null,
            'asesor_id'       => $datos['asesor_id']       ?? auth()->id(),
        ]);

        try {
            /** @var CargaMasivaService $servicio */
            $servicio   = app(CargaMasivaService::class);
            $resultado  = $servicio->crearExpedienteDesdeArchivos($items, $datosBase);
            $expediente = $resultado['expediente'];

            $extraidos = $resultado['datos_extraidos'];
            unset($extraidos['_fuentes']);
            $camposRellenos = count(array_filter($extraidos));

            $msg = "Expediente {$expediente->folio} creado con {$resultado['documentos_creados']} documentos.";
            $msg .= ' El OCR corre en background — recibirás una notificación cuando termine.';
            if (! $expediente->tipo_tramite_id) {
                $msg .= ' Recuerda asignar el tipo de trámite.';
            }

            Notification::make()
                ->title('Expediente creado')
                ->body($msg)
                ->success()
                ->send();

            $this->redirect(ExpedienteResource::getUrl('edit', ['record' => $expediente]));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CargaMasiva error: ' . $e->getMessage());

            Notification::make()
                ->title('Error al procesar la carpeta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
