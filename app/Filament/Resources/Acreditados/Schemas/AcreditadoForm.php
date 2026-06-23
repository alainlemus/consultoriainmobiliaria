<?php

namespace App\Filament\Resources\Acreditados\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcreditadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('curp'),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('nss'),
                TextInput::make('rfc'),
                TextInput::make('foto_perfil'),
                Select::make('contacto_id')
                    ->relationship('contacto', 'id'),
                Toggle::make('activo')
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                DateTimePicker::make('curp_verified_at'),
            ]);
    }
}
