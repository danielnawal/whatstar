<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatbotIndustryTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            ['key'=>'optica',       'name'=>'Óptica',                  'icon'=>'fas fa-glasses',         'desc'=>'Bot pre-armado para ópticas: agenda exámenes, precios, marcas, ubicación.', 'rules'=>$this->opticaRules()],
            ['key'=>'farmacia',     'name'=>'Farmacia / Droguería',    'icon'=>'fas fa-pills',           'desc'=>'Consultas de disponibilidad, horarios, domicilios, medicamentos.',         'rules'=>$this->farmaciaRules()],
            ['key'=>'restaurante',  'name'=>'Restaurante',             'icon'=>'fas fa-utensils',        'desc'=>'Menú, reservas, domicilios, horarios, especiales del día.',                'rules'=>$this->restauranteRules()],
            ['key'=>'taller',       'name'=>'Taller mecánico',         'icon'=>'fas fa-wrench',          'desc'=>'Diagnósticos, citas, presupuestos, repuestos.',                            'rules'=>$this->tallerRules()],
            ['key'=>'inmobiliaria', 'name'=>'Inmobiliaria',            'icon'=>'fas fa-home',            'desc'=>'Búsqueda de propiedades, visitas, asesores, requisitos.',                  'rules'=>$this->inmobiliariaRules()],
            ['key'=>'salud',        'name'=>'Consultorio médico',      'icon'=>'fas fa-stethoscope',     'desc'=>'Citas, horarios, especialidades, seguros aceptados.',                      'rules'=>$this->saludRules()],
            ['key'=>'hotel',        'name'=>'Hotel / Hospedaje',       'icon'=>'fas fa-hotel',           'desc'=>'Reservas, tarifas, disponibilidad, servicios.',                            'rules'=>$this->hotelRules()],
            ['key'=>'gimnasio',     'name'=>'Gimnasio',                'icon'=>'fas fa-dumbbell',        'desc'=>'Membresías, horarios, clases, prueba gratis.',                             'rules'=>$this->gimnasioRules()],
            ['key'=>'escuela',      'name'=>'Escuela / Academia',      'icon'=>'fas fa-graduation-cap',  'desc'=>'Inscripciones, horarios, programas, requisitos.',                          'rules'=>$this->escuelaRules()],
            ['key'=>'gps',          'name'=>'Empresa GPS',             'icon'=>'fas fa-map-marker-alt',  'desc'=>'Venta de equipos GPS, planes, instalación, marca blanca.',                 'rules'=>$this->gpsRules()],
        ];

        foreach ($industries as $i) {
            DB::table('chatbot_industry_templates')->updateOrInsert(
                ['industry_key' => $i['key'], 'language' => 'es'],
                [
                    'industry_name' => $i['name'],
                    'icon'          => $i['icon'],
                    'description'   => $i['desc'],
                    'rules_json'    => json_encode($i['rules'], JSON_UNESCAPED_UNICODE),
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }

    private function tpl(string $kw, string $type, string $reply, int $prio = 5, array $extra = []): array
    {
        return array_merge(['keyword'=>$kw,'match_type'=>$type,'reply'=>$reply,'priority'=>$prio], $extra);
    }

    private function opticaRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,buenas tardes,buenas noches,saludos,hi,hello','like',
                "Bienvenido a *{NEGOCIO}*, su óptica de confianza.\n\n¿En qué le podemos ayudar hoy?\n\n1. AGENDAR examen visual\n2. PRECIOS de lentes y monturas\n3. MARCAS disponibles\n4. UBICACIÓN y horarios\n5. Hablar con un ASESOR\n\nEscriba el número de la opción o la palabra clave.", 0),
            $this->tpl('agenda,agendar,cita,examen,examen visual,turno','like',
                "*Agendar examen visual*\n\nPodemos atenderle hoy o mañana mismo.\n\nIndíquenos el día y la hora que prefiera. Un asesor le confirma la cita por este medio.", 6, ['trigger_handoff'=>1]),
            $this->tpl('precio,precios,costo,costos,cuanto cuesta,valor','like',
                "*Precios referenciales*\n\n- Lentes formulados desde \$80.000\n- Monturas desde \$120.000\n- Examen visual GRATIS con compra\n- Lentes de contacto desde \$50.000 mensuales\n\nEnvíenos foto de su fórmula y le cotizamos exacto.", 5),
            $this->tpl('marca,marcas,ray ban,oakley,vogue,carolina herrera','like',
                "*Marcas disponibles*\n\n- Ray Ban\n- Oakley\n- Vogue\n- Carolina Herrera\n- Polaroid\n- Y más de 30 marcas\n\n¿Busca alguna marca en particular?", 5),
            $this->tpl('ubicacion,donde,direccion,horario,abierto','like',
                "*Estamos en {DIRECCION}*\n\nHorario de atención:\n- Lunes a viernes: 9:00 a 19:00\n- Sábado: 9:00 a 17:00\n- Domingo: cerrado\n\nMapa: {MAPA_URL}", 5),
            $this->tpl('asesor,humano,hablar,llamar','like',
                "Un asesor le atiende ahora mismo.\n\nMientras tanto, cuéntenos: ¿qué necesita?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos su mensaje.\n\nEscriba una de estas opciones:\n- AGENDAR examen\n- PRECIOS\n- MARCAS\n- UBICACION\n- ASESOR", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function farmaciaRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,buenas tardes,saludos,hi','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿En qué le ayudamos?\n\n1. Consultar DISPONIBILIDAD de un medicamento\n2. Pedir DOMICILIO\n3. HORARIOS y ubicación\n4. MEDICAMENTOS controlados\n5. Hablar con un FARMACEUTA\n\nEscríbanos el nombre del medicamento si ya sabe lo que necesita.", 0),
            $this->tpl('disponible,disponibilidad,tienen,hay,venden,buscar medicamento','like',
                "*Consulta de disponibilidad*\n\nEscriba el nombre exacto del medicamento (o adjunte foto de la fórmula) y le confirmamos disponibilidad y precio al instante.", 6, ['trigger_handoff'=>1]),
            $this->tpl('domicilio,domicilios,enviar,llevan,delivery','like',
                "*Domicilios*\n\n- Cobertura: toda la ciudad\n- Costo: \$5.000 dentro de 5 km, \$8.000 fuera\n- Tiempo: 30 a 45 minutos\n- Pago: efectivo, transferencia, QR\n\nEnvíenos el medicamento y la dirección y lo despachamos.", 5),
            $this->tpl('horario,abierto,hora,cuando,cierran','like',
                "*Horario*\n\n- Lunes a sábado: 7:00 a 22:00\n- Domingo: 8:00 a 20:00\n- Festivos: 9:00 a 18:00\n\nDomicilios 24 horas con recargo nocturno.", 5),
            $this->tpl('controlado,formula,formulado,receta','like',
                "*Medicamentos controlados*\n\nNecesita:\n- Fórmula médica original (no copia)\n- Cédula del paciente\n- Vigencia: no mayor a 30 días\n\nPodemos despachar a domicilio si nos envía foto de la fórmula primero.", 5),
            $this->tpl('farmaceuta,asesor,humano,hablar','like',
                "Le conectamos con un farmaceuta ahora.\n\nMientras tanto cuéntenos: ¿qué consulta?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No le entendimos. Escriba:\n- DISPONIBILIDAD + nombre del medicamento\n- DOMICILIO\n- HORARIO\n- ASESOR", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function restauranteRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,buenas tardes,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿Qué se le antoja hoy?\n\n1. Ver MENÚ\n2. Hacer RESERVA\n3. Pedir DOMICILIO\n4. HORARIOS\n5. Hablar con MESERO\n\nEscriba la opción o lo que necesita.", 0),
            $this->tpl('menu,carta,que tienen,opciones','like',
                "*Nuestro menú*\n\n- Entradas desde \$12.000\n- Pastas \$25.000 a \$38.000\n- Carnes \$35.000 a \$58.000\n- Pizzas \$28.000 a \$45.000\n- Postres \$10.000 a \$18.000\n\nResponda *MENU PDF* para recibir el menú completo en PDF.", 5),
            $this->tpl('reserva,reservar,mesa,reservar mesa','like',
                "*Reservar mesa*\n\nIndíquenos:\n- ¿Para cuántas personas?\n- ¿Qué día y hora?\n- ¿Algún cumpleaños u ocasión especial?\n\nConfirmamos en menos de 5 minutos.", 6, ['trigger_handoff'=>1]),
            $this->tpl('domicilio,llevar,delivery,a domicilio','like',
                "*Domicilios*\n\n- Cobertura: 5 km a la redonda\n- Tiempo: 30 a 45 minutos\n- Mínimo: \$30.000\n- Envío: \$5.000 (gratis con pedidos superiores a \$60.000)\n- Pago: efectivo, transferencia, QR, tarjeta a la entrega\n\nIndíquenos qué se le antoja.", 5),
            $this->tpl('horario,hora,abierto,cuando','like',
                "*Horario*\n\n- Lunes a domingo: 12:00 a 22:00\n- Viernes y sábado: hasta las 23:00\n\nCocina abierta hasta 30 minutos antes de cerrar.", 5),
            $this->tpl('mesero,asesor,hablar,llamar,reclamo','like',
                "Le conectamos con un mesero ahora.\n\nMientras tanto cuéntenos: ¿en qué le ayudamos?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\nEscriba:\n- MENU\n- RESERVA\n- DOMICILIO\n- HORARIO\n- MESERO", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function tallerRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos','like',
                "Bienvenido a *{NEGOCIO}*, su taller de confianza.\n\n¿Qué necesita?\n\n1. DIAGNOSTICO (revisión gratuita)\n2. CITA para reparación\n3. PRESUPUESTO\n4. REPUESTOS\n5. EMERGENCIA / grúa\n\nDescríbanos qué le pasa a su vehículo.", 0),
            $this->tpl('diagnostico,revisar,revision,problema,no enciende,no arranca,falla','like',
                "*Diagnóstico gratuito*\n\nTráiganos el vehículo o cuéntenos por aquí:\n- Marca y modelo\n- Año\n- ¿Qué síntoma presenta?\n- ¿Desde cuándo?\n\nLe damos diagnóstico inicial en menos de 30 minutos cuando llega.", 6, ['trigger_handoff'=>1]),
            $this->tpl('cita,agendar,turno,reservar','like',
                "*Agendar cita*\n\nTenemos disponibilidad:\n- Hoy desde las 14:00\n- Mañana de 8:00 a 17:00\n\nIndíquenos marca, modelo, año y servicio que necesita. Un asesor confirma la cita.", 6, ['trigger_handoff'=>1]),
            $this->tpl('presupuesto,cuanto cuesta,precio,cotizacion','like',
                "*Presupuesto*\n\nPodemos darle presupuesto SIN COSTO al ver el vehículo.\n\nO si ya sabe qué necesita, envíenos foto y descripción y se lo cotizamos por aquí.", 5),
            $this->tpl('repuesto,repuestos,parte,filtro,aceite,llanta','like',
                "*Repuestos*\n\nManejamos:\n- Originales\n- Aftermarket de marca\n- Importados a pedido\n\nIndíquenos qué necesita y la marca/modelo, le confirmamos disponibilidad y precio.", 5),
            $this->tpl('emergencia,urgente,grua,varado,no prende','like',
                "*Emergencia*\n\nLlámenos ya al {TELEFONO}\nTenemos grúa disponible 24/7.\n\nMientras tanto, envíenos:\n- Su ubicación\n- Marca y modelo\n- ¿Qué pasó?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\nEscriba:\n- DIAGNOSTICO\n- CITA\n- PRESUPUESTO\n- REPUESTO\n- EMERGENCIA", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function inmobiliariaRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,buenas tardes,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿En qué le ayudamos?\n\n1. COMPRAR apartamento o casa\n2. ARRENDAR\n3. VENDER su propiedad\n4. Visitar inmueble\n5. Hablar con ASESOR\n\nCuéntenos qué busca.", 0),
            $this->tpl('comprar,compra,vendo apartamento,vendo casa,interesa comprar','like',
                "*Compra de inmuebles*\n\nIndíquenos:\n- Tipo: apartamento, casa, lote, oficina\n- Sector preferido\n- Habitaciones y baños\n- Presupuesto aproximado\n\nUn asesor le envía opciones que cumplan en menos de 1 hora.", 6, ['trigger_handoff'=>1]),
            $this->tpl('arrendar,arriendo,alquilar,alquiler,rentar','like',
                "*Arriendos disponibles*\n\nIndíquenos:\n- Tipo de inmueble\n- Sector\n- Número de habitaciones\n- Canon máximo\n\nLe enviamos opciones que estén disponibles ya mismo.", 6, ['trigger_handoff'=>1]),
            $this->tpl('vender,quiero vender,publicar,poner en venta','like',
                "*Vender con nosotros*\n\nServicios incluidos:\n- Avalúo gratuito\n- Sesión de fotos profesional\n- Publicación en más de 8 portales\n- Filtrado de compradores serios\n- Acompañamiento legal\n\nComisión solo si vendemos. Cuéntenos del inmueble.", 6, ['trigger_handoff'=>1]),
            $this->tpl('visita,visitar,ver inmueble,agendar visita','like',
                "*Agendar visita*\n\nIndíquenos:\n- Código o dirección del inmueble\n- Día y hora preferida\n\nConfirmamos en menos de 30 minutos.", 6, ['trigger_handoff'=>1]),
            $this->tpl('requisitos,papeles,documentos,necesito,codeudor','like',
                "*Requisitos para arrendar*\n\nPersona natural:\n- Cédula\n- Carta laboral o ingresos\n- Extractos bancarios de 3 meses\n- 1 codeudor con finca raíz\n\nO usar fianza o seguro de arrendamiento (sin codeudor).", 5),
            $this->tpl('asesor,hablar,humano,llamar','like',
                "Un asesor especializado le atiende ahora. Cuéntenos qué necesita.", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos. Escriba:\n- COMPRAR\n- ARRENDAR\n- VENDER\n- VISITA\n- ASESOR", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function saludRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿Qué necesita?\n\n1. Agendar CITA\n2. ESPECIALIDADES\n3. HORARIOS y ubicación\n4. SEGUROS aceptados\n5. URGENCIA\n\nEscriba lo que necesita.", 0),
            $this->tpl('cita,agendar,turno,reservar consulta','like',
                "*Agendar cita*\n\nIndíquenos:\n- Especialidad\n- Nombre completo\n- Documento\n- EPS o particular\n- Día y hora preferida\n\nConfirmamos cita en menos de 1 hora.", 6, ['trigger_handoff'=>1]),
            $this->tpl('especialidad,especialidades,medicos,doctores,doctor','like',
                "*Especialidades disponibles*\n\n- Medicina general\n- Pediatría\n- Ginecología\n- Cardiología\n- Dermatología\n- Psicología\n- Nutrición\n- Odontología\n\n¿Cuál necesita?", 5),
            $this->tpl('horario,abierto,hora,donde,direccion,ubicacion','like',
                "*Horario y ubicación*\n\n{DIRECCION}\n- Lunes a viernes: 7:00 a 19:00\n- Sábado: 8:00 a 14:00\n- Domingo: urgencias 24 horas\n\nParqueadero gratuito.", 5),
            $this->tpl('seguro,eps,sura,sanitas,colsanitas,compensar','like',
                "*Seguros aceptados*\n\n- Sura\n- Sanitas\n- Colsanitas\n- Compensar\n- Famisanar\n- Particular\n\nEnvíenos su carnet y verificamos cobertura.", 5),
            $this->tpl('urgencia,urgente,emergencia,me siento mal','like',
                "*Urgencias*\n\nLlame ya al {TELEFONO}\nEstamos en {DIRECCION}\n\nSi es vida o muerte, llame al 123 o al 911.\n\nMientras tanto un médico le contacta por aquí.", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\nEscriba:\n- CITA\n- ESPECIALIDADES\n- HORARIO\n- SEGUROS\n- URGENCIA", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function hotelRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿En qué le ayudamos?\n\n1. RESERVAR habitación\n2. TARIFAS\n3. DISPONIBILIDAD\n4. SERVICIOS\n5. Hablar con RECEPCIÓN\n\nCuéntenos qué necesita.", 0),
            $this->tpl('reservar,reservacion,hacer reserva','like',
                "*Reservar*\n\nIndíquenos:\n- Fechas (check-in y check-out)\n- Número de adultos y niños\n- Tipo de habitación\n- ¿Algún requerimiento especial?\n\nConfirmamos en 10 minutos.", 6, ['trigger_handoff'=>1]),
            $this->tpl('tarifa,precio,cuanto cuesta','like',
                "*Tarifas por noche*\n\n- Sencilla: \$180.000\n- Doble: \$240.000\n- Familiar: \$320.000\n- Suite: \$450.000\n\nIncluye desayuno, WiFi, parqueadero y piscina.", 5),
            $this->tpl('disponibilidad,disponible,hay cupo','like',
                "*Disponibilidad*\n\nIndíquenos fechas exactas (check-in y check-out) y confirmamos disponibilidad en 5 minutos.", 6, ['trigger_handoff'=>1]),
            $this->tpl('servicios,incluye,desayuno,piscina,gimnasio,spa','like',
                "*Servicios incluidos*\n\n- Desayuno buffet de 6:00 a 10:00\n- WiFi de alta velocidad\n- Piscina exterior\n- Gimnasio 24 horas\n- Parqueadero gratuito\n- Recepción 24 horas\n\nAdicionales: spa, restaurante, bar.", 5),
            $this->tpl('recepcion,asesor,humano,hablar','like',
                "Recepción le atiende ahora. ¿Qué necesita?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\n- RESERVAR\n- TARIFAS\n- DISPONIBILIDAD\n- SERVICIOS\n- RECEPCION", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function gimnasioRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿Qué necesita?\n\n1. PRUEBA gratuita\n2. PRECIOS de membresía\n3. HORARIOS y clases\n4. ENTRENADOR personalizado\n5. UBICACIÓN\n\nEscriba lo que le interese.", 0),
            $this->tpl('prueba,gratis,clase de prueba,probar','like',
                "*Prueba gratuita*\n\n- 1 día completo sin compromiso\n- Acceso a todas las máquinas\n- Tour por las instalaciones\n- Evaluación física inicial\n\nIndíquenos su nombre y cuándo quiere venir.", 6, ['trigger_handoff'=>1]),
            $this->tpl('precio,membresia,cuota,plan,planes','like',
                "*Membresías*\n\n- Día: \$15.000\n- Mensual: \$90.000\n- Trimestral: \$240.000 (11% de descuento)\n- Semestral: \$450.000 (17% de descuento)\n- Anual: \$780.000 (28% de descuento)\n\nIncluye pesas, cardio, clases grupales y casillero.", 5),
            $this->tpl('horario,abierto,hora,cuando','like',
                "*Horario*\n\n- Lunes a viernes: 5:00 a 23:00\n- Sábado: 7:00 a 20:00\n- Domingo: 8:00 a 16:00\n\nClases grupales: zumba, spinning, yoga, crossfit. Pida el cronograma.", 5),
            $this->tpl('entrenador,personalizado,personal trainer,instructor','like',
                "*Entrenador personalizado*\n\n- Plan nutricional y rutina personalizada\n- 3 sesiones semanales con entrenador\n- Seguimiento semanal\n\nPrecio: \$280.000 mensuales (incluye membresía).", 5),
            $this->tpl('ubicacion,donde,direccion,como llego','like',
                "*Ubicación*\n\n{DIRECCION}\n- Parqueadero gratuito\n- 5 minutos del metro\n\nMapa: {MAPA_URL}", 5),
            $this->tpl('asesor,hablar,humano','like',
                "Un entrenador le atiende. ¿En qué le ayudamos?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\n- PRUEBA gratuita\n- PRECIOS\n- HORARIO\n- ENTRENADOR\n- UBICACION", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function escuelaRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos','like',
                "Bienvenido a *{NEGOCIO}*.\n\n¿En qué le ayudamos?\n\n1. INSCRIPCIONES\n2. PROGRAMAS / cursos\n3. HORARIOS\n4. REQUISITOS y costos\n5. Hablar con ASESOR\n\nCuéntenos qué busca.", 0),
            $this->tpl('inscripcion,inscribir,matricular,matricula','like',
                "*Inscripciones abiertas*\n\nNecesita:\n- Documento de identidad\n- Foto reciente\n- Si es menor: documento del acudiente\n- Pago de matrícula\n\nProceso 100% en línea o presencial.", 6, ['trigger_handoff'=>1]),
            $this->tpl('programa,programas,curso,cursos,carreras,clases','like',
                "*Programas disponibles*\n\n- Inglés (todos los niveles)\n- Sistemas y programación\n- Diseño gráfico\n- Marketing digital\n- Excel avanzado\n- Inglés para negocios\n\n¿Cuál le interesa? Le enviamos el pénsum detallado.", 5),
            $this->tpl('horario,jornada,cuando empieza','like',
                "*Horarios*\n\n- Mañana: 6:00 a 12:00\n- Tarde: 13:00 a 18:00\n- Noche: 18:00 a 22:00\n- Sábados: 8:00 a 14:00\n\nPróximo inicio de clases: {PROX_INICIO}", 5),
            $this->tpl('precio,costo,cuanto cuesta,valor','like',
                "*Costos*\n\n- Matrícula: \$100.000\n- Mensualidad: desde \$180.000\n- Material incluido\n- Certificado al finalizar\n\nDescuento del 15% por pago anticipado.", 5),
            $this->tpl('asesor,humano,hablar,llamar','like',
                "Un asesor académico le atiende. ¿Qué necesita?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\n- INSCRIPCIONES\n- PROGRAMAS\n- HORARIOS\n- PRECIOS\n- ASESOR", -100, ['cooldown_minutes'=>5]),
        ];
    }

    private function gpsRules(): array
    {
        return [
            $this->tpl('hola,buenos dias,saludos,info,informacion','like',
                "Bienvenido a *{NEGOCIO}*.\n\nSomos especialistas en rastreo GPS.\n\n¿En qué le ayudamos?\n\n1. COMPRAR equipos GPS\n2. PRECIOS de planes\n3. DEMO de la plataforma\n4. Resolver PROBLEMA\n5. Hablar con ASESOR\n\nEscriba la opción.", 0),
            $this->tpl('comprar,equipos,gps,dispositivo,hardware','like',
                "*Equipos GPS*\n\nTrabajamos con más de 500 modelos:\n- Coban, Concox, Teltonika\n- Para autos, motos, camiones\n- Con corte de motor remoto\n- Sensores de combustible\n\nIndíquenos qué tipo de vehículo y cuántas unidades.", 6, ['trigger_handoff'=>1]),
            $this->tpl('precio,plan,planes,cuanto,cotizar','like',
                "*Planes de software*\n\n- Básico: \$5 USD por unidad mensual\n- Pro: \$7 USD por unidad mensual\n- Empresa: \$10 USD por unidad mensual\n\nIncluye:\n- Rastreo en tiempo real\n- Reportes\n- Alertas WhatsApp\n- App móvil\n\n¿Cuántas unidades manejará?", 5),
            $this->tpl('demo,probar,ver plataforma,prueba','like',
                "*Demo gratuita*\n\nLink: {DEMO_URL}\nUsuario: demo\nClave: demo123\n\nO agendamos demo en vivo de 15 minutos: un asesor le muestra todo y resuelve dudas.", 5),
            $this->tpl('problema,no funciona,no me llega,no aparece,offline','like',
                "*Soporte técnico*\n\nIndíquenos:\n- Marca y modelo del GPS\n- Placa de la unidad\n- ¿Desde cuándo el problema?\n- ¿Algún mensaje de error?\n\nResolvemos lo más rápido posible.", 7, ['trigger_handoff'=>1]),
            $this->tpl('asesor,hablar,humano,llamar','like',
                "Un asesor le atiende ya. ¿En qué le ayudamos?", 7, ['trigger_handoff'=>1]),
            $this->tpl('*','any',
                "No entendimos.\n\n- COMPRAR\n- PRECIOS\n- DEMO\n- PROBLEMA\n- ASESOR", -100, ['cooldown_minutes'=>5]),
        ];
    }
}
