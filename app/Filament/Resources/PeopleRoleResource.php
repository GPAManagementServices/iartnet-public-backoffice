<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeopleRoleResource\Pages;
use App\Models\PeopleRole;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PeopleRoleResource extends Resource
{
    protected static ?string $model = PeopleRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'People roles';

    protected static ?int $navigationSort = 15;

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof PeopleRole) {
            return null;
        }

        return (string) $record->name_en ?: 'Role #'.$record->getKey();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Role')
                ->schema([
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state) => $state !== null && $state !== '' ? Str::slug($state) : null)
                        ->helperText('Identificatore stabile (URL/API).'),

                    TextInput::make('name_en')
                        ->label('Name (EN)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('name_it')
                        ->label('Name (IT)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('sort_order')
                        ->label('Sort order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('name_en')->label('EN')->searchable()->sortable(),
                TextColumn::make('name_it')->label('IT')->searchable()->sortable(),
                TextColumn::make('people_count')
                    ->label('People (global)')
                    ->counts('people')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (PeopleRole $record) => $record->isInUse()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeopleRoles::route('/'),
            'create' => Pages\CreatePeopleRole::route('/create'),
            'edit' => Pages\EditPeopleRole::route('/{record}/edit'),
        ];
    }
}
