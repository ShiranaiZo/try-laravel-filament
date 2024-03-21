<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Collection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Employee Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // section untuk Group form
                Section::make('Relationship')
                    ->schema([
                        Select::make('country_id')
                            ->relationship('country', 'name')
                            // Agar ketika country nya dirubah, langsung berubah datanya
                            ->live()
                            // agar bisa di search
                            ->searchable()
                            // Agar data nya muncul di awal
                            ->preload()
                            // agar ketika countries di silang/hapus, otomatis reset childern nya
                            ->afterStateUpdated(function (Set $set){
                                    $set('state_id', null);
                                    $set('city_id', null);
                                }
                            )
                            // Select lebih dari 1
                            // ->multiple()
                            ->required(),
                        Select::make('state_id')
                            // Agar datanya adalah anak dari countries
                            ->options(fn (Get $get): Collection => Collection::make(State::query()
                                ->where('country_id', $get('country_id'))
                                ->pluck('name', 'id')
                            ))
                            // Agar ketika country nya dirubah, langsung berubah datanya
                            ->live()
                            // agar bisa di search
                            ->searchable()
                            // Agar data nya muncul di awal
                            ->preload()
                            // agar ketika countries di silang/hapus, otomatis reset childern nya
                            ->afterStateUpdated(fn (Set $set) => $set('city_id', null))
                            // Select lebih dari 1
                            // ->multiple()
                            ->required(),
                        Select::make('city_id')
                            // Agar datanya adalah anak dari states
                            ->options(fn (Get $get): Collection => Collection::make(City::query()
                                ->where('state_id', $get('state_id'))
                                ->pluck('name', 'id')
                            ))
                            // Agar ketika statecountry nya dirubah, langsung berubah datanya
                            ->live()
                            // agar bisa di search
                            ->searchable()
                            // Agar data nya muncul di awal
                            ->preload()
                            // Select lebih dari 1
                            // ->multiple()
                            ->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            // agar bisa di search
                            ->searchable()
                            // Agar data nya muncul di awal
                            ->preload()
                            // Select lebih dari 1
                            // ->multiple()
                            ->required(),
                    ])->columns(2),
                Section::make('User Name')->description('Put the user name details in.')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),
                Section::make('User Address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')
                            // ->type('color') // untuk merubah tipe input menjadi color, bisa apa aja
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->required(),
                            Forms\Components\DatePicker::make('date_hired')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->required(),
                    ])
                    ->columns(2),

                // colom nya akan full
                // Forms\Components\DatePicker::make('date_hired')->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_hired')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    // Label di atas filter
                    ->label('Filter by Department'),
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    // info list harus static
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('Relationships')
                    ->schema([
                        TextEntry::make('country.name')->label('Country'),
                        TextEntry::make('state.name')->label('State'),
                        TextEntry::make('city.name')->label('City'),
                        TextEntry::make('department.name')->label('Department'),
                    ])->columns(2),
                ComponentsSection::make('Name')
                    ->schema([
                        TextEntry::make('first_name'),
                        TextEntry::make('middle_name'),
                        TextEntry::make('last_name'),
                    ])->columns(3),
                ComponentsSection::make('Address')
                    ->schema([
                        TextEntry::make('address'),
                        TextEntry::make('zip_code'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            // 'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
