<?php

namespace Database\Seeders;

use App\Models\ExpenseConcept;
use Illuminate\Database\Seeder;

class ExpenseConceptSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = [
            'AGUA',
            'GAS NATURAL',
            'ENERGÍA ELÉCTRICA',
            'ALQUILER DE SOFTWARE',
            'FUMIGACIÓN',
            'ENTRETENIMIENTO',
            'RECOLECCIÓN DE BASURA',
            'RENTA/ARRENDAMIENTO DE EQUIPO',
            'SEGURIDAD',
            'SERVICIOS DE PUBLICIDAD',
            'SERVICIOS MÉDICOS',
            'TELEFONÍA E INTERNET',
            'TELEVISIÓN DE PAGA',
            'ARTÍCULOS DE DECORACIÓN',
            'ARTÍCULOS DE OFICINA/PAPELERÍA',
            'ATENCIÓN CLIENTES',
            'CARBÓN',
            'CONCIERGE',
            'DESECHABLES',
            'ESTACIONAMIENTO',
            'GASOLINA',
            'GASTOS DE VIAJE OPERACIÓN',
            'HIELO',
            'LIMPIEZA/LAVANDERÍA',
            'MANTENIMIENTO DE MOB. Y EQ.',
            'MANTENIMIENTO LOCAL',
            'MATERIAL IMPRESO',
            'PRUEBA DE MENÚ',
            'TRANSPORTACIÓN DE PERSONAL',
            'ENVÍO Y MENSAJERÍA',
            'FLETES',
            'MOBILIARIO Y EQUIPO',
            'IGUALA TRÁMITES DE GESTORÍA',
            'LICENCIAS/PERMISOS',
            'SEGUROS',
        ];

        foreach ($concepts as $name) {
            ExpenseConcept::firstOrCreate(['name' => $name]);
        }
    }
}
