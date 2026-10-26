<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPARTMENT_CITIES = [
        'Amazonas' => [
            'Leticia', 'Puerto Nariño',
        ],
        'Antioquia' => [
            'Medellín', 'Bello', 'Itagüí', 'Envigado', 'Apartadó',
            'Turbo', 'Rionegro', 'Sabaneta', 'Caldas', 'La Estrella',
            'Caucasia', 'Copacabana', 'Girardota', 'Marinilla',
            'La Ceja', 'Guarne', 'El Carmen de Viboral',
            'Santa Fe de Antioquia', 'Carepa', 'Chigorodó',
        ],
        'Arauca' => [
            'Arauca', 'Saravena', 'Tame', 'Arauquita',
            'Fortul', 'Puerto Rondón', 'Cravo Norte',
        ],
        'Archipiélago de San Andrés, Providencia y Santa Catalina' => [
            'San Andrés', 'Providencia',
        ],
        'Atlántico' => [
            'Barranquilla', 'Soledad', 'Malambo', 'Puerto Colombia',
            'Galapa', 'Sabanalarga', 'Baranoa', 'Santo Tomás',
            'Sabanagrande', 'Palmar de Varela', 'Juan de Acosta',
            'Ponedera', 'Luruaco', 'Repelón', 'Polonuevo',
            'Tubará', 'Usiacurí', 'Campo de la Cruz', 'Manatí',
            'Candelaria',
        ],
        'Bogotá D.C.' => [
            'Bogotá',
        ],
        'Bolívar' => [
            'Cartagena de Indias', 'Magangué', 'Turbaco', 'Arjona',
            'El Carmen de Bolívar', 'Santa Rosa', 'María La Baja',
            'San Juan Nepomuceno', 'Santa Cruz de Mompox',
            'San Jacinto', 'Mahates', 'Calamar', 'Turbana',
            'Simití', 'San Pablo', 'Villanueva', 'Clemencia',
            'Santa Catalina', 'Cicuco', 'Cantagallo',
        ],
        'Boyacá' => [
            'Tunja', 'Duitama', 'Sogamoso', 'Chiquinquirá',
            'Puerto Boyacá', 'Paipa', 'Moniquirá', 'Villa de Leyva',
            'Garagoa', 'Guateque', 'Samacá', 'Nobsa', 'Tibasosa',
            'Cómbita', 'Ventaquemada', 'Soatá', 'Miraflores',
            'Aquitania', 'Turmequé', 'Ráquira',
        ],
        'Caldas' => [
            'Manizales', 'La Dorada', 'Villamaría', 'Chinchiná',
            'Riosucio', 'Anserma', 'Palestina', 'Neira', 'Supía',
            'Salamina', 'Aguadas', 'Pensilvania', 'Marmato',
            'Risaralda', 'Viterbo', 'Aranzazu', 'Filadelfia',
            'Manzanares', 'Marquetalia', 'Samaná',
        ],
        'Caquetá' => [
            'Florencia', 'San Vicente del Caguán', 'Puerto Rico',
            'El Doncello', 'Belén de los Andaquíes', 'La Montañita',
            'Curillo', 'El Paujil', 'Cartagena del Chairá',
            'San José del Fragua', 'Solano', 'Valparaíso',
            'Morelia', 'Milán', 'Solita', 'Albania',
        ],
        'Casanare' => [
            'Yopal', 'Aguazul', 'Villanueva', 'Tauramena',
            'Monterrey', 'Paz de Ariporo', 'Maní', 'Trinidad',
            'Hato Corozal', 'Orocué', 'Pore', 'Nunchía',
            'San Luis de Palenque', 'Sabanalarga', 'Chámeza',
            'Recetor', 'La Salina', 'Sácama', 'Támara',
        ],
        'Cauca' => [
            'Popayán', 'Santander de Quilichao', 'Puerto Tejada',
            'Patía', 'Piendamó', 'Guapi', 'Miranda', 'Corinto',
            'Caloto', 'Cajibío', 'Timbío', 'El Tambo', 'Silvia',
            'Toribío', 'Suárez', 'Buenos Aires', 'Villa Rica',
            'Inzá', 'Puracé', 'La Vega',
        ],
        'Cesar' => [
            'Valledupar', 'Aguachica', 'Agustín Codazzi', 'Bosconia',
            'La Jagua de Ibirico', 'Curumaní', 'Chiriguaná',
            'El Copey', 'La Paz', 'San Alberto', 'San Martín',
            'Pelaya', 'Pueblo Bello', 'Becerril', 'El Paso',
            'Gamarra', 'La Gloria', 'Astrea', 'Río de Oro',
            'Chimichagua',
        ],
        'Chocó' => [
            'Quibdó', 'Istmina', 'Tadó', 'Condoto', 'Acandí',
            'Bahía Solano', 'Nuquí', 'Riosucio', 'Carmen del Darién',
            'Lloró', 'Nóvita', 'Sipí', 'Unguía', 'Cértegui',
            'Bagadó', 'Juradó', 'El Cantón del San Pablo',
            'Medio Baudó', 'Alto Baudó', 'Medio San Juan',
        ],
        'Córdoba' => [
            'Montería', 'Cereté', 'Lorica', 'Sahagún',
            'Montelíbano', 'Planeta Rica', 'Tierralta',
            'Ciénaga de Oro', 'Puerto Libertador', 'Ayapel',
            'Chinú', 'San Pelayo', 'Pueblo Nuevo', 'La Apartada',
            'San Antero', 'San Bernardo del Viento', 'Moñitos',
            'Valencia', 'Canalete', 'Los Córdobas',
        ],
        'Cundinamarca' => [
            'Soacha', 'Chía', 'Facatativá', 'Zipaquirá',
            'Fusagasugá', 'Girardot', 'Mosquera', 'Madrid',
            'Funza', 'Cajicá', 'Sibaté', 'La Calera',
            'Tocancipá', 'Cota', 'Ubaté', 'Villeta',
            'Chocontá', 'Anapoima', 'Guaduas', 'La Mesa',
        ],
        'Guainía' => [
            'Inírida', 'Barrancominas',
        ],
        'Guaviare' => [
            'San José del Guaviare', 'El Retorno',
            'Calamar', 'Miraflores',
        ],
        'Huila' => [
            'Neiva', 'Pitalito', 'Garzón', 'La Plata',
            'Campoalegre', 'Gigante', 'Palermo', 'Rivera',
            'Acevedo', 'San Agustín', 'Isnos', 'Timaná',
            'Algeciras', 'Aipe', 'Tarqui', 'Tesalia', 'Hobo',
            'Yaguará', 'Guadalupe', 'Suaza',
        ],
        'La Guajira' => [
            'Riohacha', 'Maicao', 'Uribia', 'Manaure',
            'San Juan del Cesar', 'Fonseca', 'Villanueva',
            'Barrancas', 'Dibulla', 'Hatonuevo', 'Albania',
            'Distracción', 'El Molino', 'La Jagua del Pilar',
            'Urumita',
        ],
        'Magdalena' => [
            'Santa Marta', 'Ciénaga', 'Fundación', 'Plato',
            'El Banco', 'Zona Bananera', 'Aracataca', 'Pivijay',
            'Guamal', 'El Retén', 'Sitionuevo', 'Tenerife',
            'Pueblo Viejo', 'Chibolo', 'Salamina', 'Remolino',
            'Santa Ana', 'Nueva Granada',
            'San Sebastián de Buenavista', 'Algarrobo',
        ],
        'Meta' => [
            'Villavicencio', 'Acacías', 'Granada', 'Puerto López',
            'La Macarena', 'Puerto Gaitán', 'Restrepo', 'Cumaral',
            'San Martín', 'Vista Hermosa', 'Guamal',
            'San Carlos de Guaroa', 'Cabuyaro',
            'Castilla la Nueva', 'Lejanías', 'Mesetas',
            'Puerto Rico', 'El Castillo', 'Fuente de Oro',
            'Barranca de Upía',
        ],
        'Nariño' => [
            'Pasto', 'Tumaco', 'Ipiales', 'Túquerres',
            'Samaniego', 'La Unión', 'Sandoná', 'El Charco',
            'Barbacoas', 'Taminango', 'Guachucal', 'Cumbal',
            'Pupiales', 'Buesaco', 'El Tambo', 'Consacá',
            'Ricaurte', 'Roberto Payán', 'La Cruz', 'Linares',
        ],
        'Norte de Santander' => [
            'Cúcuta', 'Ocaña', 'Villa del Rosario', 'Los Patios',
            'Pamplona', 'Tibú', 'El Zulia', 'Ábrego',
            'Chinácota', 'Puerto Santander', 'Sardinata', 'Toledo',
            'Convención', 'El Carmen', 'La Esperanza', 'Cácota',
            'Durania', 'Bochalema', 'Gramalote', 'Pamplonita',
        ],
        'Putumayo' => [
            'Mocoa', 'Puerto Asís', 'Orito', 'Valle del Guamuez',
            'Villagarzón', 'Sibundoy', 'Puerto Caicedo',
            'San Miguel', 'Puerto Guzmán', 'Santiago', 'Colón',
            'San Francisco', 'Puerto Leguízamo',
        ],
        'Quindío' => [
            'Armenia', 'Calarcá', 'Montenegro', 'La Tebaida',
            'Quimbaya', 'Circasia', 'Filandia', 'Salento',
            'Génova', 'Pijao', 'Córdoba', 'Buenavista',
        ],
        'Risaralda' => [
            'Pereira', 'Dosquebradas', 'Santa Rosa de Cabal',
            'La Virginia', 'Santuario', 'Belén de Umbría',
            'Quinchía', 'Marsella', 'Apía', 'Pueblo Rico',
            'Mistrató', 'Guática', 'Balboa', 'La Celia',
        ],
        'Santander' => [
            'Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta',
            'Barrancabermeja', 'San Gil', 'Socorro', 'Vélez',
            'Barbosa', 'Málaga', 'Sabana de Torres',
            'Puerto Wilches', 'Lebrija', 'Rionegro', 'Cimitarra',
            'San Vicente de Chucurí', 'Charalá',
            'Puente Nacional', 'Oiba', 'Landázuri',
        ],
        'Sucre' => [
            'Sincelejo', 'Corozal', 'San Marcos', 'Sampués',
            'Santiago de Tolú', 'Toluviejo', 'San Onofre',
            'Sincé', 'Morroa', 'Galeras', 'Majagual', 'Coveñas',
            'Los Palmitos', 'Ovejas', 'Guaranda', 'San Pedro',
            'Colosó', 'Palmito', 'Buenavista', 'El Roble',
        ],
        'Tolima' => [
            'Ibagué', 'Espinal', 'Melgar', 'Honda', 'Líbano',
            'Chaparral', 'Mariquita', 'Flandes', 'Guamo',
            'Purificación', 'Fresno', 'Ortega', 'Planadas',
            'Lérida', 'Cajamarca', 'Venadillo', 'Rovira',
            'Coyaima', 'Natagaima', 'Saldaña',
        ],
        'Valle del Cauca' => [
            'Cali', 'Buenaventura', 'Palmira', 'Tuluá',
            'Cartago', 'Buga', 'Jamundí', 'Yumbo', 'Candelaria',
            'Florida', 'Pradera', 'Zarzal', 'Sevilla',
            'Caicedonia', 'Roldanillo', 'El Cerrito', 'Dagua',
            'La Unión', 'Ginebra', 'Guacarí',
        ],
        'Vaupés' => [
            'Mitú', 'Carurú', 'Taraira',
        ],
        'Vichada' => [
            'Puerto Carreño', 'La Primavera',
            'Santa Rosalía', 'Cumaribo',
        ],
    ];

    public function up(): void
    {
        $departments = DB::table('departments')
            ->whereIn('name', array_keys(self::DEPARTMENT_CITIES))
            ->pluck('id', 'name')
            ->all();
        $missingDepartments = array_diff(array_keys(self::DEPARTMENT_CITIES), array_keys($departments));

        if ($missingDepartments !== []) {
            throw new RuntimeException('No se encontraron departamentos para registrar sus ciudades: '.implode(', ', $missingDepartments));
        }

        $now = now();
        $cities = [];

        foreach (self::DEPARTMENT_CITIES as $departmentName => $cityNames) {
            foreach ($cityNames as $cityName) {
                $cities[] = [
                    'department_id' => $departments[$departmentName],
                    'name' => $cityName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($cities, 500) as $cityBatch) {
            DB::table('cities')->insertOrIgnore($cityBatch);
        }
    }

    public function down(): void
    {
        // Default catalog entries may have become user data; keep them on rollback.
    }
};
